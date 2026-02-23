<?php

namespace App\Service\CryptoApi;

use App\Entity\Currency;

interface CryptoApiProviderInterface
{
    public function getCryptoPrice(Currency $currency, string $pricingCurrencySymbol = "USD"): float;
}