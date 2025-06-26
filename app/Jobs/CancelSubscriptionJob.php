<?php

// Job without ShouldQueue
namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class CancelSubscriptionJob
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $userId;

    public function __construct($userId)
    {
        $this->userId = $userId;
    }

    public function handle()
    {
        $user = \App\Models\User::find($this->userId);
        if (!$user || !$user->isSubscribed()) {
            throw new \Exception('User is not subscribed');
        }

        $subscription = $user->subscription('default');
        $subscription->cancel();

        return [
            'ends_at' => $subscription->ends_at,
        ];
    }
}


