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

namespace MichielGerritsen\ExtractAddressParts\VO;

use MichielGerritsen\ExtractAddressParts\ExtractionMethod;

class AddressExtractionResult
{
    private readonly string $addition;

    public function __construct(
        private readonly string $street,
        private readonly string $housenumber,
        string $addition,
        private readonly ExtractionMethod $extractionMethod
    ) {
        $this->addition = ltrim($addition, " \t-/,");
    }

    public function getExtractionMethod(): ExtractionMethod
    {
        return $this->extractionMethod;
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