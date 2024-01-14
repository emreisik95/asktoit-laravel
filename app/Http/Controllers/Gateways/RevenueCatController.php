<?php

namespace App\Http\Controllers\Gateways;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Models\Currency;
use App\Models\CustomSettings;
use App\Models\GatewayProducts;
use App\Models\Gateways;
use App\Models\OldGatewayProducts;
use App\Models\PaymentPlans;
use App\Models\Setting;
use App\Models\Subscriptions as SubscriptionsModel;
use App\Models\SubscriptionItems;
use App\Models\HowitWorks;
use App\Models\User;
use App\Models\UserAffiliate;
use App\Models\UserOrder;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session;
use Carbon\Carbon;
use Brick\Math\BigDecimal;
use App\Models\Coupon;
use App\Http\Controllers\Gateways\IyzicoActions;
use App\Events\IyzicoWebhookEvent;




/**
 * Controls ALL Payment actions of RevenueCat
 */
class RevenueCatController extends Controller
{   
   
    public static function getSubscriptionDaysLeft() {
        $userId = Auth::user()->id;
        $activeSub = SubscriptionsModel::where([['stripe_status', '=', 'active'], ['user_id', '=', $userId]])->orWhere([['stripe_status', '=', 'trialing'], ['user_id', '=', $userId]])->first();
        if($activeSub != null){
            if($activeSub->stripe_status == 'trialing'){
                return \Carbon\Carbon::parse($activeSub->trial_ends_at)->diffInDays();
            }else{
                return \Carbon\Carbon::parse($activeSub->ends_at)->diffInDays();
            }
        }
        return null;

    }

    public static function getSubscriptionRenewDate() {
        $userId = Auth::user()->id;
        $activeSub = SubscriptionsModel::where([['stripe_status', '=', 'active'], ['user_id', '=', $userId]])->orWhere([['stripe_status', '=', 'trialing'], ['user_id', '=', $userId]])->first();
        if($activeSub != null){
            if($activeSub->stripe_status == 'trialing'){
                return \Carbon\Carbon::parse($activeSub->trial_ends_at)->format('F jS, Y');
            }else{
                return \Carbon\Carbon::parse($activeSub->ends_at)->format('F jS, Y');
            }
        }
        return null;

    }

