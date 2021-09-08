<?php
namespace BW\arena;

use BW\arena\player\WarsPlayer;
use BW\utils\Colors;
use pocketmine\Server;
use pocketmine\world\Position;

class Teams{

    /** @var Arena */
    private Arena $arena;
    /** @var Position */
    private Position $spawn;

    /** @var string */
    public string $tag;

    /** @var bool */
    private bool $isAlive = true;
    /** @var bool */
    public bool $hasBed = true;

    /** @var int */
    private int $team;

    /** @var int */
    private int $max_players;

    /** @var array */
    private array $players = [];


    /**
     * Teams constructor.
     * @param Arena $arena
     * @param int $team
     * @param int $teams
     * @param array $params
     */
    function __construct(Arena $arena, int $team, int $teams, array $params = []){

        $this->arena = $arena;
        $this->spawn = new Position($params["x"], $params["y"], $params["z"], Server::getInstance()->getWorldManager()->getDefaultWorld());

        $this->team = $team;
        $this->tag = Colors::convertIntToTag($team);

        $this->max_players = $arena->slots / $teams;

    }


    /**
     * @return array
     */
    function getPlayers(): array {

        return $this->players;

    }


    /**
     * @return bool
     */
    function isAlive() : bool{

        return $this->isAlive;

    }



    /**
     * @param string
     */
    function addPlayer(string $player){

        $this->players[] = strtolower($player);

    }



    /**
     * @return Position
     */
    function getPosition(): Position {

        return $this->spawn;

    }



    /**
     * @return int
     */
    function getTeam(): int {

        return $this->team;

    }



    /**
     * @return int
     */
    function getBedColour(): int {

        return $this->team;

    }



    /**
     * @param string $player
     */
    function removePlayer(string $player){

        if(in_array(strtolower($player), $this->players))
            unset($this->players[array_search(strtolower($player), $this->players)]);

    }



    function destroyBed(){

        $this->hasBed = false;

    }


    function changeStatus(){

        if(count($this->players) <= 0 and $this->arena->getState() == 2){

            $this->isAlive = false;
            $this->arena->broadcast("Команда ".Colors::getCode($this->team).Colors::languageConvert($this->team)." уничтожена!");

        }

    }



    function sendMessage(string $sender, string $message){

        foreach($this->players as $player_name){

            $player = $this->arena->loader->getServer()->getPlayerExact($player_name);
            if(!$player Instanceof WarsPlayer)
                return;

            $player->sendMessage("(".$this->arena->name.") [Команде] {$sender} -> {$message}");

        }

    }



    /**
     * @return bool
     */
    function canEnter(): bool{

        if(count($this->getPlayers()) == $this->max_players)
            return false;

        if(count($this->arena->getPlayers()) >= 2 and count($this->getPlayers()) >= round((count($this->arena->getPlayers()) / $this->arena->count_teams)))
            return false;

        return true;

    }



    function reload(){

        $this->players = [];
        $this->isAlive = true;
        $this->hasBed = true;

    }

}