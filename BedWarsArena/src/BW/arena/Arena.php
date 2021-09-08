<?php
namespace BW\arena;

use BW\arena\player\WarsPlayer;
use BW\async\Status;
use BW\packet\ServerCommunicate;
use BW\utils\Colors;
use BW\Loader;
use pocketmine\block\BlockFactory;
use pocketmine\block\VanillaBlocks;
use pocketmine\entity\effect\EffectInstance;
use pocketmine\entity\effect\VanillaEffects;
use pocketmine\player\Player;
use pocketmine\Server;
use pocketmine\utils\Config;
use pocketmine\world\Position;
use pocketmine\world\World;

class Arena{

    /** @var Loader */
    public Loader $loader;
    /** @var Positions */
    private Positions $positions;
    /** @var Teams[] */
    private array $teams = [];

    /** @var string */
    public string $name;
    /** @var string */
    public string $world;

    /** @var string */
    public string $type;

    /** @var array */
    public array $players = [];
    /** @var array */
    public array $spectators = [];
    /** @var array */
    public array $deaths = [];
    /** @var array */
    public array $teleport = [];
    /** @var array */
    public array $platform = [];
    /** @var array */
    public array $villagers = [];
    /** @var array */
    public array $resistance = [];

    /** @var int */
    private int $tick = 0;
    /** @var int */
    private int $countdown = 120;
    /** @var int */
    private int $maxTime = 3600;
    /** @var int */
    private int $state = 0;

    /** @var int */
    public int $slots = 16;
    /** @var int */
    public int $count_teams;
    /** @var int */
    public int $need_players;

    /** @var array|string[]  */
    public static array $SCOREBOARD_1 = [];
    public static array $SCOREBOARD_2 = [];
    public static array $SCOREBOARD_3 = [];


    /**
     * Arena constructor.
     * @param Loader $loader
     * @param string $name
     * @param string $world
     * @param int $slots
     * @param int $count_teams
     * @param array $positions
     * @param array $teams
     */
    function __construct(Loader $loader, string $name, string $world, int $slots, int $count_teams, array $positions = [], array $teams = []){

        $this->loader = $loader;

        $this->name = $name;
        $this->world = $world;

        $this->slots = $slots;
        $this->count_teams = $count_teams;
        $this->need_players = $slots / count($teams) * 2;
        $this->type = count($teams) . " x " . ($this->slots / count($teams));

        foreach($teams as $n => $team){

            $this->teams[$n] = new Teams($this, $n, count($teams), $team);

        }

        $this->positions = new Positions($this, $positions["center"], $positions["gold"], $positions["silver"], $positions["bronze"]);

        self::$SCOREBOARD_1 = [

            [0, "Статус: Ожидание"],
            [2, "Игроки: {CURRENT}/{SLOTS}"],
            [3, "Для начала игры нужно: {NEED}"],
            [5, "Ваша команда: {TEAM}"]

        ];

        self::$SCOREBOARD_2 = [

            [0, "Старт игры через: {TIME}"],
            [2, "Игроки: {CURRENT}/{SLOTS}"],
            [3, "Ваша команда: {TEAM}"]

        ];

    }


    /**
     * @return Positions
     */
    public function getPositions(): Positions{

        return $this->positions;

    }


    /**
     * @param int $team
     * @return Teams
     */
    function getTeam(int $team): Teams{

        return $this->teams[$team];

    }



    /**
     * @return int
     */
    function getState(): int{

        return $this->state;

    }



    /**
     * @return int
     */
    function getSlots(): int{

        return $this->slots;

    }



    /**
     * @return int
     */
    function getCountdown(): int{

        return $this->countdown;

    }



    /**
     * @return array
     */
    function getPlayers() : array{

        return $this->players;

    }



    /**
     * @return array
     */
    function getTeams() : array{

        return $this->teams;

    }



    /**
     * @return int
     */
    function getTime(): int{

        return $this->tick;

    }


    /**
     * @return array
     */
    function getAliveTeams() : array{

        $teams = [];
        foreach($this->teams as $n => $team){

            if($team->isAlive())
                $teams[$n] = $team;

        }

        return $teams;

    }



    /**
     * @return int
     */
    function getAliveTeamsCount() : int{

        return count($this->getAliveTeams());

    }



    /**
     * @return int
     */
    function getAliveTeam() : int{

        $alive = 0;
        foreach($this->teams as $n => $team){

            if($team->isAlive()){

                $alive = $n;
                break;

            }

        }

        return $alive;

    }



    /**
     * @return int
     */
    function getMinOnline() : int{

        $online = [];
        foreach($this->teams as $n => $team){

            $online[$n] = count($team->getPlayers());

        }

        return array_search(min($online), $online);

    }


