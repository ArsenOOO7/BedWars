<?php
namespace BW\arena;

use BW\utils\Colors;
use pocketmine\item\Item;
use pocketmine\item\ItemFactory;
use pocketmine\item\ItemIds;
use pocketmine\math\Vector3;
use pocketmine\Server;
use pocketmine\world\Position;
use pocketmine\world\World;

class Positions{

    /** @var Arena */
    private Arena $arena;

    /** @var Position */
    private Position $center;

    /** @var Position[] */
    private array $gold = [];
    /** @var Position[] */
    private array $silver = [];
    /** @var Position[] */
    private array $bronze = [];


    /**
     * Positions constructor.
     * @param Arena $arena
     * @param array $center
     * @param array $golds
     * @param array $silvers
     * @param array $bronzes
     */
    function __construct(Arena $arena, array $center = [], array $golds = [], array $silvers = [], array $bronzes = []){

        $this->arena = $arena;

        $world = Server::getInstance()->getWorldManager()->getDefaultWorld();

        $center[] = $world;
        $this->center = new Position(...$center);

        foreach($golds as $gold){

            $this->gold[] = new Position($gold[0], $gold[1], $gold[2], $world);

        }

        foreach($silvers as $silver){

            $this->silver[] = new Position($silver[0], $silver[1], $silver[2], $world);

        }

        foreach($bronzes as $team => $bronze){

            foreach($bronze as $n => $bronza)
                $this->bronze[Colors::convertTagToInt($team)][] = new Position($bronza[0], $bronza[1], $bronza[2], $world);

        }

    }


    function reload(){

        $world = Server::getInstance()->getWorldManager()->getDefaultWorld();
        $this->center->world = $world;

        $golds = $this->gold;
        $silvers = $this->silver;
        $bronzes = $this->bronze;

        $this->gold = [];
        $this->silver = [];
        $this->bronze = [];

        foreach($golds as $gold){

            $this->gold[] = new Position($gold->getFloorX(), $gold->getFloorY(), $gold->getFloorZ(), $world);

        }

        foreach($silvers as $silver){

            $this->silver[] = new Position($silver->getFloorX(), $silver->getFloorY(), $silver->getFloorZ(), $world);

        }

        foreach($bronzes as $team => $bronza){

            foreach($bronza as $bronze)
                $this->bronze[$team][] = new Position($bronze->getFloorX(), $bronze->getFloorY(), $bronze->getFloorZ(), $world);

        }

    }



    function spawnGold(){

        foreach($this->gold as $n => $gold){

            $gold->getWorld()->dropItem($gold, ItemFactory::getInstance()->get(ItemIds::GOLD_INGOT, 0, 1));

        }

    }



    function spawnSilver(){

        foreach($this->silver as $n => $silver){

            $silver->getWorld()->dropItem($silver, ItemFactory::getInstance()->get(ItemIds::IRON_INGOT, 0));

        }

    }



    function spawnBronze(){

        foreach($this->bronze as $team => $bronza){

            if($this->arena->getTeam($team)->isAlive())
                foreach($bronza as $n => $bronze)
                    $bronze->getWorld()->dropItem($bronze, ItemFactory::getInstance()->get(ItemIds::BRICK, 0));

        }

    }


    /**
     * @return Position
     */
    public function getCenter(): Position {

        return $this->center;

    }


}