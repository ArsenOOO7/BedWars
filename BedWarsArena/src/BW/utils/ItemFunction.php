<?php
namespace BW\utils;

use BW\arena\Arena;
use BW\arena\player\WarsPlayer;
use muqsit\invmenu\InvMenu;
use muqsit\invmenu\transaction\DeterministicInvMenuTransaction;
use pocketmine\block\Block;
use pocketmine\block\BlockFactory;
use pocketmine\block\VanillaBlocks;
use pocketmine\entity\EntityDataHelper;
use pocketmine\entity\Location;
use pocketmine\entity\object\PrimedTNT;
use pocketmine\entity\Villager;
use pocketmine\item\Bed;
use pocketmine\item\Item;
use pocketmine\item\ItemFactory;
use pocketmine\item\ItemIds;
use pocketmine\math\Vector3;
use pocketmine\world\Position;

class ItemFunction{


    /**
     * @param WarsPlayer $player
     * @param Arena $arena
     */
    static function getTeamsMenu(WarsPlayer $player, Arena $arena){

        $menu = InvMenu::create(InvMenu::TYPE_CHEST);

        $slot = 0;
        foreach($arena->getTeams() as $n => $team){

            $bed = ItemFactory::getInstance()->get(ItemIds::BED, Colors::convertToBed($team->tag), 1);

            $custom_name = "Игроки: ".implode("\n-", $team->getPlayers());
            $bed->setCustomName($custom_name);

            $menu->getInventory()->setItem($slot, $bed);
            ++$slot;

        }

        $menu->send($player);
        $listener = InvMenu::readonly(function(DeterministicInvMenuTransaction $transaction) use ($arena, $menu): void{

            $holder = $transaction->getPlayer();
            $item = $transaction->getItemClicked();

            if($item Instanceof Bed && $holder Instanceof WarsPlayer){

                $name = str_replace(" ", "_", $item->getVanillaName());
                $name = strtolower($name);
                $name = str_replace("_bed", "", $name);

                $team = Colors::convertTagToInt($name);
                if(!$arena->getTeam($team)->canEnter()){

                    $menu->onClose($holder);
                    $holder->sendMessage("Команда переполнена!");

                }else{

                    $menu->onClose($holder);
                    $holder->setTeam($arena, $team);

                }

            }

        });
        $menu->setListener($listener);

    }


    /**
     * @param WarsPlayer $player
     * @param Item $item
     */
    static function startRocket(WarsPlayer $player, Item $item){

        $x = -sin($player->getLocation()->yaw / 180 * M_PI) * cos($player->getLocation()->pitch / 180 * M_PI);
        $y = -sin($player->getLocation()->pitch / 180 * M_PI);
        $z = cos($player->getLocation()->yaw / 180 * M_PI) * cos($player->getLocation()->pitch / 180 * M_PI);

        $primedTNT = new PrimedTNT($player->getLocation(), EntityDataHelper::createBaseNBT($player->getLocation()));
        $primedTNT->setMotion(new Vector3($x, $y, $z));
        $primedTNT->spawnToAll();

        $player->getInventory()->removeItem(ItemFactory::getInstance()->get($item->getId(), 0, 1));

    }



    /**
     * @param WarsPlayer $player
     * @param Arena $arena
     * @param Item $item
     */
    static function teleportSpawn(WarsPlayer $player, Arena $arena, Item $item){

        if(isset($arena->teleport[strtolower($player->getName())]))
            return;

        $arena->teleport[strtolower($player->getName())] = time() + 5;
        $player->sendMessage("Стойте неподвижно!");

        $player->getInventory()->removeItem(ItemFactory::getInstance()->get($item->getId(), 0, 1));

    }


    /**
     * @param WarsPlayer $player
     * @param Arena $arena
     * @param Item $item
     */
    static function setPlatform(WarsPlayer $player, Arena $arena, Item $item){

        $pos_1 = new Vector3($player->getPosition()->getFloorX() + 3, $player->getPosition()->getFloorY() - 2, $player->getPosition()->getFloorZ() + 3);
        $pos_2 = new Vector3($player->getPosition()->getFloorX() - 3, $player->getPosition()->getFloorY() - 2, $player->getPosition()->getFloorZ() - 3);

        $platform = [];
        for($x = min($pos_1->x, $pos_2->x); $x <= max($pos_1->x, $pos_2->x); ++$x){

            for($z = min($pos_1->z, $pos_2->z); $z <= max($pos_1->z, $pos_2->z); ++$z){

                $block = BlockFactory::getInstance()->get(165, 0);
                if($block Instanceof Block){

                    $that = $player->getWorld()->getBlockAt($x, $pos_1->y, $z);
                    if($that->getId() == 0 or $arena->loader->metadata->hasData($that, $arena->name)) {

                        $player->getWorld()->setBlockAt($x, $pos_1->y, $z, $block);
                        $platform["block"][] = new Position($x, $pos_1->y, $z, $player->getWorld());

                    }

                }



            }

        }

        $platform["time"] = time() + 15;
        $arena->platform[] = $platform;

        $player->getInventory()->removeItem(ItemFactory::getInstance()->get($item->getId(), 0, 1));

    }



    /**
     * @param WarsPlayer $player
     * @param Arena $arena
     * @param Item $item
     * @param Position $clicked
     */
    static function spawnVillager(WarsPlayer $player, Arena $arena, Item $item, Position $clicked){

        $location = new Location($clicked->x, $clicked->y + 1, $clicked->z, 0.0, 0.0, $clicked->getWorld());
        $nbt = EntityDataHelper::createBaseNBT($clicked->add(0, 1, 0), new Vector3(0, 0, 0));
        $villager = new Villager($location, $nbt);

        $villager->setNameTag("Переносной торговец");
        $villager->setNameTagVisible();
        $villager->setNameTagAlwaysVisible();

        $villager->setImmobile(true);

        $villager->spawnToAll();

        $arena->villagers[$villager->getId()] = time() + 30;
        $player->getInventory()->removeItem(ItemFactory::getInstance()->get($item->getId(), 0, 1));

    }


    /**
     * @param WarsPlayer $victim
     * @param WarsPlayer $damager
     * @return bool
     */
    static function knockback(WarsPlayer $victim, WarsPlayer $damager): bool{

        if($victim->getArmorInventory()->getBoots()->getId() == ItemIds::CHAIN_BOOTS){

            $chance = mt_rand(0, 100);
            if($chance <= 95)
                return false;

        }

        if($damager->getInventory()->getItemInHand()->getId() == ItemIds::STICK){

            $chance = mt_rand(0, 100);
            if($chance % 9 != 0)
                return false;

        }


        return true;

    }

}