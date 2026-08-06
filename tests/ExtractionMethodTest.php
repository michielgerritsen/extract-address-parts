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
use MichielGerritsen\ExtractAddressParts\CombinedAddressExtraction;
use MichielGerritsen\ExtractAddressParts\ExtractionMethod;
use MichielGerritsen\ExtractAddressParts\PrecisionAddressExtraction;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class ExtractionMethodTest extends TestCase
{
    #[Test]
    public function itReportsAPatternMatchWhenOnlyTheRegexWasUsed(): void
    {
        $result = (new AddressExtraction())->process('Kerkstraat 95A');

        $this->assertSame(ExtractionMethod::Pattern, $result->getExtractionMethod());
    }

    #[Test]
    public function itReportsADictionaryMatchWhenTheStreetWasVerified(): void
    {
        $result = (new PrecisionAddressExtraction())->process('Kerkstraat 95A');

        $this->assertSame(ExtractionMethod::Dictionary, $result->getExtractionMethod());
    }

    #[Test]
    public function itReportsADictionaryMatchWhenTheCombinedStrategyVerifiesTheStreet(): void
    {
        $result = CombinedAddressExtraction::create()->process('Kerkstraat 95A');

        $this->assertSame(ExtractionMethod::Dictionary, $result->getExtractionMethod());
    }

    #[Test]
    public function itReportsAPatternMatchWhenTheCombinedStrategyFallsBack(): void
    {
        $result = CombinedAddressExtraction::create()->process('Qwertyuiopasdf 12');

        $this->assertSame(ExtractionMethod::Pattern, $result->getExtractionMethod());
    }

    #[Test]
    public function itDistinguishesAVerifiedStreetFromAGuess(): void
    {
        $combined = CombinedAddressExtraction::create();

        $verified = $combined->process('Kerkstraat 95');
        $guessed = $combined->process('Qwertyuiopasdf 12');

        $this->assertNotSame($verified->getExtractionMethod(), $guessed->getExtractionMethod());
    }
}
