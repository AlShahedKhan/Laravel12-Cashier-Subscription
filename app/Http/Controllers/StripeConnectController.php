<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Stripe\Stripe;
use Stripe\Account;
use Stripe\AccountLink;

class StripeConnectController extends Controller
{
    public function createAccountLink(Request $request)
    {
        Stripe::setApiKey(config('services.stripe.secret'));
        $user = Auth::user();

        // If the user does not have a Stripe Connect account ID, create one
        if (!$user->stripe_connect_id) {
            $account = Account::create([
                'type' => 'express',
                'email' => $user->email,
                'capabilities' => [
                    'card_payments' => ['requested' => true],
                    'transfers' => ['requested' => true],
                ],
            ]);
            $user->stripe_connect_id = $account->id;
            $user->save();
        }

        // Create the account link
        $accountLink = AccountLink::create([
            'account' => $user->stripe_connect_id,
            'refresh_url' => route('stripe.connect.refresh'), // You need to define this route
            'return_url' => route('stripe.connect.return'),   // And this one
            'type' => 'account_onboarding',
        ]);

        return response()->json([
            'success' => true,
            'url' => $accountLink->url,
        ]);
    }

    public function handleReturn(Request $request)
    {
        // Handle the user returning from Stripe onboarding
        // You can redirect them to their dashboard or a "success" page
        return redirect('/dashboard')->with('success', 'Payout account set up successfully!');
    }

    public function handleRefresh(Request $request)
    {
        // Handle the link expiring and needing to be refreshed
        return redirect()->route('stripe.connect.create');
    }
}
