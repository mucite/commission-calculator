<?php

namespace App;

use Exception;

class CommissionCalculator
{
    private $binProvider;
    private $currencyConverter;

    public function __construct(BinProviderInterface $binProvider, CurrencyConverterInterface $currencyConverter)
    {
        $this->binProvider = $binProvider;
        $this->currencyConverter = $currencyConverter;
    }

    /**
     * Process transactions from input file and write commissions to output file.
     *
     * @param string $inputFile  Path to input file containing JSON transactions
     * @param string $outputFile Path to output file to write commissions
     * @throws Exception If there is an error opening files or processing transactions
     */
    public function processTransactions(string $inputFile, string $outputFile)
    {
        if (!file_exists($inputFile)) {
            throw new Exception("Input file not found: $inputFile");
        }

        if (!file_exists($outputFile)) {
            if (!touch($outputFile)) {
                throw new Exception("Output file could not be created: $outputFile");
            }
        }

        $inputHandle = fopen($inputFile, 'r');
        $outputHandle = fopen($outputFile, 'w');

        if (!$inputHandle || !$outputHandle) {
            throw new Exception("Failed to open input or output file");
        }

        while (($line = fgets($inputHandle)) !== false) {
            $transaction = json_decode($line, true);
            if (!isset($transaction['amount'], $transaction['currency'], $transaction['bin'])) {
                throw new Exception("Invalid transaction data: $line");
            }

            $bin = $transaction['bin'];
            $amount = (float) $transaction['amount'];
            $currency = $transaction['currency'];

            $countryCode = $this->binProvider->getCountryCodeFromBIN($bin);
            if (!$countryCode) {
                throw new Exception("Invalid BIN: $bin");
            }

            $amountInEUR = $currency === 'EUR' ? $amount : $this->currencyConverter->convertToEUR($amount, $currency);
            if ($amountInEUR === null) {
                throw new Exception("Currency conversion failed: $amount $currency");
            }

            $commission = $this->calculateCommission($amountInEUR, $countryCode);
            $commission = ceil($commission * 100) / 100;
            fputcsv($outputHandle, [$commission]);
        }

        fclose($inputHandle);
        fclose($outputHandle);
    }

    /**
     * Calculate commission based on amount and country code.
     *
     * @param float $amount     Amount in EUR
     * @param string $countryCode Country code derived from BIN
     * @return float Commission amount
     */
    private function calculateCommission(float $amount, string $countryCode)
    {
        $rate = in_array($countryCode, self::EU_COUNTRIES) ? self::EU_COMMISSION_RATE : self::NON_EU_COMMISSION_RATE;
        return ceil($amount * $rate * 100) / 100;
    }

    const EU_COUNTRIES = [
        'AT', 'BE', 'BG', 'CY', 'CZ', 'DE', 'DK', 'EE', 'ES', 'FI', 'FR',
        'GR', 'HR', 'HU', 'IE', 'IT', 'LT', 'LU', 'LV', 'MT', 'NL', 'PL',
        'PT', 'RO', 'SE', 'SI', 'SK'
    ];

    const EU_COMMISSION_RATE = 0.01;
    const NON_EU_COMMISSION_RATE = 0.02;
}
