<?php

namespace App;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

class ExchangeRatesConverter implements CurrencyConverterInterface
{
    private $client;
    private $exchangeApiUrl;

    public function __construct()
    {
        $this->client = new Client();
        $this->exchangeApiUrl = 'https://api.exchangeratesapi.io/v1/latest';
    }

    /**
     * Convert amount to EUR using an external API.
     *
     * @param float $amount   Amount to convert
     * @param string $currency Currency code
     * @return float|null Converted amount in EUR or null if conversion fails
     */
    public function convertToEUR($amount, $currency): ?float
    {
        if ($currency === 'EUR') {
            return $amount;
        }

        try {
            $response = $this->client->request('GET', $this->exchangeApiUrl . '&base=' . $currency);

            if ($response->getStatusCode() === 200) {
                $data = json_decode($response->getBody(), true);
                $rate = $data['rates']['EUR'] ?? null;
                if ($rate) {
                    return $amount * $rate;
                }
            }
        } catch (GuzzleException $e) {
            error_log($e->getMessage());
        }

        return null;
    }
}

