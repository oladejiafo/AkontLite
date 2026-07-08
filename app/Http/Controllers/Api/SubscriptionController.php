<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use Illuminate\Http\Request;

class SubscriptionController extends Controller
{
    public function current(Request $request)
    {
        $company = $request->user()->activeCompany();

        if (!$company || !$company->subscription) {
            return response()->json(['data' => null]);
        }

        return response()->json([
            'data' => $company->subscription->load('plan'),
        ]);
    }

    public function upgrade(Request $request)
    {
        $request->validate([
            'plan_id' => 'required|exists:plans,id',
        ]);

        $company = $request->user()->activeCompany();

        if (!$company) {
            return response()->json(['message' => 'No company found'], 404);
        }

        $plan = Plan::findOrFail($request->plan_id);

        // Free plan, switch immediately, no payment needed
        if ($plan->price == 0) {
            $company->subscription()->updateOrCreate(
                ['company_id' => $company->id],
                ['plan_id' => $plan->id, 'status' => 'active']
            );

            return response()->json([
                'success' => true,
                'message' => 'Switched to Free plan',
            ]);
        }

        // Paid plan, needs a real checkout flow
        // For now, return a message. We wire this to Stripe/Paystack next.
        return response()->json([
            'message' => 'Paid plan checkout not yet connected. Coming next.',
        ], 501);
    }

    public function cancel(Request $request)
    {
        $company = $request->user()->activeCompany();

        if (!$company || !$company->subscription) {
            return response()->json(['message' => 'No active subscription found'], 404);
        }

        $freePlan = Plan::where('slug', 'free')->first();

        if (!$freePlan) {
            return response()->json(['message' => 'Free plan not configured'], 500);
        }

        $company->subscription->update([
            'plan_id' => $freePlan->id,
            'status' => 'active',
            'current_period_end' => null,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Subscription cancelled. You are now on the Free plan.',
        ]);
    }
}