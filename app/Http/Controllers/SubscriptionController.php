<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Jobs\CreateSubscriptionJob;
use App\Jobs\UpdateSubscriptionJob;
use App\Jobs\CancelSubscriptionJob;
use App\Jobs\ResumeSubscriptionJob;
use Illuminate\Support\Facades\Log;


class SubscriptionController extends Controller
{
    private const PRICE_IDS = [
        'basic_monthly' => 'price_1Ra9wzDgYV6zJ17vI6UiuhLp',
        'basic_yearly' => 'price_1Ra9xgDgYV6zJ17v4zCAQGLZ',
        'premium_monthly' => 'price_1Ra9ynDgYV6zJ17v2HMSLJpe',
        'premium_yearly' => 'price_1Ra9zADgYV6zJ17v7B4zNE1r',
    ];

    public function createSubscription(Request $request): JsonResponse
    {
        $request->validate([
            'price_id' => 'required|string|in:basic_monthly,basic_yearly,    premium_monthly,premium_yearly',
            'payment_method' => 'required|string',
        ]);

        $user = $request->user();
        $priceId = self::PRICE_IDS[$request->price_id];

        try {
            $result = dispatch_sync(new CreateSubscriptionJob($user, $priceId, $request->payment_method));

            return response()->json([
                'success' => true,
                'subscription' => $result,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    public function updateSubscription(Request $request): JsonResponse
    {
        $request->validate([
            'price_id' => 'required|string|in:basic_monthly,basic_yearly,premium_monthly,premium_yearly',
        ]);

        $user = $request->user();
        $priceId = self::PRICE_IDS[$request->price_id];

        try {
            $result = dispatch_sync(new UpdateSubscriptionJob($user, $priceId));

            return response()->json([
                'success' => true,
                'message' => 'Subscription updated successfully',
                'subscription' => $result,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }
    // Controller method
    public function cancelSubscription(Request $request): JsonResponse
    {
        $user = $request->user();

        try {
            // Using dispatchSync with non-queued job
            $result = CancelSubscriptionJob::dispatchSync($user->id);

            return response()->json([
                'success' => true,
                'message' => 'Subscription cancelled successfully',
                'ends_at' => $result['ends_at'],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }
    public function resumeSubscription(Request $request): JsonResponse
    {
        $user = $request->user();

        try {
            $result = dispatch(new ResumeSubscriptionJob($user));

            return response()->json([
                'success' => true,
                'message' => $result['message'],
                'subscription' => $result['subscription'],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Resume failed: ' . $e->getMessage(),
            ], 400);
        }
    }
}