    function startGame(){

        $this->updateQuery();

        foreach($this->players as $player_name){

            $player = $this->loader->getServer()->getPlayerExact($player_name);
            if(!$player Instanceof Player)
                continue;

            $player->getInventory()->clearAll();
            $player->getArmorInventory()->clearAll();
            $player->setImmobile(false);
            $player->getEffects()->clear();

            $player->getXpManager()->setXpLevel(0);
            $player->getXpManager()->setXpProgress(1.0);

            $player->sendMessage("Игра началась!!!");

        }

        $this->state = 2;
        $this->tick = 0;

    }



    function broadcast(string $message){

        $this->loader->getServer()->broadcastMessage($message);

    }



    function stopGame(int $win_team = 0){

        $this->maxTime = $this->tick + 15;
        $this->state = 3;

        if($win_team > 0){

            foreach($this->players as $players){

                $player = $this->loader->getServer()->getPlayerExact($players);
                if(!$player Instanceof WarsPlayer)
                    continue;

                $player->sendMessage("Ваша команда победила!!!");
                $player->getManager()->reward += 150;
                $player->sendStatistic(true);
                $player->prepare();

            }

            foreach($this->spectators as $specators){

                $player = $this->loader->getServer()->getPlayerExact($specators);
                if(!$player Instanceof WarsPlayer)
                    continue;

                $player->sendMessage("Игра окончена!");

            }


        }else{

            foreach($this->players as $players){

                $player = $this->loader->getServer()->getPlayerExact($players);
                if(!$player Instanceof WarsPlayer)
                    continue;

                $player->sendMessage("Игра окончена!");
                $player->prepare();
                $player->sendStatistic();

            }

            foreach($this->spectators as $specators){

                $player = $this->loader->getServer()->getPlayerExact($specators);
                if(!$player Instanceof WarsPlayer)
                    continue;

                $player->sendMessage("Игра окончена!");

            }

            $this->loader->getServer()->broadcastMessage("На арене ".$this->name." ничья");

        }

    }



    function quitAll(){

        foreach($this->players as $players){

            $player = $this->loader->getServer()->getPlayerExact($players);
            if(!$player Instanceof WarsPlayer)
                continue;

            $player->quitArena($this);

        }

        foreach($this->spectators as $specators){

            $player = $this->loader->getServer()->getPlayerExact($specators);
            if(!$player Instanceof WarsPlayer)
                continue;

            $player->quitArena($this);

        }

        $this->maxTime = $this->tick + 5;
        $this->state = 4;

    }



    function reloadArena(){

        $this->updateQuery("off");
        Server::getInstance()->shutdown();

    }


    /**
     * @param string $status
     */
    function updateQuery(string $status = "on"){

        Server::getInstance()->getAsyncPool()->submitTask(new Status($this->name, $this->type, $status, $this->getState(), count($this->getPlayers()), $this->getSlots()));


    }



    function teleportToPositions(){

        foreach($this->players as $players) {

            $player = $this->loader->getServer()->getPlayerExact($players);
            if (!$player instanceof WarsPlayer)
                continue;

            $player->teleportToSpawn($this);

        }

    }



    /**
     * @param int $state
     */
    function sendScoreboard(int $state = 0){

        if($state == 0){

            $scoreboard = self::$SCOREBOARD_1;
            $scoreboard[1][1] = str_replace(["{CURRENT}", "{SLOTS}"], [count($this->players), $this->slots], $scoreboard[1][1]);
            $scoreboard[2][1] = str_replace("{NEED}", $this->need_players - count($this->players), $scoreboard[2][1]);

            foreach($this->players as $player_name){

                $player = $this->loader->getServer()->getPlayerExact($player_name);
                if(!$player Instanceof WarsPlayer)
                    continue;

                $team = $player->getManager()->team;

                $scoreboard[3][1] = str_replace("{TEAM}", Colors::getCode($team).Colors::languageConvert($team), self::$SCOREBOARD_1[3][1]);

                $player->sendScoreboard($this->name, "FutureWorld BedWars", $scoreboard);

            }

        }elseif($state == 1 or $state == 2){

            $scoreboard = self::$SCOREBOARD_2;
            $scoreboard[0][1] = str_replace("{TIME}", date("i:s", ($this->countdown - $this->tick)), $scoreboard[0][1]);
            $scoreboard[1][1] = str_replace(["{CURRENT}", "{SLOTS}"], [count($this->players), $this->slots], $scoreboard[1][1]);

            foreach($this->players as $player_name){

                $player = $this->loader->getServer()->getPlayerExact($player_name);
                if(!$player Instanceof WarsPlayer)
                    continue;

                $team = $player->getManager()->team;
                $scoreboard[2][1] = str_replace("{TEAM}", Colors::getCode($team).Colors::languageConvert($team), self::$SCOREBOARD_2[2][1]);

                $player->sendScoreboard($this->name, "FutureWorld BedWars", $scoreboard);

            }

        }elseif($state == 3){

            $scoreboard = [];

            foreach($this->getAliveTeams() as $n => $team){

                if($team->hasBed)
                    $bed = "✓";
                else
                    $bed = "✗";

                $scoreboard[] = [count($team->getPlayers()), $bed." ".Colors::getCode($n).Colors::languageConvert($n)];

            }

            $scoreboard[] = [count($scoreboard), "До конца игры: ". date("i:s", ($this->maxTime - $this->tick))];

            foreach($this->players as $player_name){

                $player = $this->loader->getServer()->getPlayerExact($player_name);
                if(!$player Instanceof WarsPlayer)
                    continue;

                $player->sendScoreboard($this->name, "FutureWorld BedWars", $scoreboard);

            }

        }

    }



