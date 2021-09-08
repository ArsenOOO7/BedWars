<?php
namespace BW\metadata;

use pocketmine\block\Block;

class Metadata{

    /** @var array  */
    private array $metadata = [];


    function drop(): void{

        $this->metadata = [];

    }



    /**
     * @param Block $block
     */
    function addData(Block $block){

        $data = $block->getPos()->getFloorZ().":".$block->getPos()->getFloorY().":".$block->getPos()->getFloorZ();
        $this->metadata[] = $data;

    }



    /**
     * @param Block $block
     * @return bool
     */
    function hasData(Block $block): bool{

        $data = $block->getPos()->getFloorZ().":".$block->getPos()->getFloorY().":".$block->getPos()->getFloorZ();
        return in_array($data, $this->metadata);

    }

}