<?php

use App\BinProviderInterface;
use App\CommissionCalculator;
use App\CurrencyConverterInterface;
use PHPUnit\Framework\TestCase;

class CommissionCalculatorTest extends TestCase
{
    private $binProviderMock;
    private $currencyConverterMock;
    private $transactionCommission;

    /**
     * @throws \PHPUnit\Framework\MockObject\Exception
     */
    protected function setUp(): void
    {
        $this->binProviderMock = $this->createMock(BinProviderInterface::class);
        $this->currencyConverterMock = $this->createMock(CurrencyConverterInterface::class);

        $this->transactionCommission = new CommissionCalculator($this->binProviderMock, $this->currencyConverterMock);
    }

    /**
     * @throws Exception
     */
    public function testProcessTransactions()
    {
        $inputFile = 'test_input.txt';
        $outputFile = 'test_commissions.csv';

        $transactions = [
            [
                'amount' => 100,
                'currency' => 'USD',
                'bin' => '45717360'
            ],
            [
                'amount' => 200,
                'currency' => 'EUR',
                'bin' => '516793'
            ]
        ];

        $this->binProviderMock->expects($this->exactly(2))
            ->method('getCountryCodeFromBIN')
            ->willReturnOnConsecutiveCalls('US', 'DE');

        $this->currencyConverterMock->expects($this->once())
            ->method('convertToEUR')
            ->with(100, 'USD')
            ->willReturn(85.0); // Assume 1 USD = 0.85 EUR

        file_put_contents($inputFile, json_encode($transactions[0]) . PHP_EOL . json_encode($transactions[1]) . PHP_EOL);

        $this->transactionCommission->processTransactions($inputFile, $outputFile);

        $output = array_map('str_getcsv', file($outputFile));
        $expected = [
            ['1.7'], // 2% commission on 85 EUR (converted from 100 USD)
            ['2']  // 1% commission on 200 EUR
        ];

        $this->assertEquals($expected, $output);

        unlink($inputFile);
        unlink($outputFile);
    }
}
