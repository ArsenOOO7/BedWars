<?php
namespace BW\arena\player;

use alemiz\sga\StarGateAtlantis;
use BW\arena\Arena;
use BW\async\Status;
use BW\async\Update;
use BW\Loader;
use BW\packet\ServerCommunicate;
use BW\utils\Colors;
use muqsit\invmenu\InvMenu;
use muqsit\invmenu\transaction\DeterministicInvMenuTransaction;
use pocketmine\color\Color;
use pocketmine\entity\effect\Effect;
use pocketmine\entity\effect\EffectInstance;
use pocketmine\entity\effect\EffectManager;
use pocketmine\entity\effect\InvisibilityEffect;
use pocketmine\entity\effect\VanillaEffects;
use pocketmine\item\Armor;
use pocketmine\item\Bed;
use pocketmine\item\Durable;
use pocketmine\item\Item;
use pocketmine\item\ItemFactory;
use pocketmine\item\ItemIds;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\network\mcpe\NetworkSession;
use pocketmine\network\mcpe\protocol\RemoveObjectivePacket;
use pocketmine\network\mcpe\protocol\SetDisplayObjectivePacket;
use pocketmine\network\mcpe\protocol\SetScorePacket;
use pocketmine\network\mcpe\protocol\types\ScorePacketEntry;
use pocketmine\player\GameMode;
use pocketmine\player\Player;
use pocketmine\player\PlayerInfo;
use pocketmine\Server;

class WarsPlayer extends Player{


    /** @var ArenaManager */
    private ArenaManager $manager;

    /** @var string */
    private string $remote_address;

    /**
     * WarsPlayer constructor.
     * @param Server $server
     * @param NetworkSession $session
     * @param PlayerInfo $playerInfo
     * @param bool $authenticated
     * @param CompoundTag|null $namedtag
     */
    function __construct(Server $server, NetworkSession $session, PlayerInfo $playerInfo, bool $authenticated, ?CompoundTag $namedtag){

        parent::__construct($server, $session, $playerInfo, $authenticated, $namedtag);
        $this->manager = new ArenaManager();

    }


    /**
     * @return ArenaManager
     */
    function getManager() : ArenaManager{

        return $this->manager;

    }



    /**
     * @param string $xuid
     */
    function setXUID(string $xuid){

        $this->xuid = $xuid;

    }



    /**
     * @param string $address
     */
    function setAddress(string $address){

        $this->remote_address = $address;

    }



    /**
     * @param string $objectiveName
     */
    function removeScoreboard(string $objectiveName){

        $packet = new RemoveObjectivePacket();
        $packet->objectiveName = $objectiveName;
        $this->getNetworkSession()->sendDataPacket($packet);

    }


    /**
     * @param string $objectiveName
     * @param string $title
     * @param array $lines
     */
    function sendScoreboard(string $objectiveName, string $title, array $lines = []){

        $this->removeScoreboard($objectiveName);
        $set_display_objective_packet = new SetDisplayObjectivePacket();
        $set_display_objective_packet->displaySlot = "sidebar";
        $set_display_objective_packet->objectiveName = $objectiveName;
        $set_display_objective_packet->criteriaName = "dummy";
        $set_display_objective_packet->sortOrder = 0;
        $set_display_objective_packet->displayName = $title;

        $this->getNetworkSession()->sendDataPacket($set_display_objective_packet);

        $set_score_packet = new SetScorePacket();
        $set_score_packet->type = 0;

        $id = 0;
        foreach($lines as $n => $param){

            $entry = new ScorePacketEntry();
            $entry->objectiveName = $objectiveName;
            $entry->type = 3;
            $entry->customName = $param[1];
            $entry->score = $param[0];
            $entry->scoreboardId = ++$id;

            $set_score_packet->entries[] = $entry;

        }

        $this->getNetworkSession()->sendDataPacket($set_score_packet);

    }




    /**
     * @param Arena $arena
     */
    function joinArena(Arena $arena){

        $nick = strtolower($this->getName());

        $this->manager->addArena($arena->name);

        $this->getInventory()->clearAll();
        $this->getArmorInventory()->clearAll();

        $this->getXpManager()->setXpProgress(0.0);
        $this->getXpManager()->setXpLevel(0);

        $this->getEffects()->clear();

        $this->setGamemode(GameMode::SURVIVAL());

        $arena->players[] = $nick;
        $this->teleport($arena->getPositions()->getCenter());

        $choose = ItemFactory::getInstance()->get(ItemIds::BED, 0, 1);
        $choose->setCustomName("Команды");
        $this->getInventory()->addItem($choose);

        $this->setHealth($this->getMaxHealth());
        $this->getHungerManager()->setFood(20);


        $left = ItemFactory::getInstance()->get(ItemIds::SLIME_BALL, 0, 1);
        $left->setCustomName("Покинуть игру");
        $this->getInventory()->setItem(8, $left);

        foreach($arena->players as $player_name){

            $player = $this->getServer()->getPlayerExact($player_name);
            if($player Instanceof Player){

                $player->sendMessage("[+] ".$this->getName().". ".count($arena->players)."/".$arena->slots);

            }

        }

        $arena->updateQuery();
        if($arena->getCountdown() - $arena->getTime() < 10)
            $this->teleportToSpawn($arena);

    }


