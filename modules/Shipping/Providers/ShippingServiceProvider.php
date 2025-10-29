<?php

namespace Modules\Shipping\Providers;

use Modules\Shipping\Method;
use Illuminate\Support\ServiceProvider;
use Modules\Shipping\Facades\ShippingMethod;

class ShippingServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot(): void
    {
        if (!config('app.installed')) {
            return;
        }

        $this->registerFreeShipping();
        $this->registerLocalPickup();
        $this->registerFlatRate();
    }


    private function registerFreeShipping()
    {
        if (!setting('free_shipping_enabled')) {
            return;
        }

        ShippingMethod::register('free_shipping', function () {
            return new Method('free_shipping', setting('free_shipping_label'), 0);
        });
    }


    private function registerLocalPickup()
    {
        if (!setting('local_pickup_enabled')) {
            return;
        }

        ShippingMethod::register('local_pickup', function () {
            return new Method('local_pickup', setting('local_pickup_label'), setting('local_pickup_cost') ?? 0);
        });
    }


    private function registerFlatRate()
    {
        $flatRates = [
            'flat_rate_inside_dhaka_regular' => [
                'enabled' => true,
                'label'   => 'Inside Dhaka City',
                'cost'    => 60,
            ],
            'flat_rate_sub_urban' => [
                'enabled' => true,
                'label'   => 'Inside Dhaka Sub-Urban (Keraniganj, Nawabganj, Dohar, Savar, Dhamrai)',
                'cost'    => 80,
            ],
            'flat_rate_outside_dhaka' => [
                'enabled' => true,
                'label'   => 'Outside Dhaka',
                'cost'    => 120,
            ],
        ];

        foreach ($flatRates as $key => $rate) {
            if ($rate['enabled']) {
                ShippingMethod::register($key, function () use ($key, $rate) {
                    return new Method($key, $rate['label'], $rate['cost']);
                });
            }
        }
    }
}
