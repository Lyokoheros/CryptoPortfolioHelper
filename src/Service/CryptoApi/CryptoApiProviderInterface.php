<?php

namespace App\Service\CryptoApi;


interface CryptoApiProviderInterface
{
    public function getCryptoPrice(string $symbol, string $pricingCurrencySymbol = "USD"): float;
}