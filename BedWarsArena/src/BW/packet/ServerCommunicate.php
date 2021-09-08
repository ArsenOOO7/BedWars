<?php
namespace BW\packet;

use alemiz\sga\packets\StarGatePacket;
use alemiz\sga\StarGateAtlantis;
use alemiz\sga\utils\Convertor;
use BW\arena\Arena;
use BW\async\Status;
use BW\Loader;
use pocketmine\Server;

class ServerCommunicate extends StarGatePacket{

    /** @var int  */
    public const NETWORK_ID = 0x100;

    /** @var string */
    public string $from;
    /** @var string  */
    public string $arena;
    /** @var string  */
    public string $mode;

    /** @var int  */
    public int $state;
    /** @var int  */
    public int $slots;
    /** @var int  */
    public int $max_players;



    function __construct(){

        parent::__construct("SERVER_COMMUNICATE", self::NETWORK_ID);
        $this->from = "bw1";

    }



    /**
     * @param Arena $arena
     */
    static function sendUpdate(Arena $arena){

        $packet = new ServerCommunicate();
        $packet->arena = $arena->name;
        $packet->mode = str_replace(" x ", ":", $arena->type);
        $packet->slots = count($arena->getPlayers());
        $packet->state = $arena->getState();
        $packet->slots = $arena->getSlots();

        StarGateAtlantis::getInstance()->forwardPacket("lobby", "default", $packet);



    }


    public function encode(): void{

        $convertor = new Convertor(self::NETWORK_ID);
        $arena = Loader::getInstance()->arena;
        $mode = str_replace(" x ", ":", $arena->type);

        $convertor->putString($this->from);
        $convertor->putString($arena->name);
        $convertor->putString($mode);
        $convertor->putInt($arena->getState());
        $convertor->putInt(count($arena->getPlayers()));
        $convertor->putInt($arena->getSlots());

        $this->encoded = $convertor->getPacketString();
        $this->isEncoded = true;


    }

    public function decode(): void{

        $this->isEncoded = false;
        $data = Convertor::getPacketStringData($this->encoded);

        $this->from = $data[1];
        $this->arena = $data[2];
        $this->mode = $data[3];
        $this->state = (int) $data[4];
        $this->slots = (int) $data[5];
        $this->max_players = (int) $data[6];

    }
}