<?php
namespace BW\command;

use BW\arena\player\WarsPlayer;
use BW\arena\register\Register;
use BW\utils\Colors;
use BW\Loader;
use pocketmine\block\VanillaBlocks;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\command\utils\CommandException;
use pocketmine\entity\EntityDataHelper;
use pocketmine\entity\Human;
use pocketmine\entity\Villager;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\ChangeDimensionPacket;
use pocketmine\network\mcpe\protocol\types\GeneratorType;
use pocketmine\world\generator\Flat;
use pocketmine\world\World;

class BWcommand extends Command{

    /** @var Loader */
    private Loader $loader;


    function __construct(Loader $loader){

        parent::__construct("bw", "bw cmd");

        $this->loader = $loader;

    }



    /**
     * @param CommandSender $sender
     * @param string $commandLabel
     * @param array $args
     * @return mixed|void
     */
    function execute(CommandSender $sender, string $commandLabel, array $args){

        if(!$sender Instanceof WarsPlayer)
            return;

        if(!$sender->isOp())
            return;

        switch(array_shift($args)){

            default:

                $sender->sendMessage(


                    "Команды бед варса: \n".
                    "/bw create - создать арену \n".
                    "/bw setspawn - поставить спавн команде\n".
                    "/bw center - поставить центр \n".
                    "/bw setres - поставить спавнер ресурсов\n".
                    "/bw remove - удалить арену"

                );

                break;

            case "create":

                if(count($args) < 3){

                    $sender->sendMessage("Используйте: /bw create [название] [слоты] [к-во команд]");
                    return;

                }

                $name = array_shift($args);
                $slots = (int) array_shift($args);
                $teams = (int) array_shift($args);


                if($slots % $teams != 0){

                    $sender->sendMessage("Все должно быть ровно! ".($slots % $teams));
                    return;

                }

                $register = new Register();
                $register->name = $name;
                $register->world = $sender->getPosition()->world->getFolderName();
                $register->slots = $slots;
                $register->count_teams = $teams;

                $this->loader->register[strtolower($sender->getName())] = $register;
                $sender->sendMessage("Отлично! Теперь поставте центр! /bw center");

                break;

            case "center":

                if(!array_key_exists(strtolower($sender->getName()), $this->loader->register))
                    return;

                $register = $this->loader->register[strtolower($sender->getName())];
                if($register->world != $sender->getWorld()->getFolderName()){

                    $sender->sendMessage("Вы должны быть в мире арены!");
                    return;

                }

                $register->center = $sender->getPosition();
                $sender->sendMessage("Вы успешно поставили центр! Теперь спавн для команд: /bw setspawn");

                break;

            case "setspawn":

                if(!array_key_exists(strtolower($sender->getName()), $this->loader->register))
                    return;

                $register = $this->loader->register[strtolower($sender->getName())];
                if($register->world != $sender->getWorld()->getFolderName()){

                    $sender->sendMessage("Вы должны быть в мире арены!");
                    return;

                }

                if(!isset($args[0])){

                    $sender->sendMessage("Используйте: /bw setspawn [команда (англ)]");
                    return;

                }

                $team = array_shift($args);
                if(Colors::convertTagToInt($team) == 0){

                    $sender->sendMessage("Такой команды нет!");
                    return;

                }

                $register->setSpawn($team, $sender->getPosition());
                if(count($register->teams) == $register->count_teams){

                    $sender->sendMessage("Отлично! Терь ресы: /bw setres. Когда закончите, напишите /bw save");

                }

                break;

            case "setres":

                if(!array_key_exists(strtolower($sender->getName()), $this->loader->register))
                    return;

                $register = $this->loader->register[strtolower($sender->getName())];
                if($register->world != $sender->getWorld()->getFolderName()){

                    $sender->sendMessage("Вы должны быть в мире арены!");
                    return;

                }

                if(!isset($args[0])){

                    $sender->sendMessage("Используйте: /bw setres [bronze/silver/gold]");
                    return;

                }

                if($args[0] == "bronze"){

                    if(!isset($args[1])){

                        $sender->sendMessage("Используйте: /bw setres bronze [команда (tag)]");
                        return;

                    }

                }

                $resource = array_shift($args);

                $team = "null";
                if(isset($args[0]))
                    $team = (array_shift($args));

                if($register->setResource($resource, $sender->getPosition(), $team)){

                    $sender->sendMessage("Вы успешно поставили ресурс {$resource}");

                }else{

                    $sender->sendMessage("Такого ресурса нет!");

                }

                break;

            case "save":

                if(!array_key_exists(strtolower($sender->getName()), $this->loader->register))
                    return;

                $register = $this->loader->register[strtolower($sender->getName())];
                if($register->world != $sender->getWorld()->getFolderName()){

                    $sender->sendMessage("Вы должны быть в мире арены!");
                    return;

                }

                if(!$register->check()){

                    $sender->sendMessage("Вы что-то забыли ,_,");
                    return;

                }

                $sender->transfer("localhost", 19132);
                $register->saveAll($this->loader);

                unset($this->loader->register[strtolower($sender->getName())]);



                break;

            case "exit":

                if(!array_key_exists(strtolower($sender->getName()), $this->loader->register))
                    return;

                unset($this->loader->register[strtolower($sender->getName())]);
                $sender->sendMessage("Вы успешно вышли с режима создания арены!");

                break;

            case "world":

                if(!isset($args[0])){

                    $sender->sendMessage("/bw world [название мира]");
                    return;

                }

                $name = array_shift($args);

                if(!$this->loader->getServer()->getWorldManager()->isWorldLoaded($name))
                    $this->loader->getServer()->getWorldManager()->loadWorld($name);

                $world = $this->loader->getServer()->getWorldManager()->getWorldByName($name);
                if($world Instanceof World){

                    $world->loadChunk($world->getSafeSpawn()->getFloorX() >> 4, $world->getSafeSpawn()->getFloorZ() >> 4);
                    $sender->teleport($world->getSafeSpawn());

                }

                break;

            case "shop":

                $villager = new Villager($sender->getLocation(), EntityDataHelper::createBaseNBT($sender->getPosition(), new Vector3(0, 0, 0), $sender->getLocation()->yaw, $sender->getLocation()->pitch));
                $villager->setNameTag("Торговец");
                $villager->setNameTagVisible();
                $villager->setNameTagAlwaysVisible();

                $villager->spawnToAll();

                break;

            case "teleport":

                if(!isset($args[0])){

                    $sender->sendMessage("Используйте: /bw teleport [мод (4:4)]");
                    return;

                }

                $type = array_shift($args);
                $type = str_replace(":", " x ", $type);

                $npc = new Human($sender->getLocation(), $sender->getSkin(), EntityDataHelper::createBaseNBT($sender->getPosition(), new Vector3(0, 0, 0), $sender->getLocation()->yaw, $sender->getLocation()->pitch));
                $npc->setNameTag("BedWars Join\n".$type);
                $npc->setNameTagAlwaysVisible(true);
                $npc->setNameTagVisible();

                $npc->spawnToAll();

                break;

            case "generate":

                if(!isset($args[0])){

                    $sender->sendMessage("Используйте: /bw generate");
                    return;

                }

                $name = array_shift($args);
                if($this->loader->getServer()->getWorldManager()->getWorldByName($name) Instanceof World){

                    $sender->sendMessage("Такой мир уже есть!");
                    return;

                }

                $this->loader->getServer()->getWorldManager()->generateWorld($name, null, Flat::class);
                $sender->sendMessage("Вы успешно сгенерировали новый мир!");

                break;

        }

    }

}