    function run(){

        if($this->state > 4)
            return;

        if($this->state == 0){

            if(count($this->players) == 0)
                return;

            if(count($this->players) < $this->need_players){

                $this->sendScoreboard(0);
                return;

            }

            if(count($this->players) >= $this->need_players){

                $this->sendScoreboard(1);

            }


            if($this->slots == count($this->players) and $this->tick < 100){

                $this->tick = 100;

            }

            if($this->countdown - $this->tick == 10){

                $this->teleportToPositions();
                $this->state = 1;
                $this->updateQuery();

            }

        }

        ++$this->tick;

        if($this->state == 1){

            $this->sendScoreboard(2);

            if($this->countdown == $this->tick){

                $this->startGame();
                return;

            }

        }

        if($this->state == 2){

            $this->sendScoreboard(3);

            if($this->tick % 3 == 0){

                $this->positions->spawnBronze();

            }

            if($this->tick % 20 == 0){

                $this->positions->spawnSilver();

            }

            if($this->tick % 40 == 0){

                $this->positions->spawnGold();

            }

            if(count($this->deaths) > 0) {

                foreach($this->deaths as $player_name => $time){

                    $player = $this->loader->getServer()->getPlayerExact($player_name);
                    if(!$player instanceof WarsPlayer)
                        continue;

                    if(time() >= $time) {

                        $player->respawnArena($this);
                        unset($this->deaths[strtolower($player_name)]);

                    }else{

                        $player->sendTitle("Респавн через", "" . ($time - time()));

                    }

                }

            }

            if(count($this->teleport) > 0){

                foreach($this->teleport as $player_name => $time){

                    $player = $this->loader->getServer()->getPlayerExact($player_name);
                    if(!$player instanceof WarsPlayer)
                        continue;

                    if(time() >= $time) {

                        $player->respawnArena($this, false);
                        unset($this->teleport[strtolower($player_name)]);

                    }else{

                        $player->sendTitle("Телепорт через", "" . ($time - time()));

                    }

                }

            }

            if(count($this->platform) > 0){

                foreach($this->platform as $n => $platform){

                    if($platform["time"] > time())
                        continue;

                    foreach($platform["block"] as $block){

                        if($block Instanceof Position) {

                            $block->getWorld()->setBlockAt($block->x, $block->y, $block->z, VanillaBlocks::AIR());

                        }

                    }

                    unset($this->platform[$n]);

                }

            }

            if(count($this->villagers) > 0){

                foreach($this->villagers as $id => $time){

                    if($time > time())
                        continue;

                    if($this->loader->getServer()->getWorldManager()->getDefaultWorld()->getEntity($id) != null)
                        $this->loader->getServer()->getWorldManager()->getDefaultWorld()->getEntity($id)->close();

                    unset($this->villagers[$id]);

                }

            }

            if(count($this->resistance) > 0){

                foreach($this->resistance as $player_name => $time){

                    $player = Server::getInstance()->getPlayerExact($player_name);
                    if(!$player instanceof WarsPlayer)
                        continue;

                    if($time > time())
                        continue;

                    $effect = new EffectInstance(VanillaEffects::RESISTANCE());
                    $effect->setAmplifier(3);
                    $effect->setDuration(20 * ($this->maxTime + 5000));

                    $player->getEffects()->add($effect);
                    unset($this->resistance[$player_name]);

                }

            }

            if($this->getAliveTeamsCount() == 1){

                $this->stopGame($this->getAliveTeam());
                return;

            }

            if($this->maxTime == $this->tick){

                $this->stopGame();

            }

        }

        if($this->state == 3){

            if($this->maxTime == $this->tick){

                $this->quitAll();

            }

        }

        if($this->state == 4){

            if($this->maxTime == $this->tick){

                $this->reloadArena();

            }

        }

    }

}