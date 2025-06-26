<?php

namespace App\Jobs;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ResumeSubscriptionJob implements ShouldQueue
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

        // Check different scenarios for resuming
        if ($subscription->ended()) {
            $subscription->resume();
            return [
                'message' => 'Ended subscription resumed successfully',
                'subscription' => [
                    'id' => $subscription->stripe_id,
                    'status' => $subscription->stripe_status,
                    'ends_at' => $subscription->ends_at,
                ],
            ];
        }

        if ($subscription->cancel() && $subscription->onGracePeriod()) {
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

        if ($subscription->active() && !$subscription->cancel()) {
            throw new \Exception('Subscription is already active');
        }

        // Default case - try to resume anyway
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
