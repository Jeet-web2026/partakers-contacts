<?php

namespace App\Livewire\Settings;

use Livewire\Component;

class Recharge extends Component
{
    public function proceedToPurchase()
    {
        $pricingUrl = config('services.pricing.url', env('PRICING_APP_URL', 'https://subscription.partakers.in'));
        return redirect()->away(rtrim($pricingUrl, '/') . '/order-summary/1016');
    }

    public function render()
    {
        return view('livewire.settings.recharge');
    }
}
