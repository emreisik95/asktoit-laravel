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
 * Controls ALL Payment actions of Mobile ( Android & iOS )
 * 
 * Also, controls settings of Mobile ( Android & iOS )
 */
class MobilePaymentsController extends Controller {


    public function mobilePlanIdSettings(Request $request){

        if($request->isMethod('post')) {


            if($request->plan_id == 0){
                return back()->with(['message' => 'Please select a plan.', 'type' => 'error']);
            }

            if($request->plan_name_label == ""){
                return back()->with(['message' => 'Please enter a plan name.', 'type' => 'error']);
            }

            if($request->plan_type_label == ""){
                return back()->with(['message' => 'Please enter a plan type.', 'type' => 'error']);
            }

            if($request->revenuecat_package_id == ""){
                return back()->with(['message' => 'Please enter a RevenueCat Package ID.', 'type' => 'error']);
            }

            if($request->revenuecat_entitlement_id == ""){
                return back()->with(['message' => 'Please enter a RevenueCat Entitlement ID.', 'type' => 'error']);
            }
            
            $plan_id = $request->plan_id;

            $plan = PaymentPlans::find($plan_id);

            if(!$plan){
                return redirect()->back()->with('error', 'Plan not found');
            }

            $gatewayProducts = GatewayProducts::where('plan_id', $plan_id)->get();

            $revenueCatEntitlementFound = false;

            foreach($gatewayProducts as $gatewayProduct){
                if($gatewayProduct->gateway_code == "revenuecat"){
                    $revenueCatEntitlementFound = true;
                    $gatewayProduct->product_id = $request->revenuecat_package_id;
                    $gatewayProduct->price_id = $request->revenuecat_entitlement_id;
                    $gatewayProduct->save();
                }
            }

            if(!$revenueCatEntitlementFound){
                $gatewayProduct = new GatewayProducts();
                $gatewayProduct->plan_id = $plan_id;
                $gatewayProduct->plan_name = $request->plan_name_label;
                $gatewayProduct->gateway_code = "revenuecat";
                $gatewayProduct->gateway_title = "RevenueCat";
                $gatewayProduct->product_id = $request->revenuecat_package_id;
                $gatewayProduct->price_id = $request->revenuecat_entitlement_id;
                $gatewayProduct->save();
            }
            
            return back()->with(['message' => 'Plan updated successfully.', 'type' => 'success']);
        }



        $plans = PaymentPlans::with('gateway_products')->get();

        return view('panel.admin.finance.mobile.index', compact('plans'));
    }


}