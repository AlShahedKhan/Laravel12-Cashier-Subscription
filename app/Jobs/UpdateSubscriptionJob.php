<?php

namespace App\Jobs;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class UpdateSubscriptionJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $user;
    protected $priceId;

    /**
     * Create a new job instance.
     */
    public function __construct(User $user, string $priceId)
    {
        $this->user = $user;
        $this->priceId = $priceId;
    }

    /**
     * Execute the job.
     */
    public function handle()
    {
        if (!$this->user->isSubscribed()) {
            throw new \Exception('User is not subscribed');
        }

        $subscription = $this->user->subscription('default');
        $subscription->swap($this->priceId);

        return [
            'id' => $subscription->stripe_id,
            'status' => $subscription->stripe_status,
        ];
    }
}
