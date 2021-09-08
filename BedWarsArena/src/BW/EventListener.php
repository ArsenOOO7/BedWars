<?php
namespace BW;

use alemiz\sga\StarGateAtlantis;
use BW\arena\player\WarsPlayer;
use BW\packet\ServerCommunicate;
use BW\utils\Colors;
use BW\utils\ItemFunction;
use pocketmine\block\Block;
use pocketmine\block\inventory\ChestInventory;
use pocketmine\block\tile\Bed;
use pocketmine\block\VanillaBlocks;
use pocketmine\entity\projectile\Arrow;
use pocketmine\entity\Villager;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\entity\EntityDamageByChildEntityEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntityExplodeEvent;
use pocketmine\event\entity\EntityTeleportEvent;
use pocketmine\event\inventory\CraftItemEvent;
use pocketmine\event\inventory\InventoryPickupArrowEvent;
use pocketmine\event\inventory\InventoryPickupItemEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerChangeSkinEvent;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\event\player\PlayerCreationEvent;
use pocketmine\event\player\PlayerDropItemEvent;
use pocketmine\event\player\PlayerExhaustEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerItemUseEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\inventory\PlayerInventory;
use pocketmine\item\Item;
use pocketmine\item\ItemFactory;
use pocketmine\item\ItemIds;
use pocketmine\network\mcpe\JwtUtils;
use pocketmine\network\mcpe\protocol\LoginPacket;
use pocketmine\network\mcpe\protocol\PlaySoundPacket;

class EventListener implements Listener{

    /** @var Loader */
    private Loader $loader;


    /**
     * EventListener constructor.
     * @param Loader $loader
     */
    function __construct(Loader $loader){

        $this->loader = $loader;

    }



    /**
     * @param PlayerJoinEvent $event
     */
    function onJoin(PlayerJoinEvent $event){

        $player = $event->getPlayer();
        if(!$player Instanceof WarsPlayer)
            return;

        $arena = $this->loader->arena;

        if($arena == null){

            $player->sendMessage("Зарегай арену, дибил");
            return;

        }

        if($arena->getState() > 0){

            $player->transfer("localhost", 19132);
            return;

        }

        if(count($arena->getPlayers()) == $arena->slots){

            $player->transfer("localhost", 19132);
            return;

        }

        $player->joinArena($arena);
        $event->setJoinMessage("");

    }




    /**
     * @param PlayerCreationEvent $event
     */
    function onPlayerCreation(PlayerCreationEvent $event){

        $event->setPlayerClass(WarsPlayer::class);

    }





    /**
     * @param BlockPlaceEvent $event
     */
    function onPlaceBlock(BlockPlaceEvent $event){

        $player = $event->getPlayer();
        if(!$player Instanceof WarsPlayer)
            return;

        if($this->loader->arena == null)
            return;

        $arena = $this->loader->arena;
        if($arena->getState() < 2){

            $event->cancel();

        }else{

            ++$player->getManager()->place;
            $this->loader->metadata->addData($event->getBlock());

        }

    }



