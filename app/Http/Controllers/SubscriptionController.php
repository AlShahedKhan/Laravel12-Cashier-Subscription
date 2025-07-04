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
            // Use dispatchSync with non-queued job
            $result = ResumeSubscriptionJob::dispatchSync($user);

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

    public function getSubscriptionDetails(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user->isSubscribed()) {
            return response()->json([
                'subscribed' => false,
                'tier' => 'none',
            ]);
        }

        $subscription = $user->subscription('default');

        return response()->json([
            'subscribed' => true,
            'tier' => $user->getSubscriptionTier(),
            'subscription' => [
                'id' => $subscription->stripe_id,
                'status' => $subscription->stripe_status,
                'current_period_start' => $subscription->created_at,
                'current_period_end' => $subscription->ends_at,
                'cancelled' => $subscription->ended(),
                'on_grace_period' => $subscription->onGracePeriod(),
            ],
        ]);
    }

    public function getPaymentMethods(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user->hasStripeId()) {
            return response()->json(['payment_methods' => []]);
        }

        $paymentMethods = $user->paymentMethods()->map(function ($paymentMethod) {
            return [
                'id' => $paymentMethod->id,
                'type' => $paymentMethod->type,
                'card' => $paymentMethod->card ? [
                    'brand' => $paymentMethod->card->brand,
                    'last4' => $paymentMethod->card->last4,
                    'exp_month' => $paymentMethod->card->exp_month,
                    'exp_year' => $paymentMethod->card->exp_year,
                ] : null,
            ];
        });

        return response()->json([
            'payment_methods' => $paymentMethods,
            'default_payment_method' => $user->defaultPaymentMethod()?->id,
        ]);
    }

    public function getPrices(): JsonResponse
    {
        return response()->json([
            'prices' => [
                'basic_monthly' => [
                    'id' => 'basic_monthly',
                    'stripe_id' => self::PRICE_IDS['basic_monthly'],
                    'name' => 'Basic Monthly',
                    'interval' => 'month',
                    'tier' => 'basic',
                ],
                'basic_yearly' => [
                    'id' => 'basic_yearly',
                    'stripe_id' => self::PRICE_IDS['basic_yearly'],
                    'name' => 'Basic Yearly',
                    'interval' => 'year',
                    'tier' => 'basic',
                ],
                'premium_monthly' => [
                    'id' => 'premium_monthly',
                    'stripe_id' => self::PRICE_IDS['premium_monthly'],
                    'name' => 'Premium Monthly',
                    'interval' => 'month',
                    'tier' => 'premium',
                ],
                'premium_yearly' => [
                    'id' => 'premium_yearly',
                    'stripe_id' => self::PRICE_IDS['premium_yearly'],
                    'name' => 'Premium Yearly',
                    'interval' => 'year',
                    'tier' => 'premium',
                ],
            ],
        ]);
    }

    public function pauseSubscription(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user->isSubscribed()) {
            return response()->json([
                'success' => false,
                'message' => 'User is not subscribed',
            ], 400);
        }

        try {
            $subscription = $user->subscription('default');

            // Check if subscription is already cancelled/paused
            if ($subscription->ended()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Subscription is already cancelled',
                ], 400);
            }

            // Cancel the subscription (this will pause it at the end of the current period)
            $subscription->cancel();

            return response()->json([
                'success' => true,
                'message' => 'Subscription paused successfully. It will remain active until the end of the current billing period.',
                'ends_at' => $subscription->ends_at->toISOString(),
                'on_grace_period' => $subscription->onGracePeriod(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }
}
