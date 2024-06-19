<?php

namespace App;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

class BinListProvider implements BinProviderInterface
{
    private $client;

    private $binListUrl;

    public function __construct()
    {
        $this->client = new Client();
        $this->binListUrl = 'https://lookup.binlist.net/';
    }

    /**
     * Get country code from BIN
     *
     * @param string $bin BIN number
     * @return string|null Country code (2-letter ISO code) or null if not found
     */
    public function getCountryCodeFromBIN($bin): ?string
    {
        try {
            $response = $this->client->request('GET', $this->binListUrl . $bin, [
                'headers' => ['Accept' => 'application/json']
            ]);

            if ($response->getStatusCode() === 200) {
                $data = json_decode($response->getBody(), true);
                return $data['country']['alpha2'] ?? null;
            }
        } catch (GuzzleException $e) {
            error_log($e->getMessage());
        }

        return null;
    }
}
