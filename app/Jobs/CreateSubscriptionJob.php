<?php

namespace App\Jobs;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class CreateSubscriptionJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $user;
    protected $priceId;
    protected $paymentMethod;

    /**
     * Create a new job instance.
     */
    public function __construct(User $user, string $priceId, string $paymentMethod)
    {
        $this->user = $user;
        $this->priceId = $priceId;
        $this->paymentMethod = $paymentMethod;
    }

    /**
     * Execute the job.
     */
    public function handle()
    {
        // Create or get customer
        if (!$this->user->hasStripeId()) {
            $this->user->createAsStripeCustomer();
        }

        // Add payment method
        $this->user->addPaymentMethod($this->paymentMethod);
        $this->user->updateDefaultPaymentMethod($this->paymentMethod);

        // Create subscription
        $subscription = $this->user->newSubscription('default', $this->priceId)->create($this->paymentMethod);

        return [
            'id' => $subscription->stripe_id,
            'status' => $subscription->stripe_status,
            'current_period_end' => $subscription->ends_at,
        ];
    }
}
