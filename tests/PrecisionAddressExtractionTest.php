<?php

declare(strict_types=1);

/**
 *    ______            __             __
 *   / ____/___  ____  / /__________  / /
 *  / /   / __ \/ __ \/ __/ ___/ __ \/ /
 * / /___/ /_/ / / / / /_/ /  / /_/ / /
 * \______________/_/\__/_/   \____/_/
 *    /   |  / / /_
 *   / /| | / / __/
 *  / ___ |/ / /_
 * /_/ _|||_/\__/ __     __
 *    / __ \___  / /__  / /____
 *   / / / / _ \/ / _ \/ __/ _ \
 *  / /_/ /  __/ /  __/ /_/  __/
 * /_____/\___/_/\___/\__/\___/
 *
 */

namespace MichielGerritsen\ExtractAddressParts\Tests;

use MichielGerritsen\ExtractAddressParts\Exceptions\AddressExtractionError;
use MichielGerritsen\ExtractAddressParts\ExtractionMethod;
use MichielGerritsen\ExtractAddressParts\PrecisionAddressExtraction;
use MichielGerritsen\ExtractAddressParts\VO\AddressExtractionResult;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class PrecisionAddressExtractionTest extends TestCase
{
    /**
     * @return array<string, array{string[], AddressExtractionResult}>
     */
    public static function addressProvider(): array
    {
        return [
            'regular address' => [
                ['Kerkstraat 95'],
                new AddressExtractionResult('Kerkstraat', '95', '', ExtractionMethod::Dictionary)
            ],
            'address with extension' => [
                ['Kerkstraat 95A'],
                new AddressExtractionResult('Kerkstraat', '95', 'A', ExtractionMethod::Dictionary)
            ],
            'address with extension and space' => [
                ['Kerkstraat 95 A'],
                new AddressExtractionResult('Kerkstraat', '95', 'A', ExtractionMethod::Dictionary)
            ],
            'housenumber on second line' => [
                ['Kerkstraat', '95'],
                new AddressExtractionResult('Kerkstraat', '95', '', ExtractionMethod::Dictionary)
            ],
            'housenumber on second line with extension' => [
                ['Kerkstraat', '95A'],
                new AddressExtractionResult('Kerkstraat', '95', 'A', ExtractionMethod::Dictionary)
            ],
            'housenumber on second line with extension and space' => [
                ['Kerkstraat', '95 A'],
                new AddressExtractionResult('Kerkstraat', '95', 'A', ExtractionMethod::Dictionary)
            ],
            'Streetname with spaces' => [
                ['Clare Lennarthof 7'],
                new AddressExtractionResult('Clare Lennarthof', '7', '', ExtractionMethod::Dictionary)
            ],
            'Streetname with spaces on second line' => [
                ['Clare Lennarthof ', '7'],
                new AddressExtractionResult('Clare Lennarthof', '7', '', ExtractionMethod::Dictionary)
            ],
            'Streetname with spaces and extension' => [
                ['Clare Lennarthof 7B'],
                new AddressExtractionResult('Clare Lennarthof', '7', 'B', ExtractionMethod::Dictionary)
            ],
            'Streetname with spaces and extension and extra space' => [
                ['Clare Lennarthof 7 B'],
                new AddressExtractionResult('Clare Lennarthof', '7', 'B', ExtractionMethod::Dictionary)
            ],
            'Streetname with numbers' => [
                ['7 Januaristraat 18'],
                new AddressExtractionResult('7 Januaristraat', '18', '', ExtractionMethod::Dictionary)
            ],
            'Abbreviated address with dot' => [
                ['Kerkstr. 8'],
                new AddressExtractionResult('Kerkstraat', '8', '', ExtractionMethod::Dictionary)
            ],
            'Abbreviated prefix in address' => [
                ['Prof. C. Eijkmanstraat 12'],
                new AddressExtractionResult('Prof. C. Eijkmanstraat', '12', '', ExtractionMethod::Dictionary)
            ],
            'Abbreviated prefix in abbreviated address' => [
                ['Prof. C. Eijkmanstr. 12'],
                new AddressExtractionResult('Prof. C. Eijkmanstraat', '12', '', ExtractionMethod::Dictionary)
            ],
            'Lowercase tussenvoegsel is preserved' => [
                ['Laan van Meerdervoort 1000'],
                new AddressExtractionResult('Laan van Meerdervoort', '1000', '', ExtractionMethod::Dictionary)
            ],
            'Street starting with an apostrophe keeps its casing' => [
                ["'s-Gravendijkwal 12"],
                new AddressExtractionResult("'s-Gravendijkwal", '12', '', ExtractionMethod::Dictionary)
            ],
            'Uppercase input keeps its casing' => [
                ['KERKSTRAAT 95'],
                new AddressExtractionResult('KERKSTRAAT', '95', '', ExtractionMethod::Dictionary)
            ],
            'Extension separated by a dash' => [
                ['Kerkstraat 95-A'],
                new AddressExtractionResult('Kerkstraat', '95', 'A', ExtractionMethod::Dictionary)
            ],
            'Address followed by postcode and city' => [
                ['Kerkstraat 95, 1234 AB Amsterdam'],
                new AddressExtractionResult('Kerkstraat', '95', '', ExtractionMethod::Dictionary)
            ],
            'Year in the street is not mistaken for a postcode' => [
                ['Plein 1940 nr 2'],
                new AddressExtractionResult('Plein 1940', '2', '', ExtractionMethod::Dictionary)
            ],
        ];
    }

    /**
     * @param string[] $address
     */
    #[DataProvider('addressProvider')]
    public function testExtract(array $address, AddressExtractionResult $expected): void
    {
        $instance = new PrecisionAddressExtraction();
        $result = $instance->process($address);

        $this->assertEquals($expected, $result);
    }

    #[Test]
    public function itThrowsWhenTheStreetIsNotInTheDictionary(): void
    {
        $instance = new PrecisionAddressExtraction();

        $this->expectException(AddressExtractionError::class);

        $instance->process(['Qwertyuiopasdf 12']);
    }

    #[Test]
    public function itThrowsForAPostboxInsteadOfReturningItAsAStreet(): void
    {
        $instance = new PrecisionAddressExtraction();

        $this->expectException(AddressExtractionError::class);

        $instance->process(['Postbus 1234']);
    }

    #[Test]
    public function itThrowsWhenTheAddressHasNoHouseNumber(): void
    {
        $instance = new PrecisionAddressExtraction();

        $this->expectException(AddressExtractionError::class);

        $instance->process(['Kerkstraat']);
    }
}
