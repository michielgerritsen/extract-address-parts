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
use MichielGerritsen\ExtractAddressParts\AddressExtractionInterface;
use MichielGerritsen\ExtractAddressParts\CombinedAddressExtraction;
use MichielGerritsen\ExtractAddressParts\PrecisionAddressExtraction;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class StringInputTest extends TestCase
{
    /**
     * @return array<string, array{AddressExtractionInterface}>
     */
    public static function extractorProvider(): array
    {
        return [
            'regex' => [new AddressExtraction()],
            'precision' => [new PrecisionAddressExtraction()],
            'combined' => [CombinedAddressExtraction::create()],
        ];
    }

    #[DataProvider('extractorProvider')]
    public function testItAcceptsAPlainString(AddressExtractionInterface $extractor): void
    {
        $result = $extractor->process('Kerkstraat 95A');

        $this->assertSame('Kerkstraat', $result->getStreet());
        $this->assertSame('95', $result->getHousenumber());
        $this->assertSame('A', $result->getAddition());
    }

    #[DataProvider('extractorProvider')]
    public function testItGivesTheSameResultForAStringAndASingleElementArray(
        AddressExtractionInterface $extractor
    ): void {
        $fromString = $extractor->process('Clare Lennarthof 7B');
        $fromArray = $extractor->process(['Clare Lennarthof 7B']);

        $this->assertEquals($fromArray, $fromString);
    }

    #[Test]
    public function itStillAcceptsMultipleAddressLines(): void
    {
        $result = CombinedAddressExtraction::create()->process(['Kerkstraat', '95A']);

        $this->assertSame('Kerkstraat', $result->getStreet());
        $this->assertSame('95', $result->getHousenumber());
        $this->assertSame('A', $result->getAddition());
    }
}
