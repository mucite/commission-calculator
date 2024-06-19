<?php

namespace App;

interface CurrencyConverterInterface
{
    public function convertToEUR($amount, $currency): ?float;
}