    /**
     * @param BlockBreakEvent $event
     */
    function onBreakBlock(BlockBreakEvent $event){

        $player = $event->getPlayer();
        if(!$player Instanceof WarsPlayer)
            return;

        if($this->loader->arena == null)
            return;

        $arena = $this->loader->arena;

        if($arena->getState() < 2) {

            $event->cancel();
            return;

        }

        $block = $event->getBlock();

        if($block->getId() == VanillaBlocks::BED()->getId()){

            $tile = $block->getPos()->getWorld()->getTile($block->getPos());
            if($tile Instanceof Bed){

                $bed_team = Colors::convertTagToInt($tile->getColor()->name());
                $player_team = $player->getManager()->team;

                if($bed_team == $player_team){

                    $event->cancel();
                    $player->sendMessage("Вы не можете ломать свою кровать!");

                }else{

                    $event->setDrops([]);
                    $player->getManager()->addBeds();
                    $arena->getTeam($bed_team)->destroyBed();
                    $player->getManager()->reward += 50;

                    $arena->broadcast("Игрок {$player->getName()} сломал кровать команде ".Colors::getCode($bed_team).Colors::languageConvert($bed_team));
                    foreach($arena->getTeam($bed_team)->getPlayers() as $player_name){

                        $arena_player = $this->loader->getServer()->getPlayerExact($player_name);
                        if(!$arena_player Instanceof WarsPlayer)
                            continue;

                        $arena_player->sendTitle("Твоя кровать сломана!", "Береги себя");
//                        if($arena_player->hasPermission("future.totem")){
//
//                           $effect = new EffectInstance(VanillaEffects::ABSORPTION());
//                           $effect->setAmplifier(10);
//                           $effect->setDuration(19999999999);
//
//                           $arena_player->getEffects()->add($effect);
//
//                           $effect = new EffectInstance(VanillaEffects::RESISTANCE());
//                           $effect->setAmplifier(5);
//                           $effect->setDuration(20 * 10);
//
//                           $arena_player->getEffects()->add($effect);
//                           $arena->resistance[$player_name] = time() + 10;
//
//                        }

                    }

                }

            }

            return;

        }

        if(!$this->loader->metadata->hasData($block))
            $event->cancel();
        else
            ++$player->getManager()->breaks;

    }