    /**
     * @param Arena $arena
     * @param bool $hasBed
     */
    function death(Arena $arena, bool $hasBed = true){

        if(!$arena->getTeam($this->getManager()->team)->hasBed){

            $this->setGamemode(GameMode::SPECTATOR());
            $this->teleport($this->getWorld()->getSafeSpawn());

            $this->getInventory()->clearAll();
            $this->getArmorInventory()->clearAll();

            $this->getEffects()->clear();

            $this->manager->resources = 0;
            $this->getXpManager()->setXpProgress(0.0);
            $this->getXpManager()->setXpLevel(0);

            $this->setHealth($this->getMaxHealth());
            $this->getHungerManager()->setFood(20);

            $arena->spectators[] = strtolower($this->username);
            unset($arena->players[array_search(strtolower($this->username), $arena->players)]);

            $arena->getTeam($this->manager->team)->removePlayer($this->username);
            $arena->getTeam($this->manager->team)->changeStatus();

            $this->manager->death();

            $this->getInventory()->addItem(ItemFactory::getInstance()->get(ItemIds::COMPASS, 0, 1));
            $this->sendStatistic();

        }else{

            $this->teleport($this->getWorld()->getSafeSpawn());

            $arena->deaths[strtolower($this->username)] = time() + 5;
            $this->getInventory()->clearAll();
            $this->getArmorInventory()->clearAll();

            $this->manager->resources = 0;
            $this->getXpManager()->setXpProgress(0.0);
            $this->getXpManager()->setXpLevel(0);

            $effect_invisibility = new EffectInstance(VanillaEffects::INVISIBILITY());
            $effect_invisibility->setAmbient(5);
            $effect_invisibility->setDuration(20 * 5);
            $effect_invisibility->setColor(new Color(0 ,0, 0));

            $effect_saturation = new EffectInstance(VanillaEffects::SATURATION());
            $effect_saturation->setAmbient(5);
            $effect_saturation->setDuration(20 * 5);
            $effect_saturation->setColor(new Color(0 ,0, 0));

            $this->getEffects()->add($effect_invisibility);
            $this->getEffects()->add($effect_saturation);

            $this->setImmobile(true);
            $this->setHealth($this->getMaxHealth());
            $this->getHungerManager()->setFood(20);


        }

    }



    /**
     * @param Arena $arena
     * @param int $team
     */
    function setTeam(Arena $arena, $team = 0){

        $this->manager->team = $team;

        $arena->getTeam($team)->addPlayer($this->username);

        $this->setNameTag("§7[".Colors::getCode($team).Colors::languageConvert($team)."§7] §f".$this->username);
        $this->setNameTagVisible(true);
        $this->setNameTagAlwaysVisible(true);

        $this->sendMessage("Ваша команда - ".Colors::getCode($team).Colors::languageConvert($team));

    }


    /**
     * @param Arena $arena
     * @return bool
     */
    function isPlaying(Arena $arena) : bool{

        if(in_array(strtolower($this->username), $arena->spectators))
            return false;

        return in_array(strtolower($this->username), $arena->players);

    }



    /**
     * @param Arena $arena
     */
    function teleportToSpawn(Arena $arena){

        if($this->manager->team < 0){

            $this->setTeam($arena, $arena->getMinOnline());

        }

        $this->setImmobile(true);

        $this->getInventory()->clearAll();
        $this->getArmorInventory()->clearAll();

        $this->teleport($arena->getTeam($this->manager->team)->getPosition());

    }


    /**
     * @param Arena $arena
     * @param bool $death
     */
    function respawnArena(Arena $arena, $death = true){

        $team = $arena->getTeam($this->manager->team);

        if($death) {

            $this->getEffects()->clear();
            $this->setImmobile(false);

        }

        $pos = $team->getPosition();
        $this->teleport($team->getPosition()->add(0, 1 + ($pos->getWorld()->getHighestBlockAt($pos->getFloorX() >> 4, $pos->getFloorZ() >> 4) - $pos->y), 0));

    }



