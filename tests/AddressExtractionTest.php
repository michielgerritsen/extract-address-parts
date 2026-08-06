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

use MichielGerritsen\ExtractAddressParts\AddressExtraction;
use MichielGerritsen\ExtractAddressParts\Exceptions\AddressExtractionError;
use MichielGerritsen\ExtractAddressParts\VO\AddressExtractionResult;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class AddressExtractionTest extends TestCase
{
    /**
     * @return array<string, array{string[], AddressExtractionResult}>
     */
    public static function addressProvider(): array
    {
        return [
            'regular address' => [
                ['Kerkstraat 95'],
                new AddressExtractionResult('Kerkstraat', '95', '')
            ],
            'address with extension' => [
                ['Kerkstraat 95A'],
                new AddressExtractionResult('Kerkstraat', '95', 'A')
            ],
            'address with extension and space' => [
                ['Kerkstraat 95 A'],
                new AddressExtractionResult('Kerkstraat', '95', 'A')
            ],
            'housenumber on second line' => [
                ['Kerkstraat', '95'],
                new AddressExtractionResult('Kerkstraat', '95', '')
            ],
            'housenumber on second line with extension' => [
                ['Kerkstraat', '95A'],
                new AddressExtractionResult('Kerkstraat', '95', 'A')
            ],
            'housenumber on second line with extension and space' => [
                ['Kerkstraat', '95 A'],
                new AddressExtractionResult('Kerkstraat', '95', 'A')
            ],
            'Streetname with spaces' => [
                ['Clare Lennarthof 7'],
                new AddressExtractionResult('Clare Lennarthof', '7', '')
            ],
            'Streetname with spaces on second line' => [
                ['Clare Lennarthof ', '7'],
                new AddressExtractionResult('Clare Lennarthof', '7', '')
            ],
            'Streetname with spaces and extension' => [
                ['Clare Lennarthof 7B'],
                new AddressExtractionResult('Clare Lennarthof', '7', 'B')
            ],
            'Streetname with spaces and extension and extra space' => [
                ['Clare Lennarthof 7 B'],
                new AddressExtractionResult('Clare Lennarthof', '7', 'B')
            ],
            'Streetname with numbers' => [
                ['7 Januaristraat 18'],
                new AddressExtractionResult('7 Januaristraat', '18', '')
            ],
            'Reversed' => [
                ['95 Kerkstraat'],
                new AddressExtractionResult('Kerkstraat', '95', '')
            ],
            'Abbreviated address with dot' => [
                ['Kerkstr. 8'],
                new AddressExtractionResult('Kerkstraat', '8', '')
            ],
            'Abbreviated address with dot without space' => [
                ['Kerkstr.8'],
                new AddressExtractionResult('Kerkstraat', '8', '')
            ],
            'Abbreviated prefix in address' => [
                ['Prof. De Grootstraat 12'],
                new AddressExtractionResult('Prof. De Grootstraat', '12', '')
            ],
            'Abbreviated prefix in abbreviated address' => [
                ['Prof. De Grootstr. 12'],
                new AddressExtractionResult('Prof. De Grootstraat', '12', '')
            ],
            'Abbreviated prefix in abbreviated address without space' => [
                ['Prof. De Grootstr.12'],
                new AddressExtractionResult('Prof. De Grootstraat', '12', '')
            ],
            'Address with special character (‘)' => [
                ["rue du plat d‘étain", '1'],
                new AddressExtractionResult("rue du plat d‘étain", '1', '')
            ],
            "Address with special character (')" => [
                ["rue du plat d'etain", '1'],
                new AddressExtractionResult("rue du plat d'etain", '1', '')
            ],
            'Address with special character (`)' => [
                ["rue du plat d`etain", '1'],
                new AddressExtractionResult("rue du plat d`etain", '1', '')
            ],
            'Address with special character (’)' => [
                ["rue du plat d’etain", '1'],
                new AddressExtractionResult("rue du plat d’etain", '1', '')
            ],
            'Extension separated by a dash' => [
                ['Kerkstraat 95-A'],
                new AddressExtractionResult('Kerkstraat', '95', 'A')
            ],
            'Floor separated by a dash' => [
                ['Kerkstraat 95-2'],
                new AddressExtractionResult('Kerkstraat', '95', '2')
            ],
            'Address followed by postcode and city' => [
                ['Kerkstraat 95, 1234 AB Amsterdam'],
                new AddressExtractionResult('Kerkstraat', '95', '')
            ],
            'Year in the street is not mistaken for a postcode' => [
                ['Plein 1940 nr 2'],
                new AddressExtractionResult('Plein 1940 nr', '2', '')
            ],
        ];
    }

    /**
     * @param string[] $address
     */
    #[DataProvider('addressProvider')]
    public function testExtract(array $address, AddressExtractionResult $expected): void
    {
        $instance = new AddressExtraction();
        $result = $instance->process($address);

        $this->assertEquals($expected, $result);
    }

    #[Test]
    public function itThrowsWhenTheAddressHasNoHouseNumber(): void
    {
        $instance = new AddressExtraction();

        $this->expectException(AddressExtractionError::class);

        $instance->process(['Kerkstraat']);
    }

    #[Test]
    public function itThrowsForAPostboxInsteadOfReturningItAsAStreet(): void
    {
        $instance = new AddressExtraction();

        $this->expectException(AddressExtractionError::class);

        $instance->process(['Postbus 1234']);
    }

    #[Test]
    public function itThrowsForALowercasePostbox(): void
    {
        $instance = new AddressExtraction();

        $this->expectException(AddressExtractionError::class);

        $instance->process(['postbus 1234']);
    }

    #[Test]
    public function itThrowsWhenTheAddressIsEmpty(): void
    {
        $instance = new AddressExtraction();

        $this->expectException(AddressExtractionError::class);

        $instance->process(['']);
    }
}
