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
     * @param string $inputFile Path to input file containing JSON transactions
     * @param string $outputFile Path to output file to write commissions
     * @throws Exception If there is an error opening files or processing transactions
     */
    public function processTransactions(string $inputFile, string $outputFile)
    {
        $transactions = file($inputFile, FILE_IGNORE_NEW_LINES);
        $output = fopen($outputFile, 'w');

        foreach ($transactions as $transaction) {
            $value = json_decode($transaction, true);

            $countryCode = $this->binProvider->getCountryCodeFromBIN($value['bin']);
            $commissionRate = in_array($countryCode, self::EU_COUNTRIES) ? self::EU_COMMISSION_RATE :
                self::NON_EU_COMMISSION_RATE;

            if ($value['currency'] != 'EUR') {
                $amountInEUR = $this->currencyConverter->convertToEUR($value['amount'], $value['currency']);
            } else {
                $amountInEUR = $value['amount'];
            }

            $commission = $amountInEUR * $commissionRate;
            $roundedCommission = ceil($commission * 100) / 100;

            fputcsv($output, [$roundedCommission]);
        }

        fclose($output);
    }

    const EU_COUNTRIES = [
        'AT', 'BE', 'BG', 'CY', 'CZ', 'DE', 'DK', 'EE', 'ES', 'FI', 'FR',
        'GR', 'HR', 'HU', 'IE', 'IT', 'LT', 'LU', 'LV', 'MT', 'NL', 'PL',
        'PT', 'RO', 'SE', 'SI', 'SK'
    ];

    const EU_COMMISSION_RATE = 0.01;
    const NON_EU_COMMISSION_RATE = 0.02;
}
