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

namespace MichielGerritsen\ExtractAddressParts\VO;

class AddressExtractionResult
{
    public function __construct(
        private readonly string $street,
        private readonly string $housenumber,
        private readonly string $addition
    ) {
    }

    public function getStreet(): string
    {
        return $this->street;
    }

    public function getHousenumber(): string
    {
        return $this->housenumber;
    }

    public function getAddition(): string
    {
        return $this->addition;
    }
}