    /**
     * @param EntityDamageEvent $event
     */
    function onEntityDamage(EntityDamageEvent $event){

        $player = $event->getEntity();

        if($this->loader->arena == null)
            return;

        if(!$player Instanceof WarsPlayer){

            if($event->getCause() == EntityDamageEvent::CAUSE_ENTITY_EXPLOSION or $event->getCause() == EntityDamageEvent::CAUSE_BLOCK_EXPLOSION)
                $event->cancel();

            if($event Instanceof EntityDamageByEntityEvent and $event->getCause() == EntityDamageEvent::CAUSE_ENTITY_ATTACK){

                $damager = $event->getDamager();

                if(!$damager Instanceof WarsPlayer)
                    return;

                $event->cancel();


                $arena = $this->loader->arena;

                if($arena->getState() < 2)
                    return;

                if(in_array(strtolower($damager->getName()), $arena->spectators))
                    return;

                if($player Instanceof Villager) {

                    $this->loader->shop->addMenu($damager);

                }
            }

        }

        if(!$player Instanceof WarsPlayer) {

            $event->cancel();
            return;

        }


        $arena = $this->loader->arena;
        if($arena->getState() < 2){

            $event->cancel();
            return;

        }

        if($event Instanceof EntityDamageByEntityEvent){

            $damager = $event->getDamager();
            if(!$damager Instanceof WarsPlayer)
                return;

            if($damager->getManager()->team == $player->getManager()->team){

                $event->cancel();
                return;

            }

            if(!ItemFunction::knockback($player, $damager))
                $event->setKnockBack(0);

            if($event->getFinalDamage() >= $player->getHealth()){

                $event->cancel();
                $player->death($arena);
                $arena->broadcast("Игрок {$damager->getName()} убил игрока {$player->getName()}");

                ++$player->getManager()->deaths;
                ++$damager->getManager()->kills;
                $damager->getManager()->reward += 7;

            }else{

                $player->getManager()->last_attacker = [time(), $damager->getName()];

            }

        }elseif($event Instanceof EntityDamageByChildEntityEvent){

            $damager = $event->getDamager();
            if(!$damager Instanceof WarsPlayer)
                return;


            if($damager->getManager()->team == $player->getManager()->team){

                $event->cancel();
                return;

            }

            if($event->getChild() Instanceof Arrow){

                if(!ItemFunction::knockback($player, $damager))
                    $event->setKnockBack(0);

                $sound = new PlaySoundPacket();
                $sound->x = $damager->getPosition()->getX();
                $sound->y = $damager->getPosition()->getY();
                $sound->z = $damager->getPosition()->getZ();
                $sound->volume = 1;
                $sound->pitch = 1;
                $sound->soundName = "random.toast";
                $damager->getNetworkSession()->sendDataPacket($sound);

                $damager->getManager()->reward += 3;

                if($event->getFinalDamage() >= $player->getHealth()){

                    $event->cancel();
                    $player->death($arena, $arena->getTeam($player->getManager()->team)->hasBed);
                    $arena->broadcast("Игрок {$damager->getName()} застрелил игрока {$player->getName()}");

                    ++$player->getManager()->deaths;
                    ++$damager->getManager()->kills;
                    ++$damager->getManager()->shoots;

                }else{

                    $player->getManager()->last_attacker = [time(), $damager->getName()];
                    ++$damager->getManager()->shoots;

                }

            }

        }else{

            if($event->getCause() == EntityDamageEvent::CAUSE_FALL){

                if($event->getFinalDamage() >= $player->getHealth()){

                    $event->cancel();
                    $player->death($arena, $arena->getTeam($player->getManager()->team)->hasBed);
                    ++$player->getManager()->deaths;

                    if(!empty($player->getManager()->last_attacker)) {

                        if(isset($player->getManager()->last_attacker[0]) and time() - $player->getManager()->last_attacker[0] <= 10) {

                            $arena->broadcast("Игрок {$player->getName()} разбился убегая от {$player->getManager()->last_attacker[1]}");
                            $damager = $this->loader->getServer()->getPlayerExact($player->getManager()->last_attacker[1]);

                            if(!$damager Instanceof WarsPlayer)
                                return;

                            if(in_array(strtolower($damager->getName()), $arena->spectators))
                                return;

                            ++$damager->getManager()->kills;
                            $damager->getManager()->reward += 7;

                        }else{

                            $arena->broadcast("Игрок {$player->getName()} разбился насмерть");

                        }

                    }else{

                        $arena->broadcast("Игрок {$player->getName()} разбился насмерть");

                    }

                }

            }elseif($event->getCause() == EntityDamageEvent::CAUSE_BLOCK_EXPLOSION){

                if($event->getFinalDamage() >= $player->getHealth()) {

                    $event->cancel();
                    $player->death($arena, $arena->getTeam($player->getManager()->team)->hasBed);
                    ++$player->getManager()->deaths;
                    $arena->broadcast("Игрок {$player->getName()} умер от взрыва");

                }

            }elseif($event->getCause() == EntityDamageEvent::CAUSE_VOID){

                if($event->getFinalDamage() >= $player->getHealth()) {

                    $event->cancel();
                    $player->death($arena, $arena->getTeam($player->getManager()->team)->hasBed);
                    ++$player->getManager()->deaths;

                    if(!empty($player->getManager()->last_attacker)) {

                        if(isset($player->getManager()->last_attacker[0]) and time() - $player->getManager()->last_attacker[0] <= 10) {

                            $arena->broadcast("Игрок {$player->getName()} упал в пропасть убегая от {$player->getManager()->last_attacker[1]}");
                            $damager = $this->loader->getServer()->getPlayerExact($player->getManager()->last_attacker[1]);

                            if(!$damager Instanceof WarsPlayer)
                                return;

                            if(in_array(strtolower($damager->getName()), $arena->spectators))
                                return;

                            ++$damager->getManager()->kills;
                            $damager->getManager()->reward += 7;

                        }else{

                            $arena->broadcast("Игрок {$player->getName()} упал в пропасть");

                        }

                    }else{

                        $arena->broadcast("Игрок {$player->getName()} упал в пропасть");

                    }

                }

            }

        }

    }



    /**
     * @param EntityTeleportEvent $event
     */
    function onTeleport(EntityTeleportEvent $event){

        $player = $event->getEntity();
        if(!$player Instanceof WarsPlayer)
            return;

        if($this->loader->arena == null)
            return;

        if($player->getCurrentWindow() Instanceof ChestInventory){

            $player->getCurrentWindow()->onClose($player);

        }

    }



