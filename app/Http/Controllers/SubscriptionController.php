<?php

namespace App\Http\Controllers;

class SubscriptionController extends Controller
{
    /**
     * Pantalla que ve una empresa cuya suscripción venció, está suspendida,
     * cancelada o todavía no fue habilitada por el operador.
     */
    public function blocked()
    {
        $user = auth()->user();
        $company = $user->getCurrentCompany();
        $subscription = $company?->subscription;

        // Si la suscripción está al día no tiene sentido quedarse aquí.
        if ($subscription && $subscription->allowsWrite()) {
            return redirect()->route('dashboard');
        }

        return view('subscription.blocked', [
            'company'      => $company,
            'subscription' => $subscription,
        ]);
    }
}
