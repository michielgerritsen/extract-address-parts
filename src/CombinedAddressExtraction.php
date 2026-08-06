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

class CombinedAddressExtraction implements AddressExtractionInterface
{
    /**
     * Strategies are attempted in the order they are given.
     */
    public function __construct(
        private readonly AddressExtractionInterface $preferred,
        private readonly AddressExtractionInterface $fallback
    ) {
    }

    public static function create(): self
    {
        return new self(new PrecisionAddressExtraction(), new AddressExtraction());
    }

    /**
     * @param string|string[] $address A full address, or a list of address lines
     * @throws AddressExtractionError
     */
    public function process(string|array $address): AddressExtractionResult
    {
        try {
            return $this->preferred->process($address);
        } catch (AddressExtractionError $exception) {
            // Ignore.
        }

        return $this->fallback->process($address);
    }
}