    public static function getSubscriptionStatus($fromApi=false) {

        $user = Auth::user();
        $userId=$user->id;

        // Since this is RevenueCat (updates are made outside of our system), we need to get the subscription status from the RevenueCat API first
        // Get api key from gateway settings
        $gateway = Gateways::where('code', 'revenuecat')->first();
        if($gateway == null){
            if($fromApi){
                return response()->json(['message' => 'Gateway not found.'], 404);  
            }else{
                return null;
            }
        }

        $apiKey = $gateway->live_client_id;
        if($apiKey == null){
            if($fromApi){
                return response()->json(['message' => 'Gateway is not set properly.'], 412);  
            }else{
                return null;
            }
        }

        $settings = Setting::first();

        // Get user's revenuecat id
        $user = Auth::user();
        $userId=$user->id;
        $userRevenueCatId = $user->revenuecat_id;

        if($userRevenueCatId == null){
            if($fromApi){
                return response()->json(['message' => 'User is not set properly. User must login at least once from mobile app.'], 412);  
            }else{
                return null;
            }
        }

        // Get subscription status from RevenueCat API
        $url = "https://api.revenuecat.com/v1/subscribers/" . $userRevenueCatId;
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Authorization: Bearer ' . $apiKey,
            'Content-Type: application/json'
        ));
        $response = curl_exec($ch);
        curl_close($ch);

        if($response == null || $response == "" || $response == "{}"){
            if($fromApi){
                return response()->json(['message' => 'Error getting subscription status from RevenueCat API.'], 500);  
            }else{
                return null;
            }
        }

        $response = json_decode($response, true);

        $subscriber = $response['subscriber'];

        

        ///////////////////////////
        /// TOKEN PACKS
        ///////////////////////////
        $tokenPacks = $subscriber['non_subscriptions'];

        // check if all token packs are applied and saved to database
        /*
            "non_subscriptions": {
                "onetime": [
                    {
                    "id": "cadba0c81b",
                    "is_sandbox": true,
                    "purchase_date": "2019-04-05T21:52:45Z",
                    "store": "app_store"
                    }
                ],
            },
        */
        foreach($tokenPacks as $identifier => $purchasedPacksArray){

            foreach($purchasedPacksArray as $pack) {

                $orders = UserOrder::where([['user_id', '=', $userId], ['order_id', '=', $pack['id']]])->get();

                if(count($orders) == 0){
                    // Create order
                    $gatewayProduct = GatewayProducts::where('product_id', $identifier)->first();
                    if($gatewayProduct == null){
                        $gatewayProduct = GatewayProducts::where('price_id', $identifier)->first();
                        if($gatewayProduct == null){
                            Log::error('Gateway product not found for RevenueCat. Identifier: ' . $identifier);
                            if($fromApi){
                                return response()->json(['message' => 'Gateway product not found.'], 404);  
                            }else{
                                return null;
                            }
                        }
                    }

                    $plan = PaymentPlans::where('id', $gatewayProduct->plan_id)->first();
                    if($plan == null){
                        Log::error('Plan not found for RevenueCat. Identifier: ' . $identifier);
                        if($fromApi){
                            return response()->json(['message' => 'Plan not found.'], 404);  
                        }else{
                            return null;
                        }
                    }

                    $order = new UserOrder();
                    $order->order_id = $pack['id'];
                    $order->plan_id = $plan->id;
                    $order->type = 'prepaid';
                    $order->user_id = $userId;
                    $order->payment_type = 'RevenueCat';
                    $order->price = $plan->price; // set coupons at stores
                    $order->affiliate_earnings = ($plan->price * $settings->affiliate_commission_percentage)/100;
                    $order->status = 'Success';
                    $order->country = $user->country ?? 'Unknown';
                    $order->save();

                    $plan->total_words == -1? ($user->remaining_words = -1) : ($user->remaining_words += $plan->total_words);
                    $plan->total_images == -1? ($user->remaining_images = -1) : ($user->remaining_images += $plan->total_images);

                    $user->save();
                }else{
                    /// Order already exists, do nothing
                }

            }

        }
            


        ///////////////////////////
        /// SUBSCRIPTIONS
        ///////////////////////////
        $subscriptions = $subscriber['subscriptions'];
        /*
            "rc_promo_pro_cat_monthly": {
                "auto_resume_date": null,
                "billing_issues_detected_at": null,
                "expires_date": "2019-08-26T01:02:16Z",
                "grace_period_expires_date": null,
                "is_sandbox": false,
                "original_purchase_date": "2019-07-26T01:02:16Z",
                "ownership_type": "FAMILY_SHARED",
                "period_type": "normal",
                "purchase_date": "2019-07-26T01:02:16Z",
                "refunded_at": null,
                "store": "promotional",
                "unsubscribe_detected_at": null
            }
        */

        /// We are going to use "purchase_date" instead of "original_purchase_date" because "original_purchase_date" is not updated when user renews the subscription

        foreach($subscriptions as $identifier => $subscriptionsArray){

            foreach($subscriptionsArray as $subs) {

                $orderId = (string)$subs['purchase_date'];
                $orderId = str_replace("-", "", $orderId);
                $orderId = str_replace(":", "", $orderId);
                $orderId = str_replace(" ", "", $orderId);
                $orderId = str_replace("T", "", $orderId);
                $orderId = str_replace("Z", "", $orderId);


                $orders = UserOrder::where([['user_id', '=', $userId], ['order_id', '=', $orderId]])->get();

                if(count($orders) == 0){
                    // Create order
                    $gatewayProduct = GatewayProducts::where('product_id', $identifier)->first();
                    if($gatewayProduct == null){
                        $gatewayProduct = GatewayProducts::where('price_id', $identifier)->first();
                        if($gatewayProduct == null){
                            Log::error('Gateway product not found for RevenueCat. Identifier: ' . $identifier);
                            if($fromApi){
                                return response()->json(['message' => 'Gateway product not found.'], 404);  
                            }else{
                                return null;
                            }
                        }
                    }

                    $plan = PaymentPlans::where('id', $gatewayProduct->plan_id)->first();
                    if($plan == null){
                        Log::error('Plan not found for RevenueCat. Identifier: ' . $identifier);
                        if($fromApi){
                            return response()->json(['message' => 'Plan not found.'], 404);  
                        }else{
                            return null;
                        }
                    }

                    $planId = $plan->id;

                    $order = new UserOrder();
                    $order->order_id = $orderId;
                    $order->plan_id = $planId;
                    $order->type = 'subscription';
                    $order->user_id = $userId;
                    $order->payment_type = 'RevenueCat';
                    $order->price = $plan->price; // set coupons at stores
                    $order->affiliate_earnings = ($plan->price * $settings->affiliate_commission_percentage)/100;
                    $order->status = 'Success';
                    $order->country = $user->country ?? 'Unknown';
                    $order->save();

                    $status = 'cancelled';

                    if($subs['unsubscribe_detected_at'] == null){
                        // Subscription is active
                        $isTrial = $subs['period_type'] == 'trial';
                        $status = $subs['billing_issues_detected_at'] == null ? ($isTrial == true ? 'trialing' : 'active') : 'cancelled';
                        if($subs['billing_issues_detected_at'] != null) {
                            Log::error("Billing issue detected at RevenueCat. User ID: " . $userId . " Subscription: " . $identifier);
                        }
                        /// Plan is active, and we haven't added to orders before; so this is a new subscription. Hence add the plan to user's remaining words and images
                        $plan->total_words == -1? ($user->remaining_words = -1) : ($user->remaining_words += $plan->total_words);
                        $plan->total_images == -1? ($user->remaining_images = -1) : ($user->remaining_images += $plan->total_images);
                    }else{
                        // Subscription is cancelled
                        if($subs['billing_issues_detected_at'] != null) {
                            Log::error("Billing issue detected at RevenueCat. User ID: " . $userId . " Subscription: " . $identifier);
                        }
                        if(Carbon::parse($subs['unsubscribe_detected_at'])->isBefore(Carbon::parse($subs['expires_date']))){
                            /// Subscription is cancelled before the end date (user cancelled it)
                            /// Since user cancelled it, we need to remove the plan from user's remaining words and images
                            $plan->total_words == -1? ($user->remaining_words = 0) : ($user->remaining_words -= $plan->total_words);
                            $plan->total_images == -1? ($user->remaining_images = 0) : ($user->remaining_images -= $plan->total_images);
                        }
                    }

                    /// check if subscription is already added
                    $subscription = SubscriptionsModel::where([['user_id', '=', $userId], ['stripe_id', '=', $orderId]])->first();
                    if($subscription == null) {
                        // Subscription is not added, add it
                        $subscription = new SubscriptionsModel();
                        $subscription->user_id = $userId;
                        $subscription->name = $planId;
                        $subscription->stripe_id = $orderId;
                        $subscription->stripe_status = $status;
                        $subscription->stripe_price = $gatewayProduct->price_id;
                        $subscription->quantity = 1;
                        $subscription->trial_ends_at = $subs['expires_date'];
                        $subscription->ends_at = $subs['expires_date'];
                        $subscription->plan_id = $planId;
                        $subscription->paid_with = 'revenuecat';
                        $subscription->save();

                        $subscriptionItem = new SubscriptionItems();
                        $subscriptionItem->subscription_id = $subscription->id;
                        $subscriptionItem->stripe_id = $orderId;
                        $subscriptionItem->stripe_product = $gatewayProduct->product_id;
                        $subscriptionItem->stripe_price = $gatewayProduct->price_id;
                        $subscriptionItem->quantity = 1;
                        $subscriptionItem->save();
                    }else{
                        // Subscription is already added, update it
                        $subscription->stripe_status = $status;
                        $subscription->trial_ends_at = $subs['expires_date'];
                        $subscription->ends_at = $subs['expires_date'];
                        $subscription->save();
                    }

                    $user->save();
                }else{
                    /// Order already exists, but maybe subscription is cancelled

                    $isTrial = $subs['period_type'] == 'trial';
                    $status = $subs['billing_issues_detected_at'] == null ? ($isTrial == true ? 'trialing' : 'active') : 'cancelled';

                    if($subs['unsubscribe_detected_at'] != null){
                        // Subscription is cancelled
                        if($subs['billing_issues_detected_at'] != null) {
                            Log::error("Billing issue detected at RevenueCat. User ID: " . $userId . " Subscription: " . $identifier);
                        }
                        if(Carbon::parse($subs['unsubscribe_detected_at'])->isBefore(Carbon::parse($subs['expires_date']))){
                            /// Subscription is cancelled before the end date (user cancelled it)
                            /// Since user cancelled it, we need to remove the plan from user's remaining words and images
                            $plan->total_words == -1? ($user->remaining_words = 0) : ($user->remaining_words -= $plan->total_words);
                            $plan->total_images == -1? ($user->remaining_images = 0) : ($user->remaining_images -= $plan->total_images);
                        }
                    }

                    /// check if subscription is already added
                    $subscription = SubscriptionsModel::where([['user_id', '=', $userId], ['stripe_id', '=', $orderId]])->first();
                    if($subscription == null) {
                        // Subscription is added while creating order
                        Log::error("Subscription is added while creating order. But not found on checks. User ID: " . $userId . " Subscription: " . $identifier);
                    }else{
                        // Subscription is already added, update it
                        $subscription->stripe_status = $status;
                        $subscription->trial_ends_at = $subs['expires_date'];
                        $subscription->ends_at = $subs['expires_date'];
                        $subscription->save();
                    }

                    $user->save();

                }
            }

        }

        ///////////////////////////
        /// Prepare Result
        ///////////////////////////

        /// We have checked both subscriptions and token packs, now we need to check if there is any active subscription and return the result

        // Get current active subscription
        $activeSub = SubscriptionsModel::where([['stripe_status', '=', 'active'], ['user_id', '=', $userId]])->orWhere([['stripe_status', '=', 'trialing'], ['user_id', '=', $userId]])->first();
        if($activeSub != null){
            if($fromApi){
                return response()->json(['message' => 'Active subscription found.', 'status' => 'active'], 200);
            }
            return true;
        }
        return false;
        
    }
    
    public static function checkIfTrial() {
        $userId = Auth::user()->id;
        $activeSub = SubscriptionsModel::where([['stripe_status', '=', 'active'], ['user_id', '=', $userId]])->orWhere([['stripe_status', '=', 'trialing'], ['user_id', '=', $userId]])->first();
        if($activeSub != null){
            return $activeSub->stripe_status == 'trialing';
        }
        return false;
    }


}