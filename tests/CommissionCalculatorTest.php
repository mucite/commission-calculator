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
     * @throws \PHPUnit\Framework\MockObject\Exception
     * @throws Exception
     */
    public function testProcessTransactions()
    {
        $this->binProviderMock->expects($this->exactly(5))
            ->method('getCountryCodeFromBIN')
            ->willReturnMap([['45717360'], ['516793'], ['45417360'], ['41417360'], ['4745030']])
            ->willReturnOnConsecutiveCalls('US', 'DE', 'JP', 'US', 'GB');

        $this->currencyConverterMock->expects($this->exactly(4))
            ->method('convertToEUR')
            ->willReturnMap([[50.00, 'USD'], [10000.00, 'JPY'], [130.00, 'USD'], [2000.00, 'GBP']])
            ->willReturnOnConsecutiveCalls(42.0, 75.0, 110.5, 2320.0);

        $inputFile = 'test_input.txt';
        $transactions = [
            '{"bin":"45717360","amount":100.00,"currency":"EUR"}',
            '{"bin":"516793","amount":50.00,"currency":"USD"}',
            '{"bin":"45417360","amount":10000.00,"currency":"JPY"}',
            '{"bin":"41417360","amount":130.00,"currency":"USD"}',
            '{"bin":"4745030","amount":2000.00,"currency":"GBP"}'
        ];
        file_put_contents($inputFile, implode(PHP_EOL, $transactions));

        // Define expected output (rounded to nearest cent)
        $outputFile = 'test_commissions.csv';
        $expected = [
            ['2'],
            ['0.42'],
            ['1.5'],
            ['2.21'],
            ['46.4']
        ];

        // Call the method under test
        $this->transactionCommission->processTransactions($inputFile, $outputFile);

        // Read the output file
        $output = file($outputFile, FILE_IGNORE_NEW_LINES);

        // Assert the expected output matches the actual output
        $this->assertEquals($expected, array_map('str_getcsv', $output));

        // Clean up
        unlink($inputFile);
        unlink($outputFile);
    }

}