    /**
     * @param InventoryPickupItemEvent $event
     */
    function onInventoryPickup(InventoryPickupItemEvent $event){

        $inventory = $event->getInventory();

        if($this->loader->arena == null)
            return;

        if($inventory Instanceof PlayerInventory){

            $player = $inventory->getHolder();
            if(!$player Instanceof WarsPlayer)
                return;


            if(!$player->isPlaying($this->loader->arena)){

                $event->cancel();
                return;

            }

            $item = $event->getItemEntity()->getItem();

            if($item->getId() == ItemIds::BRICK){

                $event->cancel();
                $event->getItemEntity()->close();
                $player->getManager()->addResources(3);
                $player->getXpManager()->setXpLevel($player->getManager()->getResources());
                $player->sendTip("+".($item->getCount() * 3));

            }

            if($item->getId() == ItemIds::IRON_INGOT){

                $event->cancel();
                $event->getItemEntity()->close();
                $player->getManager()->addResources(10);
                $player->getXpManager()->setXpLevel($player->getManager()->getResources());
                $player->sendTip("+".($item->getCount() * 10));

            }

            if($item->getId() == ItemIds::GOLD_INGOT){

                $event->cancel();
                $event->getItemEntity()->close();
                $player->getManager()->addResources(20);
                $player->getXpManager()->setXpLevel($player->getManager()->getResources());
                $player->sendTip("+".($item->getCount() * 20));

            }

        }

    }



    /**
     * @param InventoryPickupArrowEvent $event
     */
    function onArrowPickup(InventoryPickupArrowEvent $event){

        $event->cancel();

    }



    /**
     * @param PlayerItemUseEvent $event
     */
    function onUseItem(PlayerItemUseEvent $event){

        $player = $event->getPlayer();

        if($this->loader->arena == null)
            return;

        if(!$player Instanceof WarsPlayer)
            return;

        if(!$this->interact($player, $event->getItem()))
            $event->cancel();

    }



    /**
     * @param PlayerInteractEvent $event
     */
    function onInteract(PlayerInteractEvent $event){

        $player = $event->getPlayer();

        if(!$player Instanceof WarsPlayer)
            return;

        if($this->loader->arena == null)
            return;

        if(!$this->interact($player, $event->getItem(), $event->getBlock()))
            $event->cancel();

    }


    /**
     * @param WarsPlayer $player
     * @param Item $item
     * @param Block|null $block
     * @return bool
     */
    function interact(WarsPlayer $player, Item $item, ?Block $block = null): bool{

        $arena = $this->loader->arena;
        if($arena->getState() < 2){

            if($item->getId() == ItemIds::BED){

                ItemFunction::getTeamsMenu($player, $arena);
                return false;

            }

            if($item->getId() == ItemIds::SLIME_BALL){

                $player->quitArena($arena);
                StarGateAtlantis::getInstance()->transferPlayer($player, "lobby");
                foreach(StarGateAtlantis::getInstance()->getClients() as $client){

                    var_dump($client->getClientName());

                }
                return false;

            }

        }else{

            if(!$player->isPlaying($arena)){

                if($item->getId() == ItemIds::COMPASS){

                    $player->quitArena($arena);
                    return false;

                }

            }else{

                if($item->getId() == ItemIds::SLIME_BLOCK){

                    ItemFunction::setPlatform($player, $arena, $item);

                }elseif($item->getId() == ItemIds::SUGAR){

                    ItemFunction::teleportSpawn($player, $arena, $item);

                }elseif($item->getId() == ItemIds::FIREBALL){

                    ItemFunction::startRocket($player, $item);

                }elseif($item->getId() == ItemIds::MAGMA_CREAM){

                    if($block !== null)
                        ItemFunction::spawnVillager($player, $arena, $item, $block->getPos());

                }

            }

        }

        return true;

    }



    /**
     * @param PlayerDropItemEvent $event
     */
    function onDropItem(PlayerDropItemEvent $event){

        $player = $event->getPlayer();
        if(!$player Instanceof WarsPlayer)
            return;

        if($this->loader->arena == null)
            return;


        if(!$player->isPlaying($this->loader->arena)){

            $event->cancel();
            return;

        }

        if($this->loader->arena->getState() < 2)
            $event->cancel();

    }



