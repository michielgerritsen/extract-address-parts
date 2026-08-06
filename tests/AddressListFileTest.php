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

use MichielGerritsen\ExtractAddressParts\PrecisionAddressExtraction;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * The bundled lists use one line ending, but PHP_EOL depends on the platform the library runs on.
 * Splitting on PHP_EOL means a Windows runtime never matches a list written with unix line endings,
 * so the dictionary silently matches nothing at all.
 */
class AddressListFileTest extends TestCase
{
    /**
     * @return array<string, array{string}>
     */
    public static function lineEndingProvider(): array
    {
        return [
            'unix line endings' => [__DIR__ . '/fixtures/addresses-lf'],
            'windows line endings' => [__DIR__ . '/fixtures/addresses-crlf'],
        ];
    }

    #[DataProvider('lineEndingProvider')]
    public function testItReadsAStreetListRegardlessOfLineEndings(string $directory): void
    {
        $result = (new PrecisionAddressExtraction($directory))->process('Kerkstraat 95');

        $this->assertSame('Kerkstraat', $result->getStreet());
        $this->assertSame('95', $result->getHousenumber());
    }

    #[DataProvider('lineEndingProvider')]
    public function testItReadsEveryEntryNotJustTheFirst(string $directory): void
    {
        $result = (new PrecisionAddressExtraction($directory))->process('Kalverstraat 1');

        $this->assertSame('Kalverstraat', $result->getStreet());
    }
}
