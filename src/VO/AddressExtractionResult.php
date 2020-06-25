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
    /**
     * @var string
     */
    private $street;

    /**
     * @var string
     */
    private $housenumber;

    /**
     * @var string
     */
    private $addition;

    public function __construct(
        string $street,
        string $housenumber,
        string $addition
    ) {
        $this->street = $street;
        $this->housenumber = $housenumber;
        $this->addition = $addition;
    }

    /**
     * @return string
     */
    public function getStreet(): string
    {
        return $this->street;
    }

    /**
     * @return string
     */
    public function getHousenumber(): string
    {
        return $this->housenumber;
    }

    /**
     * @return string
     */
    public function getAddition(): string
    {
        return $this->addition;
    }
}