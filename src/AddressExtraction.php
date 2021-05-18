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

namespace MichielGerritsen\ExtractAddressParts;

use MichielGerritsen\ExtractAddressParts\Exceptions\AddressExtractionError;
use MichielGerritsen\ExtractAddressParts\VO\AddressExtractionResult;

class AddressExtraction
{
    const STREET_SPLIT_NAME_FROM_NUMBER = '/^(?P<street>\d*[\p{L}\d \'\/\\\\\-]+)[,.\s]+(?P<housenumber>\d+)\s*(?P<addition>[\p{L} \d\-\/\'"\(\)]*)$/iu';
    const STREET_SPLIT_NUMBER_FROM_NAME = '/^(?P<housenumber>\d+)\s*(?P<street>[\p{L}\d \'\-\.]*)$/i';

    /**
     * @param string[] $address
     * @return AddressExtractionResult
     * @throws AddressExtractionError
     */
    public function process(array $address): AddressExtractionResult
    {
        $address = implode(' ', $address);

        $matched = preg_match(static::STREET_SPLIT_NAME_FROM_NUMBER, trim($address), $result);

        if (!$matched) {
            $result = $this->extractStreetFromNumber($address);
        }

        if (isset($result['error'])) {
            throw new AddressExtractionError($result['error']);
        }

        return new AddressExtractionResult(
            trim($result['street']),
            trim($result['housenumber'] ?? ''),
            trim($result['addition'])
        );
    }

    /**
     * @param string $street
     *
     * @return array
     * @throws AddressExtractionError
     */
    private function extractStreetFromNumber($street): array
    {
        $matched = preg_match(static::STREET_SPLIT_NUMBER_FROM_NAME, trim($street), $result);

        if (!$matched) {
            throw new AddressExtractionError(
                'Unable to extract the house number, could not find a number inside the street value'
            );
        }

        $result['addition'] = '';

        return $result;
    }
}
