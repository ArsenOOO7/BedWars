<?php
namespace BW\arena\player;

class ArenaManager{

    /** @var bool */
    public bool $onArea = false;
    /** @var bool */
    public bool $player = false;
    /** @var bool */
    public bool $spectator = false;


    /** @var string */
    public string $arena = "";

    /** @var int */
    public int $team = -1;
    /** @var int */
    public int $shoots = 0;
    /** @var int */
    public int $kills = 0;
    /** @var int */
    public int $deaths = 0;
    /** @var int */
    public int $resources = 0;
    /** @var int */
    public int $beds = 0;
    /** @var int */
    public int $breaks = 0;
    /** @var int */
    public int $place = 0;

    /** @var int */
    public int $reward = 0;

    /** @var array */
    public array $last_attacker = [];

    /**
     * @param string $arena
     */
    function addArena(string $arena){

        $this->arena = $arena;
        $this->onArea = true;

        $this->player = true;

    }



    function remove(){

        $this->arena = "";

        $this->team = -1;
        $this->kills = 0;
        $this->shoots = 0;
        $this->deaths = 0;
        $this->resources = 0;
        $this->beds = 0;
        $this->reward = 0;
        $this->breaks = 0;
        $this->place = 0;

        $this->onArea = false;
        $this->player = false;
        $this->spectator = false;

    }



    function death(){

        $this->player = false;
        $this->spectator = true;

        $this->team = -1;

    }


    /**
     * @return bool
     */
    function onArena() : bool{

        return $this->onArea;

    }



    function addKill(){

        ++$this->kills;

    }


    function addBeds(){

        ++$this->beds;

    }




    function addShoot(){

        ++$this->shoots;

    }



    /**
     * @param int $resource
     */
    function addResources(int $resource){

        $this->resources += $resource;

    }


    /**
     * @return int
     */
    function getShoots() : int{

        return $this->shoots;

    }



    /**
     * @return int
     */
    function getKills() : int{

        return $this->kills;

    }



    /**
     * @return int
     */
    function getResources() : int{

        return $this->resources;

    }



    /**
     * @return int
     */
    function getBeds() : int{

        return $this->beds;

    }

}