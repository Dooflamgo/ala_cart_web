<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Lunar\Models\Country;
use Lunar\Models\Currency;
use Lunar\Models\Price;
use Lunar\Shipping\Models\ShippingMethod;
use Lunar\Shipping\Models\ShippingRate;
use Lunar\Shipping\Models\ShippingZone;

class ShippingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $currency = Currency::getDefault();

        // Standard Shipping for UK
        $standardShipping = ShippingMethod::create([
            'name' => 'Standard Shipping',
            'code' => 'STNDRD',
            'enabled' => true,
            'driver' => 'ship-by',
            'data' => [
                'charge_by' => 'cart_total',
            ],
        ]);

        $ukShippingZone = ShippingZone::create([
            'name' => 'UK',
            'type' => 'countries',
        ]);

        $ukShippingRate = ShippingRate::create([
            'shipping_zone_id' => $ukShippingZone->id,
            'shipping_method_id' => $standardShipping->id,
            'enabled' => true,
        ]);

        $ukShippingZone->countries()->sync(
            Country::where('iso3', '=', 'GBR')->first()->id,
        );

        Price::create([
            'priceable_type' => (new ShippingRate)->getMorphClass(),
            'priceable_id' => $ukShippingRate->id,
            'price' => 1000,
            'min_quantity' => 1,
            'currency_id' => $currency->id,
        ]);

        // Free shipping on £100 or over orders
        Price::create([
            'priceable_type' => (new ShippingRate)->getMorphClass(),
            'priceable_id' => $ukShippingRate->id,
            'price' => 0,
            'min_quantity' => 10000,
            'currency_id' => $currency->id,
        ]);

        // Shipping for 9 European countries
        $euroShipping = ShippingMethod::create([
            'name' => 'Europe Delivery',
            'code' => 'EURO',
            'enabled' => true,
            'driver' => 'ship-by',
        ]);

        $euroShippingZone = ShippingZone::create([
            'name' => 'Europe',
            'type' => 'countries',
        ]);

        $euroShippingRate = ShippingRate::create([
            'shipping_zone_id' => $euroShippingZone->id,
            'shipping_method_id' => $euroShipping->id,
            'enabled' => true,
        ]);

        $euroShippingZone->countries()->sync(
            Country::whereIn('iso3', [
                'AUT', // Austria
                'BEL', // Belgium
                'BGR', // Bulgaria
                'HRV', // Croatia
                'CYP', // Cyprus
                'CZE', // Czech Republic
                'DNK', // Denmark
                'EST', // Estonia
                'FIN', // Finland
            ])->pluck('id'),
        );

        Price::create([
            'priceable_type' => (new ShippingRate)->getMorphClass(),
            'priceable_id' => $euroShippingRate->id,
            'price' => 2000,
            'min_quantity' => 1,
            'currency_id' => $currency->id,
        ]);
    }
}