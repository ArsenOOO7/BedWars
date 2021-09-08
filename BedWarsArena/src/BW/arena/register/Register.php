<?php
namespace BW\arena\register;

use BW\arena\Arena;
use BW\utils\Colors;
use BW\Loader;
use PHPUnit\Util\Color;
use pocketmine\utils\Config;
use pocketmine\world\Position;

class Register{

    /** @var String */
    public string $name;
    /** @var String */
    public string $world;

    /** @var Position */
    public Position $center;

    /** @var int */
    public int $slots;
    /** @var int */
    public int $count_teams;

    /** @var array */
    public array $gold = [];
    /** @var array */
    public array $silver = [];
    /** @var array */
    public array $bronze = [];


    /** @var array */
    public array $teams = [];



    function saveAll(Loader $loader) {

        $arena_format = [];
        $arena_format["arena"] = $this->name;
        $arena_format["world"] = $this->world;
        $arena_format["slots"] = $this->slots;
        $arena_format["count_teams"] = $this->count_teams;

        $arena = new Config($loader->getDataFolder()."arena.json", Config::JSON);
        $arena->setAll($arena_format);
        $arena->save();


        $teams_format = [];
        foreach($this->teams as $tag => $team){

            $teams_format[Colors::convertTagToInt($tag)] = $team;

        }

        $teams = new Config($loader->getDataFolder()."teams.json", Config::JSON);
        $teams->setAll($teams_format);
        $teams->save();

        $positions_format["center"] = [$this->center->x, $this->center->y, $this->center->z];
        $positions_format["gold"] = $this->gold;
        $positions_format["silver"] = $this->silver;
        $positions_format["bronze"] = $this->bronze;

        $positions = new Config($loader->getDataFolder()."positions.json", Config::JSON);
        $positions->setAll($positions_format);
        $positions->save();

        $loader->arena = new Arena($loader, $this->name, $this->world, $this->slots, $this->count_teams, $positions_format, $teams_format);

    }



    /**
     * @param string $tag
     * @param Position $spawn
     */
    function setSpawn(string $tag, Position $spawn){

        $this->teams[$tag] = ["x" => $spawn->x, "y" => $spawn->y, "z" => $spawn->z];

    }


    /**
     * @param string $team
     * @param string $resource
     * @param Position $position
     * @return bool
     */

    function setResource(string $resource, Position $position, string $team = "") : bool{

        if($resource == "gold"){

            $this->gold[] = [$position->x, $position->y, $position->z];
            return true;

        }elseif($resource == "silver"){

            $this->silver[] = [$position->x, $position->y, $position->z];
            return true;

        }elseif($resource == "bronze"){

            $this->bronze[$team][] = [$position->x, $position->y, $position->z];
            return true;

        }

        return false;

    }



    /**
     * @param Position $center
     */
    function setCenter(Position $center){

        $this->center = $center;

    }



    /**
     * @return bool
     */
    function check(): bool{

        if($this->name == "")
            return false;

        if($this->world == "")
            return false;

        if($this->slots == 0)
            return false;

        if($this->count_teams == 0)
            return false;

        if(!$this->center Instanceof Position)
            return false;

        if(count($this->gold) == 0 or count($this->bronze) == 0 or count($this->silver) == 0)
            return false;

        if(count($this->teams) == 0)
            return false;

        return true;

    }


}