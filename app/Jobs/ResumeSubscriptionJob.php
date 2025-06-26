<?php

namespace App\Jobs;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Bus\Queueable;
use Illuminate\Http\JsonResponse;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class ResumeSubscriptionJob // Removed ShouldQueue interface
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $user;

    /**
     * Create a new job instance.
     */
    public function __construct(User $user)
    {
        $this->user = $user;
    }

    /**
     * Execute the job.
     */
    public function handle()
    {
        $subscription = $this->user->subscription('default');
        if (!$subscription) {
            throw new \Exception('No subscription found');
        }

        // Check if subscription is already active and not on grace period
        if ($subscription->active() && !$subscription->onGracePeriod()) {
            throw new \Exception('Subscription is already active');
        }

        // Check if subscription is on grace period (cancelled but not ended)
        if ($subscription->onGracePeriod()) {
            $subscription->resume();
            return [
                'message' => 'Cancelled subscription resumed successfully',
                'subscription' => [
                    'id' => $subscription->stripe_id,
                    'status' => $subscription->stripe_status,
                    'ends_at' => $subscription->ends_at,
                ],
            ];
        }

        // Check if subscription has ended
        if ($subscription->ended()) {
            throw new \Exception('Subscription has ended and cannot be resumed. Please create a new subscription.');
        }

        // Default case - try to resume
        $subscription->resume();
        return [
            'message' => 'Subscription resumed successfully',
            'subscription' => [
                'id' => $subscription->stripe_id,
                'status' => $subscription->stripe_status,
                'ends_at' => $subscription->ends_at,
            ],
        ];
    }


}
