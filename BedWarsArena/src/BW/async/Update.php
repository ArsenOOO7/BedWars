<?php
namespace BW\async;

use BW\Loader;
use pocketmine\scheduler\AsyncTask;
use RedBeanPHP\R;

class Update extends AsyncTask{

    /** @var string  */
    private string $player;

    /** @var int  */
    private int $kills;
    /** @var int  */
    private int $deaths;
    /** @var int  */
    private int $shoots;
    /** @var int  */
    private int $beds;
    /** @var int  */
    private int $placed;
    /** @var int  */
    private int $broke;
    /** @var int  */
    private int $time;

    /** @var int */
    private int $reward;

    /** @var bool */
    private bool $win = false;


    function __construct(string $player, array $data, bool $win = false){

        $this->player = $player;
        $this->win = $win;

        $this->kills = $data["kills"];
        $this->deaths = $data["deaths"];
        $this->shoots = $data["shoots"];
        $this->beds = $data["beds"];
        $this->placed = $data["placed"];
        $this->broke = $data["breaks"];
        $this->time = $data["time"];
        $this->reward = $data["reward"];

    }




    function onRun(): void{

        if(!R::testConnection())
            R::setup('mysql:host=' . Loader::DB_HOST . ';dbname=' . Loader::DB_NAME, Loader::USER, Loader::PASSWORD);


        $data = R::findOne("users", "nick = ?", [$this->player]);
        $store = R::load("bedwars", $data->id);

        $store->kills += $this->kills;
        $store->deaths += $this->deaths;
        $store->shoots += $this->shoots;
        $store->beds += $this->beds;
        $store->placed += $this->placed;
        $store->broke += $this->broke;
        $store->time += $this->time;

        ++$store->games;
        if($this->win)
            ++$store->wins;

        R::store($store);

        $money = R::load("users", $data->id);
        $money->money += $this->reward;

        R::store($money);

    }

}