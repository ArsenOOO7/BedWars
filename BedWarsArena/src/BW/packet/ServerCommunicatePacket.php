<?php
namespace BedWars\packet;

use alemiz\sga\packets\StarGatePacket;
use alemiz\sga\utils\Convertor;
use BW\Loader;

class ServerCommunicatePacket extends StarGatePacket{

    /** @var int  */
    public const NETWORK_ID = 0x100;

    /** @var string  */
    public string $arena;

    /** @var int  */
    public int $state;
    /** @var int  */
    public int $slots;
    /** @var int  */
    public int $max_players;



    function __construct(){

        parent::__construct("SERVER_COMMUNICATE", self::NETWORK_ID);

    }


    public function encode(): void{

        $convertor = new Convertor(self::NETWORK_ID);
        $arena = Loader::getInstance()->arena;

        $convertor->putString($arena->name);
        $convertor->putInt($arena->getState());
        $convertor->putInt(count($arena->getPlayers()));
        $convertor->putInt($arena->getSlots());

        $this->encoded = $convertor->getPacketString();
        $this->isEncoded = true;


    }

    public function decode(): void{

        $this->isEncoded = false;
        $data = Convertor::getPacketStringData($this->encoded);

        $this->arena = $data[0];
        $this->state = (int) $data[1];
        $this->slots = (int) $data[2];
        $this->max_players = (int) $data[3];

    }
}