<?php
namespace BW\async;

use BW\Loader;
use pocketmine\scheduler\AsyncTask;
use pocketmine\Server;
use RedBeanPHP\R;

class Status extends AsyncTask{

    /** @var string  */
    private string $server;
    /** @var string  */
    private string $mode;
    /** @var string  */
    private string $status;

    /** @var int  */
    private int $state;
    /** @var int  */
    private int $players;
    /** @var int  */
    private int $slots;
    /** @var int  */
    private int $online;



    /**
     * Status constructor.
     * @param string $server
     * @param string $mode
     * @param string $status
     * @param int $state
     * @param int $players
     * @param int $slots
     */
    function __construct(string $server, string $mode, string $status, int $state, int $players, int $slots){

        $this->server = $server;
        $this->mode = $mode;
        $this->status = $status;

        $this->state = $state;
        $this->players = $players;
        $this->slots = $slots;
        $this->online = count(Server::getInstance()->getOnlinePlayers());

    }



    function onRun(): void{

        if (!R::testConnection())
            R::setup('mysql:host=' . Loader::DB_HOST . ';dbname=' . Loader::DB_NAME, Loader::USER, Loader::PASSWORD);

        $id = 0;
        $data = R::find('servers', 'server LIKE :server', array(':server' => $this->server));
        if (count($data) == 0) {

            $servers = R::dispense("servers");
            $servers->server = $this->server;
            $servers->type = "bwclassic";
            $servers->mode = str_replace(" x ", ":", $this->mode);
            $servers->status = "on";

            $id = R::store($servers);
            var_dump($id);

        }else{

            $id = R::findOne("servers", "server = ?", [$this->server])->id;

        }


        $data = R::load("servers", $id);

        $data->status = $this->status;
        $data->state = $this->state;
        $data->players = $this->players;
        $data->slots = $this->slots;
        $data->online = $this->online;

        R::store($data);

    }

}