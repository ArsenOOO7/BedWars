<?php
namespace BW;

use alemiz\sga\StarGateAtlantis;
use BW\async\Status;
use BW\packet\ServerCommunicate;
use BW\arena\Arena;
use BW\command\BWcommand;
use BW\metadata\Metadata;
use BW\shop\Shop;
use muqsit\invmenu\InvMenuHandler;
use pocketmine\plugin\PluginBase;
use pocketmine\scheduler\Task;
use pocketmine\utils\Config;
use pocketmine\world\World;
use RedBeanPHP\R;

class Loader extends PluginBase{

    /** @var Loader  */
    public static Loader $instance;

    /** @var Arena|null  */
    public ?Arena $arena = null;
    /** @var Metadata */
    public Metadata $metadata;
    /** @var Shop */
    public Shop $shop;

    /** @var array */
    public array $register = [];

    const DB_HOST = "127.0.0.1:3306";
    const DB_NAME = "minigames";
    const USER = "mysql";
    const PASSWORD = "mysql";


    function onEnable() : void{

        self::$instance = $this;

        $this->getLogger()->info("+");
        $this->getServer()->getPluginManager()->registerEvents(new EventListener($this), $this);
        $this->getServer()->getCommandMap()->register("bw", new BWcommand($this));
        $this->getScheduler()->scheduleRepeatingTask(new Run($this), 20);

        $this->metadata = new Metadata();
        $this->shop = new Shop($this);

        $world = $this->getServer()->getWorldManager()->getDefaultWorld();
        $world->setAutoSave(false);
        $world->setTime(World::TIME_DAY);
        $world->stopTime();

        if(!InvMenuHandler::isRegistered())
            InvMenuHandler::register($this);

        if(!file_exists($this->getDataFolder()."arena.json")){

            $this->getLogger()->warning("У вас не зарегана аренпа!");

        }else{

            $this->loadArena();

        }

        StarGateAtlantis::getInstance()->onClientCreation("lobby", StarGateAtlantis::getInstance()->getClient("lobby"));

    }



    /**
     * @return Loader
     */
    static function getInstance(): Loader{

        return self::$instance;

    }



    function loadArena(){

        $arena = (new Config($this->getDataFolder()."arena.json", Config::JSON))->getAll();
        $teams = (new Config($this->getDataFolder()."teams.json", Config::JSON))->getAll();
        $positions = (new Config($this->getDataFolder()."positions.json", Config::JSON))->getAll();

        $this->arena = new Arena($this, $arena["arena"], $arena["world"], $arena["slots"], $arena["count_teams"], $positions, $teams);
        $this->getLogger()->info("Арена успешно запущена!");
        $this->getServer()->getAsyncPool()->submitTask(new Status($arena["arena"], $this->arena->type, "on", 0, 0, $arena["slots"]));

    }



    function onDisable() : void{

        if (!R::testConnection())
            R::setup('mysql:host=' . Loader::DB_HOST . ';dbname=' . Loader::DB_NAME, Loader::USER, Loader::PASSWORD);

        $id = R::findOne("servers", "server = ?", [$this->arena->name])->id;
        $data = R::load("servers", $id);

        $data->status = "off";

        R::store($data);

    }

}

class Run extends Task{

    /** @var Loader  */
    private Loader $loader;


    /**
     * Run constructor.
     * @param Loader $loader
     */
    function __construct(Loader $loader){

        $this->loader = $loader;

    }



    function onRun(): void{

        if($this->loader->arena Instanceof Arena)
            $this->loader->arena->run();

    }

}