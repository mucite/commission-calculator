<?php

namespace App;

interface BinProviderInterface
{
    public function getCountryCodeFromBIN($bin): ?string;
}