    /**
     * @param EntityExplodeEvent $event
     */
    function onExplode(EntityExplodeEvent $event){

        if($this->loader->arena == null)
            return;

        $blocks = $event->getBlockList();
        $list = [];
        foreach($blocks as $block){

            if($this->loader->metadata->hasData($block) and !$block Instanceof \pocketmine\block\Bed){

                $list[] = $block;

            }

        }

        $event->setBlockList($list);

    }






    /**
     * @param PlayerMoveEvent $event
     */
    function onMove(PlayerMoveEvent $event){

        $player = $event->getPlayer();
        if(!$player Instanceof WarsPlayer)
            return;

        if($this->loader->arena == null)
            return;

        $arena = $this->loader->arena;

        if($arena->getState() < 2)
            return;

        if(!$player->isPlaying($arena))
            return;

        if(array_key_exists(strtolower($player->getName()), $arena->teleport)){

            unset($arena->teleport[strtolower($player->getName())]);
            $come_back = ItemFactory::getInstance()->get(ItemIds::SUGAR, 0, 1);
            $come_back->setCustomName("Возвращение на базу");
            $come_back->setLore(["200", "материалов"]);

            $player->getInventory()->addItem($come_back);

        }

        if($player->getPosition()->getY() < 5 && !$arena->getTeam($player->getManager()->team)->hasBed){

            $player->getEffects()->clear();

        }

    }



    /**
     * @param PlayerChatEvent $event
     */
    function onChat(PlayerChatEvent $event){

        $player = $event->getPlayer();
        if(!$player Instanceof WarsPlayer)
            return;

        if($this->loader->arena == null)
            return;

        $arena = $this->loader->arena;
        $message = $event->getMessage();

        $event->cancel();

        if($arena->getState() == 0){

            $arena->broadcast("(".$arena->name.") {$player->getName()} -> {$message}");
            return;

        }


        if(!$player->isPlaying($arena)){

            $arena->broadcast("(".$arena->name.") [Наблюдатель] {$player->getName()} -> {$message}");
            return;

        }

        if($message[0] == "!"){

            $message = substr($message, 1);
            $arena->broadcast("(".$arena->name.") [Всем] ".Colors::getCode($player->getManager()->team)."{$player->getName()} -> {$message}");

        }else{

            $arena->getTeam($player->getManager()->team)->sendMessage($player->getName(), $message);

        }

    }



    /**
     * @param PlayerChangeSkinEvent $event
     */
    function onSkinChange(PlayerChangeSkinEvent $event){

        $event->cancel();

    }



    /**
     * @param CraftItemEvent $event
     */
    function onCraft(CraftItemEvent $event){

        $event->cancel();

    }



    /**
     * @param PlayerExhaustEvent $event
     */
    function onExhaust(PlayerExhaustEvent $event){

        $player = $event->getPlayer();
        if(!$player Instanceof WarsPlayer)
            return;

        if($this->loader->arena == null)
            return;


        $arena = $this->loader->arena;
        if($arena->getState() < 2)
            $event->cancel();

    }



    /**
     * @param PlayerQuitEvent $event
     */
    function onQuit(PlayerQuitEvent $event){

        $player = $event->getPlayer();
        if(!$player Instanceof WarsPlayer)
            return;

        if($this->loader->arena == null)
            return;


        $player->quitArena($this->loader->arena, true);

    }



    /**
     * @param DataPacketReceiveEvent $event
     */
    function onDataReceive(DataPacketReceiveEvent $event){

        $packet = $event->getPacket();
        if($packet instanceof LoginPacket){

            $player = $event->getOrigin()->getPlayer();
            if(!$player instanceof WarsPlayer)
                return;

            $clientData = JwtUtils::parse($packet->clientDataJwt);

            if(isset($clientDataClaims["Waterdog_XUID"]))
                $player->setXUID($clientData["Waterdog_XUID"]);

            if(isset($clientDataClaims["WaterDog_RemoteIP"]))
                $player->setAddress($clientData["WaterDog_RemoteIP"]);

        }

    }

}