    /**
     * @param bool $win
     */
    function sendStatistic(bool $win = false){

        $message = "Твоя статистика: \n";
        $message .= "Убито игроков: ".$this->manager->kills."\n";
        $message .= "Подстрелено игроков: ".$this->manager->shoots."\n";
        $message .= "Сломано кроватей: ".$this->manager->beds."\n";
        $message .= "Смертей: ".$this->manager->deaths."\n\n";
        $message .= "Поставлено блоков: ".$this->manager->place."\n";
        $message .= "Сломано блоков: ".$this->manager->breaks."\n\n";

        $time = Loader::getInstance()->arena->getTime();

        $hours = floor($time / 3600);
        $minutes = floor(($time / 60) % 60);
        $seconds = $time % 60;

        $message .= "Время вашей игры: {$hours} часов {$minutes} минут {$seconds} секунд\n\n\n";

        $message .= "Ваша награда: ".$this->manager->reward;

        $this->sendMessage($message);

        $data = [

            "kills" => $this->manager->kills,
            "shoots" => $this->manager->shoots,
            "beds" => $this->manager->beds,
            "deaths" => $this->manager->deaths,
            "placed" => $this->manager->place,
            "breaks" => $this->manager->breaks,
            "time" => $time,
            "reward" => $this->manager->reward

        ];

        Server::getInstance()->getAsyncPool()->submitTask(new Update($this->getName(), $data, $win));

    }


    /**
     * @param Arena $arena
     * @param bool $broadcast
     */
    function quitArena(Arena $arena, bool $broadcast = false){

        $this->removeScoreboard($arena->name);

        $team = $this->manager->team;
        $this->manager->remove();
        $nick = strtolower($this->username);

        if(in_array($nick, $arena->players)){

            unset($arena->players[array_search($nick, $arena->players)]);

        }

        if($team >= 0){

            $arena->getTeam($team)->removePlayer($nick);
            $arena->getTeam($team)->changeStatus();

        }

        if(in_array($nick, $arena->spectators)){

            unset($arena->spectators[array_search($nick, $arena->spectators)]);

        }

        $this->getInventory()->clearAll();
        $this->getArmorInventory()->clearAll();

        $this->setHealth($this->getMaxHealth());
        $this->getHungerManager()->setFood(20);

        $this->getEffects()->clear();
        $this->setGamemode(GameMode::SURVIVAL());

        $this->getXpManager()->setXpLevel(0.0);
        $this->getXpManager()->setXpProgress(0.0);

        if($broadcast){

            if(in_array(strtolower($this->username), $arena->spectators))
                return;

            foreach($arena->players as $player_name){

                $player = $this->getServer()->getPlayerExact($player_name);
                if($player Instanceof Player){

                    $player->sendMessage("[-] ".$this->getName().". ".count($arena->players)."/".$arena->slots);

                }

            }

            foreach($arena->spectators as $player_name){

                $player = $this->getServer()->getPlayerExact($player_name);
                if($player Instanceof Player){

                    $player->sendMessage("[-] ".$this->getName().". ".count($arena->players)."/".$arena->slots);

                }

            }

        }

        if($arena->getState() == 0){

            $arena->updateQuery();

        }

    }



    /**
     * @return bool
     */
    function isOp(): bool{

        return $this->server->isOp(strtolower($this->username));

    }



    function prepare(){

        $this->removeScoreboard(Loader::getInstance()->arena->name);
        $this->getInventory()->clearAll();
        $this->getArmorInventory()->clearAll();
        $this->getEffects()->clear();

        $this->getInventory()->addItem(ItemFactory::getInstance()->get(ItemIds::COMPASS, 0, 1));

    }




    /**
     * @param Armor $armor
     */
    function sendEquipment(Armor $armor){

        if($armor->getId() == ItemIds::LEATHER_CAP){

            if($this->getArmorInventory()->getHelmet()->getId() == 0 or $this->getArmorInventory()->getHelmet()->getDamage() < 15){

                $this->getInventory()->addItem($this->getArmorInventory()->getHelmet());
                $this->getArmorInventory()->setHelmet($armor);

            }else{

                $this->getInventory()->addItem($armor);

            }

        }elseif($armor->getId() == ItemIds::LEATHER_LEGGINGS){

            if($this->getArmorInventory()->getLeggings()->getId() == 0 or $this->getArmorInventory()->getLeggings()->getDamage() < 15){

                $this->getInventory()->addItem($this->getArmorInventory()->getLeggings());
                $this->getArmorInventory()->setLeggings($armor);

            }else{

                $this->getInventory()->addItem($armor);

            }

        }elseif($armor->getId() == ItemIds::CHAIN_CHESTPLATE){

            if($this->getArmorInventory()->getChestplate()->getId() == 0 or $this->getArmorInventory()->getChestplate()->getDamage() < 15 or (int) $this->getArmorInventory()->getChestplate()->getLore()[0] < (int) $armor->getLore()[0]){

                $this->getInventory()->addItem($this->getArmorInventory()->getChestplate());
                $this->getArmorInventory()->setChestplate($armor);

            }else{

                $this->getInventory()->addItem($armor);

            }

        }elseif($armor->getId() == ItemIds::LEATHER_BOOTS){

            if($this->getArmorInventory()->getBoots()->getId() == 0 or $this->getArmorInventory()->getBoots()->getDamage() < 15){

                $this->getInventory()->addItem($this->getArmorInventory()->getBoots());
                $this->getArmorInventory()->setBoots($armor);

            }else{

                $this->getInventory()->addItem($armor);

            }

        }

    }

}