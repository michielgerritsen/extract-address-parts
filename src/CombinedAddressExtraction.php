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

class CombinedAddressExtraction implements AddressExtractionInterface
{
    /**
     * @var AddressExtraction
     */
    private $addressExtraction;

    /**
     * @var PrecisionAddressExtraction
     */
    private $precisionAddressExtraction;

    public function __construct(
        AddressExtraction $addressExtraction,
        PrecisionAddressExtraction $precisionAddressExtraction
    ) {
        $this->addressExtraction = $addressExtraction;
        $this->precisionAddressExtraction = $precisionAddressExtraction;
    }

    public function process(array $address): AddressExtractionResult
    {
        try {
            return $this->precisionAddressExtraction->process($address);
        } catch (AddressExtractionError $exception) {
            // Ignore.
        }

        return $this->addressExtraction->process($address);
    }
}
