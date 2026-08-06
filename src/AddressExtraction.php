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

class AddressExtraction implements AddressExtractionInterface
{
    public const STREET_SPLIT_NAME_FROM_NUMBER = '/^(?P<street>\d*[\p{L}\d \`\'\‘\’\/\\\\\-\.]+[,.\s])+(?P<housenumber>\d+)\s*(?P<addition>[\p{L} \d\-\/\`\'"\(\)]*)$/iu';
    public const STREET_SPLIT_NUMBER_FROM_NAME = '/^(?P<housenumber>\d+)\s*(?P<street>[\p{L}\d \`\'\-\.]*)$/i';

    /**
     * @param string[] $address A list of address lines
     * @throws AddressExtractionError
     */
    public function process(array $address): AddressExtractionResult
    {
        $address = implode(' ', $address);

        $matched = preg_match(self::STREET_SPLIT_NAME_FROM_NUMBER, trim($address), $result);

        if (!$matched) {
            $result = $this->extractStreetFromNumber($address);
        }

        if (isset($result['error'])) {
            throw new AddressExtractionError($result['error']);
        }

        return new AddressExtractionResult(
            $this->replaceAbbreviations(trim($result['street'])),
            trim($result['housenumber'] ?? ''),
            trim($result['addition'])
        );
    }

    private function replaceAbbreviations(string $street): string
    {
        return preg_replace('/(st\.|str\.)$/', 'straat', $street) ?? $street;
    }

    /**
     * @return array<int|string, string>
     * @throws AddressExtractionError
     */
    private function extractStreetFromNumber(string $street): array
    {
        $matched = preg_match(self::STREET_SPLIT_NUMBER_FROM_NAME, trim($street), $result);

        if (!$matched) {
            throw new AddressExtractionError(
                'Unable to extract the house number, could not find a number inside the street value'
            );
        }

        $result['addition'] = '';

        return $result;
    }
}
