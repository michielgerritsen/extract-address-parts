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

trait NormalizesAddressInput
{
    /**
     * @param string|string[] $address
     */
    private function toInputString(string|array $address): string
    {
        return is_array($address) ? implode(' ', $address) : $address;
    }

    /**
     * A postbox has no street and no house number, so returning "Postbus" as a street name would be a
     * plausible looking result for an address that cannot be extracted at all.
     *
     * @throws AddressExtractionError
     */
    private function rejectPostbox(string $address): void
    {
        if (preg_match('/^\s*postbus\b/iu', $address)) {
            throw new AddressExtractionError('A postbox is not a street address');
        }
    }

    /**
     * Strips a trailing Dutch postcode and city. A comma allows a lowercase postcode, without one the
     * postcode must be uppercase so a street such as "Plein 1940 nr 2" is left alone.
     */
    private function removePostcodeAndCity(string $address): string
    {
        $pattern = '/(?:,\s*\d{4}\s?[A-Za-z]{2}|\s+\d{4}\s?[A-Z]{2})(?![A-Za-z])(?:\s+.*)?$/u';

        return trim(preg_replace($pattern, '', $address) ?? $address);
    }
}
