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

namespace MichielGerritsen\ExtractAddressParts;

use MichielGerritsen\ExtractAddressParts\Exceptions\AddressExtractionError;
use MichielGerritsen\ExtractAddressParts\VO\AddressExtractionResult;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class CombinedAddressExtractionTest extends TestCase
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
                ['Prof. C. Eijkmanstr.12'],
                new AddressExtractionResult('Prof. C. Eijkmanstraat', '12', '')
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
            'Street casing does not depend on the matching strategy' => [
                ['Laan van Meerdervoort 1000'],
                new AddressExtractionResult('Laan van Meerdervoort', '1000', '')
            ],
            'Extension separated by a dash' => [
                ['Kerkstraat 95-A'],
                new AddressExtractionResult('Kerkstraat', '95', 'A')
            ],
            'Address followed by postcode and city' => [
                ['Kerkstraat 95, 1234 AB Amsterdam'],
                new AddressExtractionResult('Kerkstraat', '95', '')
            ],
        ];
    }

    /**
     * @param string[] $address
     */
    #[DataProvider('addressProvider')]
    public function testExtract(array $address, AddressExtractionResult $expected): void
    {
        $instance = CombinedAddressExtraction::create();

        $result = $instance->process($address);

        $this->assertSame($expected->getStreet(), $result->getStreet());
        $this->assertSame($expected->getHousenumber(), $result->getHousenumber());
        $this->assertSame($expected->getAddition(), $result->getAddition());
    }

    #[Test]
    public function itCanBeCreatedWithoutWiringTheStrategiesByHand(): void
    {
        $instance = CombinedAddressExtraction::create();

        $result = $instance->process(['Kerkstraat 95A']);

        $this->assertEquals(
            new AddressExtractionResult('Kerkstraat', '95', 'A', ExtractionMethod::Dictionary),
            $result
        );
    }

    #[Test]
    public function itAcceptsAnyImplementationOfTheExtractionInterface(): void
    {
        $preferred = $this->createStub(AddressExtractionInterface::class);
        $preferred->method('process')->willThrowException(new AddressExtractionError('nope'));

        $fallback = $this->createStub(AddressExtractionInterface::class);
        $fallback->method('process')->willReturn(new AddressExtractionResult('Fallbackstraat', '1', ''));

        $instance = new CombinedAddressExtraction($preferred, $fallback);

        $this->assertEquals(
            new AddressExtractionResult('Fallbackstraat', '1', ''),
            $instance->process(['Kerkstraat 95'])
        );
    }

    #[Test]
    public function itThrowsForAPostboxInsteadOfReturningItAsAStreet(): void
    {
        $instance = CombinedAddressExtraction::create();

        $this->expectException(AddressExtractionError::class);

        $instance->process(['Postbus 1234']);
    }
}
