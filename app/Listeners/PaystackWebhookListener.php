<?php

namespace App\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

use App\Events\PaystackWebhookEvent;
use App\Http\Controllers\Gateways\PaystackController;
use App\Models\PaymentPlans;
use App\Models\Setting;
use App\Models\Subscriptions as SubscriptionsModel;
use App\Models\User;
use App\Models\UserOrder;
use App\Models\WebhookHistory;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

use Throwable;


class PaystackWebhookListener
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    use InteractsWithQueue;
 
    public $afterCommit = true;

    /**
     * The name of the queue the job should be sent to.
     *
     * @var string|null
     */
    public $queue = 'default';
 
    /**
     * The time (seconds) before the job should be processed.
     *
     * @var int
     */
    public $delay = 0; //60

    /**
     * Handle the event.
     */
    public function handle(PaystackWebhookEvent $event): void
    {
        $settings = Setting::first();
        $incomingJson = json_decode($event->payload);
        $event_type = $incomingJson->event;
        $resource_type = '';
        $summary = '';
        $resource_id = '';
        $resource_state = '';

        if($event_type == 'invoice.update'){
            $resource_id = $incomingJson->data?->subscription?->subscription_code; // Subscription id
            $resource_type = $incomingJson->data?->subscription?->status == 'active' ? 'subscription' : 'prepaid';
            $summary = $incomingJson->data?->description;
            $resource_state = $incomingJson->data?->paid == true ? 'paid' : 'unpaid';
        }else if($event_type == 'subscription.disable'){
            $resource_id = $incomingJson->data?->subscription_code; // Subscription id
            $resource_type = 'subscription';
            $summary = $incomingJson->data?->status;
            $resource_state = 'cancelled';
        }

        $newData = new WebhookHistory();
        $newData->gatewaycode = 'paystack';
        $newData->webhook_id = $incomingJson->created_at;
        $newData->create_time = $incomingJson->created_at;
        $newData->resource_type = $resource_type; // Subscription / prepaid
        $newData->event_type = $event_type;
        $newData->summary = $summary;
        $newData->resource_id = $resource_id;
        $newData->resource_state = $resource_state;

        if($event_type == 'invoice.update'){
            $newData->amount_total = $incomingJson->data?->amount;
            $newData->amount_currency = $incomingJson->data?->transaction?->currency;
        }
        $newData->incoming_json = json_encode($incomingJson);
        $newData->status = 'check';
        $newData->save();

        if($event_type == 'subscription.disable'){
            // $resource_id is subscription id in this event.
            $currentSubscription = SubscriptionsModel::where('stripe_id', $resource_id)->first();
            if($currentSubscription->stripe_status != "cancelled"){
                $currentSubscription->stripe_status = "cancelled";
                $currentSubscription->ends_at = Carbon::now();
                $currentSubscription->save();
                $newData->status = 'checked';
                $newData->save();
            }

        }else if($event_type == 'invoice.update'){
            // $resource_id is subscription id in this event.
            $activeSub = SubscriptionsModel::where('stripe_id', $resource_id)->first();
            if(isset($activeSub->plan_id) == true) { // Plan may be deleted and null at database.

                // Get plan
                $plan = PaymentPlans::where('id', $activeSub->plan_id)->first();

                if($plan != null){
                    // Check status from gateway first
                    $currentStripeStatus = StripeController::getSubscriptionStatus($activeSub->user_id); 
                    
                    if($currentStripeStatus == true){ // active or trial at stripe side

                        // check for duplication
                        $duplicate = false;
                        // check for first payment in subscription
                        if(Carbon::parse($activeSub->created_at)->diffInMinutes(Carbon::parse($incomingJson->created_at)) < 5 ){
                            $duplicate = true;
                        }

                        if($duplicate == false){
                            // if it is trial then convert it to active
                            // if it is active and/or converted to active add plan word/image amount to the user
                            // if($activeSub->stripe_status == 'trialing'){} // it may be cancelled so in any case its going to be active
                            $activeSub->stripe_status = 'active';
                            $activeSub->save();

                            $payment = new UserOrder();
                            $payment->order_id = $incomingJson->created_at;
                            $payment->plan_id = $plan->id;
                            $payment->user_id = $activeSub->user_id;
                            $payment->payment_type = 'Paystack Recurring Payment';
                            $payment->price = $plan->price;
                            $payment->affiliate_earnings = ($plan->price*$settings->affiliate_commission_percentage)/100;
                            $payment->status = 'Success';
                            $payment->country = $user->country ?? 'Unknown';
                            $payment->save();

                            $user = User::where('id', $activeSub->user_id)->first();
                            $plan->total_words == -1? ($user->remaining_words = -1) : ($user->remaining_words += $plan->total_words);
                            $plan->total_images == -1? ($user->remaining_images = -1) : ($user->remaining_images += $plan->total_images);

                            $user->save();

                            $newData->status = 'checked';
                            $newData->save();
                        }
                    }
                }

            }else{ // plan id is null at subscription database table.
                if($activeSub->stripe_status != "cancelled"){
                    $activeSub->stripe_status = "cancelled";
                    $activeSub->ends_at = Carbon::now();
                    $activeSub->save();
                    $newData->status = 'checked';
                    $newData->save();
                }
                Log::error('Payment on a deleted plan. Please check: '.$resource_id.' with incoming webhook : '.json_encode($incomingJson));
            }   
        }
    }
    /**
     * Handle a job failure.
     */
    public function failed(PaystackWebhookEvent $event, Throwable $exception): void
    {
        $space = "*****";
        $msg = '\n'.$space.'\n'.$space;
        $msg = $msg.json_encode($event->payload);
        $msg = $msg.'\n'.$space.'\n';
        $msg = $msg.'\n'.$exception.'\n';
        $msg = $msg.'\n'.$space.'\n'.$space;

        Log::error($msg);
    }
}
