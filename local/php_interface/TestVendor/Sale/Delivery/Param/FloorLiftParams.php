<?php

namespace TestVendor\Sale\Delivery\Param;

final class FloorLiftParams
{
    /**
     * @var int[]
     */
    public array $basketItemIds;
    public int $floor;
    public string $type;

    /**
     * @param int[] $basketItemIds
     */
    public function __construct(array $basketItemIds, int $floor, string $type)
    {
        $this->basketItemIds = $basketItemIds;
        $this->floor = $floor;
        $this->type = $type;
    }
}
