<?php

require 'vendor/autoload.php';

use App\CommissionCalculator;
use App\BinListProvider;
use App\ExchangeRatesConverter;

if ($argc !== 2) {
    echo "Usage: php app.php input.txt\n";
    exit(1);
}

$inputFile = $argv[1];
$outputFile = 'commissions.csv';

$binProvider = new BinListProvider();
$currencyConverter = new ExchangeRatesConverter();

$calculator = new CommissionCalculator($binProvider, $currencyConverter);
try {
    $calculator->processTransactions($inputFile, $outputFile);
} catch (Exception $e) {
    error_log($e->getMessage());
}

echo "Commissions calculated and saved to {$outputFile}\n";

