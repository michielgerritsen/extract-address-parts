<?php
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
use MichielGerritsen\ExtractAddressParts\VO\AddressExtractionResult;
use PHPUnit\Framework\TestCase;

class AddressExtractionTest extends TestCase
{
    public function addressProvider()
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
                new AddressExtractionResult('Kerkstr', '8', '')
            ],
            'Abbreviated address with dot without space' => [
                ['Kerkstr.8'],
                new AddressExtractionResult('Kerkstr', '8', '')
            ],
        ];
    }

    /**
     * @dataProvider addressProvider
     */
    public function testExtract(array $address, AddressExtractionResult $expected)
    {
        $instance = new AddressExtraction();
        $result = $instance->process($address);

        $this->assertEquals($expected, $result);
    }
}
