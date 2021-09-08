<?php
namespace BW\shop;

use BW\arena\player\WarsPlayer;
use BW\Loader;
use BW\utils\Colors;
use muqsit\invmenu\InvMenu;
use muqsit\invmenu\transaction\DeterministicInvMenuTransaction;
use muqsit\invmenu\transaction\InvMenuTransaction;
use muqsit\invmenu\transaction\InvMenuTransactionResult;
use pocketmine\block\Wall;
use pocketmine\item\Armor;
use pocketmine\item\enchantment\Enchantment;
use pocketmine\item\enchantment\EnchantmentInstance;
use pocketmine\item\enchantment\VanillaEnchantments;
use pocketmine\item\Item;
use pocketmine\item\ItemFactory;
use pocketmine\item\ItemIds;
use pocketmine\world\sound\XpCollectSound;

class Shop{

    /** @var Loader */
    public Loader $loader;


    /**
     * Shop constructor.
     * @param Loader $loader
     */
    function __construct(Loader $loader){

        $this->loader = $loader;

    }


    /**
     * @param WarsPlayer $player
     * @param InvMenu|null $menu
     */
    function addMenu(WarsPlayer $player, InvMenu $menu = null){

        $was = true;
        if($menu == null){

            $menu = InvMenu::create(InvMenu::TYPE_CHEST);
            $menu->setName("Магазин");
            $was = false;

        }

        $inventory = $menu->getInventory();
        $inventory->setContents([

            ItemFactory::getInstance()->get(ItemIds::GOLD_SWORD, 0, 1), // +
            ItemFactory::getInstance()->get(ItemIds::BOW, 0, 1), // +
            ItemFactory::getInstance()->get(ItemIds::IRON_PICKAXE, 0, 1), // +
            ItemFactory::getInstance()->get(ItemIds::IRON_CHESTPLATE, 0, 1), // +
            ItemFactory::getInstance()->get(ItemIds::SANDSTONE, 0, 1), // +
            ItemFactory::getInstance()->get(ItemIds::CAKE, 0, 1), // +
            ItemFactory::getInstance()->get(ItemIds::POTION, 0, 1), // -
            ItemFactory::getInstance()->get(ItemIds::FIREWORKS, 0, 1) // +

        ]);

        if(!$was)
            $menu->send($player);
        else
            $menu->sendInventory($player);

        $listener = InvMenu::readonly(function(DeterministicInvMenuTransaction $transaction) use ($menu): void{

            $holder = $transaction->getPlayer();
            if($holder Instanceof WarsPlayer) {

                $item = $transaction->getItemClicked();

                if ($item->getId() == ItemIds::GOLD_SWORD) {

                    $this->addSwords($holder, $menu);

                }elseif($item->getId() == ItemIds::SANDSTONE){

                    $this->addBlocks($holder, $menu);

                }elseif($item->getId() == ItemIds::IRON_PICKAXE){

                    $this->addPickaxe($holder, $menu);

                }elseif($item->getId() == ItemIds::BOW){

                    $this->addBows($holder, $menu);

                }elseif($item->getId() == ItemIds::CAKE){

                    $this->addFood($holder, $menu);

                }elseif($item->getId() == ItemIds::IRON_CHESTPLATE){

                    $this->addEquipment($holder, $menu);

                }elseif($item->getId() == ItemIds::FIREWORKS){

                    $this->addFun($holder, $menu);

                }

            }

        });

        $menu->setListener($listener);

    }



    /**
     * @param WarsPlayer $player
     * @param InvMenu $menu
     */
    function addSwords(WarsPlayer $player, InvMenu $menu){

        $factory = ItemFactory::getInstance();

        $stick = $factory->get(ItemIds::STICK, 0, 1);
        $stick->addEnchantment(new EnchantmentInstance(VanillaEnchantments::KNOCKBACK(), 1));
        $stick->setCustomName("Палочка-откидывалочка");
        $stick->setLore(["15", "материалов"]);

        $sword_1 = $factory->get(ItemIds::GOLD_SWORD, 0, 1);
        $sword_1->addEnchantment(new EnchantmentInstance(VanillaEnchantments::UNBREAKING(), 10));
        $sword_1->setLore(["25", "материалов"]);

        $sword_2 = $factory->get(ItemIds::GOLD_SWORD, 0, 1);
        $sword_2->addEnchantment(new EnchantmentInstance(VanillaEnchantments::UNBREAKING(), 10));
        $sword_2->addEnchantment(new EnchantmentInstance(VanillaEnchantments::SHARPNESS(), 2));
        $sword_2->setLore(["50", "материалов"]);

        $sword_3 = $factory->get(ItemIds::GOLD_SWORD, 0, 1);
        $sword_3->addEnchantment(new EnchantmentInstance(VanillaEnchantments::UNBREAKING(), 10));
        $sword_3->addEnchantment(new EnchantmentInstance(VanillaEnchantments::SHARPNESS(), 3));
        $sword_3->setLore(["150", "материалов"]);

        $map = $factory->get(ItemIds::MAP, 0, 1);
        $map->setLore(["Назад"]);

        $menu->getInventory()->setContents([

            $stick,
            $sword_1,
            $sword_2,
            $sword_3,
            $map


        ]);

        $menu->sendInventory($player);
        $listener = InvMenu::readonly(function(DeterministicInvMenuTransaction $transaction) use ($menu): void{

            $holder = $transaction->getPlayer();
            if($holder Instanceof WarsPlayer) {

                $item = $transaction->getItemClicked();

                if(count($item->getLore()) == 1){

                    $this->addMenu($holder, $menu);

                }else{

                    if($holder->getManager()->getResources() >= (int)$item->getLore()[0]){

                        $holder->getInventory()->addItem($item);
                        $holder->getManager()->resources -= (int)$item->getLore()[0];
                        $holder->getXpManager()->setXpLevel($holder->getManager()->getResources());
                        $holder->getWorld()->addSound($holder->getPosition(), new XpCollectSound());

                    }

                }

            }

        });
        $menu->setListener($listener);

    }



    /**
     * @param WarsPlayer $player
     * @param InvMenu $menu
     */
    function addBlocks(WarsPlayer $player, InvMenu $menu){

        $factory = ItemFactory::getInstance();
        $sandstone_1 = $factory->get(ItemIds::SANDSTONE, 2, 32);
        $sandstone_1->setLore(["20", "материалов"]);

        $sandstone_2 = $factory->get(ItemIds::SANDSTONE, 2, 64);
        $sandstone_2->setLore(["35", "материалов"]);

        $enderstone = $factory->get(ItemIds::END_STONE, 0, 16);
        $enderstone->setLore(["40", "материалов"]);

        $map = $factory->get(ItemIds::MAP, 0, 1);
        $map->setLore(["Назад"]);

        $menu->getInventory()->setContents([

            $sandstone_1,
            $sandstone_2,
            $enderstone,
            $map


        ]);

        $menu->sendInventory($player);
        $listener = InvMenu::readonly(function(DeterministicInvMenuTransaction $transaction) use ($menu): void{

            $holder = $transaction->getPlayer();
            if($holder Instanceof WarsPlayer) {

                $item = $transaction->getItemClicked();

                if(count($item->getLore()) == 1){

                    $this->addMenu($holder, $menu);

                }else{

                    if($holder->getManager()->getResources() >= (int)$item->getLore()[0]){

                        $holder->getInventory()->addItem($item);
                        $holder->getManager()->resources -= (int)$item->getLore()[0];
                        $holder->getXpManager()->setXpLevel($holder->getManager()->getResources());
                        $holder->getWorld()->addSound($holder->getPosition(), new XpCollectSound());

                    }

                }

            }

        });
        $menu->setListener($listener);

    }



    /**
     * @param WarsPlayer $player
     * @param InvMenu $menu
     */
    function addPickaxe(WarsPlayer $player, InvMenu $menu){

        $factory = ItemFactory::getInstance();

        $stone_pickaxe = $factory->get(ItemIds::STONE_PICKAXE, 0, 1);
        $stone_pickaxe->addEnchantment(new EnchantmentInstance(VanillaEnchantments::EFFICIENCY(), 1));
        $stone_pickaxe->setLore(["25", "материалов"]);

        $iron_pickaxe_1 = $factory->get(ItemIds::IRON_PICKAXE, 0, 1);
        $iron_pickaxe_1->addEnchantment(new EnchantmentInstance(VanillaEnchantments::EFFICIENCY(), 1));
        $iron_pickaxe_1->setLore(["50", "материалов"]);

        $iron_pickaxe_2 = $factory->get(ItemIds::IRON_PICKAXE, 0, 1);
        $iron_pickaxe_2->addEnchantment(new EnchantmentInstance(VanillaEnchantments::EFFICIENCY(), 2));
        $iron_pickaxe_2->setLore(["100", "материалов"]);

        $map = $factory->get(ItemIds::MAP, 0, 1);
        $map->setLore(["Назад"]);

        $menu->getInventory()->setContents([$stone_pickaxe, $iron_pickaxe_1, $iron_pickaxe_2, $map]);
        $menu->sendInventory($player);
        $listener = InvMenu::readonly(function(DeterministicInvMenuTransaction $transaction) use ($menu): void {

            $holder = $transaction->getPlayer();
            if ($holder instanceof WarsPlayer) {

                $item = $transaction->getItemClicked();

                if (count($item->getLore()) == 1) {

                    $this->addMenu($holder, $menu);

                } else {

                    if($holder->getManager()->getResources() >= (int)$item->getLore()[0]){

                        $holder->getInventory()->addItem($item);
                        $holder->getManager()->resources -= (int)$item->getLore()[0];
                        $holder->getXpManager()->setXpLevel($holder->getManager()->getResources());
                        $holder->getWorld()->addSound($holder->getPosition(), new XpCollectSound());

                    }

                }

            }

        });
        $menu->setListener($listener);

    }



    /**
     * @param WarsPlayer $player
     * @param InvMenu $menu
     */
    function addBows(WarsPlayer $player, InvMenu $menu){

        $factory = ItemFactory::getInstance();
        $bow_1 = $factory->get(ItemIds::BOW, 0, 1);
        $bow_1->addEnchantment(new EnchantmentInstance(VanillaEnchantments::INFINITY(), 1));
        $bow_1->setLore(["150", "материалов"]);

        $bow_2 = $factory->get(ItemIds::BOW, 0, 1);
        $bow_2->addEnchantment(new EnchantmentInstance(VanillaEnchantments::INFINITY(), 1));
        $bow_2->addEnchantment(new EnchantmentInstance(VanillaEnchantments::POWER(), 1));
        $bow_2->setLore(["250", "материалов"]);

        $bow_3 = $factory->get(ItemIds::BOW, 0, 1);
        $bow_3->addEnchantment(new EnchantmentInstance(VanillaEnchantments::INFINITY(), 1));
        $bow_3->addEnchantment(new EnchantmentInstance(VanillaEnchantments::POWER(), 1));
        $bow_3->addEnchantment(new EnchantmentInstance(VanillaEnchantments::PUNCH(), 1));
        $bow_3->setLore(["450", "материалов"]);

        $arrow = $factory->get(ItemIds::ARROW, 0, 1);
        $arrow->setLore(["50", "материалов"]);

        $map = $factory->get(ItemIds::MAP, 0, 1);
        $map->setLore(["Назад"]);

        $menu->getInventory()->setContents([$bow_1, $bow_2, $bow_3, $arrow, $map]);
        $menu->sendInventory($player);
        $listener = InvMenu::readonly(function(DeterministicInvMenuTransaction $transaction) use ($menu): void {

            $holder = $transaction->getPlayer();
            if ($holder instanceof WarsPlayer) {

                $item = $transaction->getItemClicked();

                if (count($item->getLore()) == 1) {

                    $this->addMenu($holder, $menu);

                } else {

                    if($holder->getManager()->getResources() >= (int)$item->getLore()[0]){

                        $holder->getInventory()->addItem($item);
                        $holder->getManager()->resources -= (int)$item->getLore()[0];
                        $holder->getXpManager()->setXpLevel($holder->getManager()->getResources());
                        $holder->getWorld()->addSound($holder->getPosition(), new XpCollectSound());

                    }

                }

            }
        });
        $menu->setListener($listener);

    }



    /**
     * @param WarsPlayer $player
     * @param InvMenu $menu
     */
    function addFood(WarsPlayer $player, InvMenu $menu){

        $factory = ItemFactory::getInstance();
        $apple = $factory->get(ItemIds::APPLE, 0, 3);
        $apple->setLore(["1", "материал"]);

        $potato = $factory->get(ItemIds::BAKED_POTATO, 0, 1);
        $potato->setLore(["3", "материала"]);

        $schintel = $factory->get(ItemIds::COOKED_PORKCHOP, 0, 1);
        $schintel->setLore(["2", "материала"]);

        $gapple = $factory->get(ItemIds::GOLDEN_APPLE, 0, 1);
        $gapple->setLore(["100", "материалов"]);

        $map = $factory->get(ItemIds::MAP, 0, 1);
        $map->setLore(["Назад"]);

        $menu->getInventory()->setContents([$apple, $potato, $schintel, $gapple, $map]);
        $listener = InvMenu::readonly(function(DeterministicInvMenuTransaction $transaction) use ($menu): void {

            $holder = $transaction->getPlayer();
            if ($holder instanceof WarsPlayer) {

                $item = $transaction->getItemClicked();

                if (count($item->getLore()) == 1) {

                    $this->addMenu($holder, $menu);

                } else {

                    if($holder->getManager()->getResources() >= (int)$item->getLore()[0]){

                        $holder->getInventory()->addItem($item);
                        $holder->getManager()->resources -= (int)$item->getLore()[0];
                        $holder->getXpManager()->setXpLevel($holder->getManager()->getResources());
                        $holder->getWorld()->addSound($holder->getPosition(), new XpCollectSound());

                    }

                }

            }
        });
        $menu->setListener($listener);


    }



    /**
     * @param WarsPlayer $player
     * @param InvMenu $invMenu
     */
    function addEquipment(WarsPlayer $player, InvMenu $invMenu){

        $factory = ItemFactory::getInstance();
        $color = Colors::getColor($player->getManager()->team);

        $helmet = $factory->get(ItemIds::LEATHER_CAP, 0, 1);
        $helmet->setCustomColor($color);
        $helmet->setLore(["5", "материалов"]);

        $leggings = $factory->get(ItemIds::LEATHER_LEGGINGS, 0, 1);
        $leggings->setCustomColor($color);
        $leggings->setLore(["5", "материалов"]);

        $boots = $factory->get(ItemIds::LEATHER_BOOTS, 0, 1);
        $boots->setCustomColor($color);
        $boots->setLore(["5", "материалов"]);

        $chestplate_1 = $factory->get(ItemIds::CHAINMAIL_CHESTPLATE, 0, 1);
        $chestplate_1->addEnchantment(new EnchantmentInstance(VanillaEnchantments::PROTECTION(), 1));
        $chestplate_1->setLore(["50", "материалов"]);

        $chestplate_2 = $factory->get(ItemIds::CHAINMAIL_CHESTPLATE, 0, 1);
        $chestplate_2->addEnchantment(new EnchantmentInstance(VanillaEnchantments::PROTECTION(), 2));
        $chestplate_2->setLore(["150", "материалов"]);

        $chestplate_3 = $factory->get(ItemIds::CHAINMAIL_CHESTPLATE, 0, 1);
        $chestplate_3->addEnchantment(new EnchantmentInstance(VanillaEnchantments::PROTECTION(), 3));
        $chestplate_3->setLore(["300", "материалов"]);

        $map = $factory->get(ItemIds::MAP, 0, 1);
        $map->setLore(["Назад"]);

        $invMenu->getInventory()->setContents([$boots, $leggings, $chestplate_1, $chestplate_2, $chestplate_3, $helmet, $map]);
        $listener = InvMenu::readonly(function(DeterministicInvMenuTransaction $transaction) use ($invMenu): void {

            $holder = $transaction->getPlayer();
            if ($holder instanceof WarsPlayer) {

                $item = $transaction->getItemClicked();

                if (count($item->getLore()) == 1) {

                    $this->addMenu($holder, $invMenu);

                } else {

                    if($holder->getManager()->getResources() >= (int)$item->getLore()[0]){

                        if($item Instanceof Armor) {

                            $holder->sendEquipment($item);
                            $holder->getManager()->resources -= (int)$item->getLore()[0];
                            $holder->getXpManager()->setXpLevel($holder->getManager()->getResources());
                            $holder->getWorld()->addSound($holder->getPosition(), new XpCollectSound());

                        }
                    }

                }

            }
        });
        $invMenu->setListener($listener);

    }



    function addFun(WarsPlayer $player, InvMenu $invMenu){

        $factory = ItemFactory::getInstance();

        $platform = $factory->get(ItemIds::SLIME_BLOCK, 0, 1);
        $platform->setCustomName("Слайм-платформа");
        $platform->setLore(["300", "материалов"]);

        $ender_pearl = $factory->get(ItemIds::ENDER_PEARL, 0, 1);
        $ender_pearl->setLore(["400", "материалов"]);

        $come_back = $factory->get(ItemIds::SUGAR, 0, 1);
        $come_back->setCustomName("Возвращение на базу");
        $come_back->setLore(["200", "материалов"]);

        $ladder = $factory->get(ItemIds::LADDER, 0, 3);
        $ladder->setLore(["50", "материалов"]);

        $rocket = $factory->get(ItemIds::FIREBALL, 0, 1);
        $rocket->setCustomName("Ракета");
        $rocket->setLore(["250", "материалов"]);

        $thread = $factory->get(ItemIds::COBWEB, 0, 1);
        $thread->setLore(["50", "материалов"]);

        $villager = $factory->get(ItemIds::MAGMA_CREAM, 0, 1);
        $villager->setCustomName("Переносной житель");
        $villager->setLore(["250", "материалов"]);

        $boots = $factory->get(ItemIds::CHAIN_BOOTS, 0, 1);
        $boots->setLore(["120", "материалов"]);

        $map = $factory->get(ItemIds::MAP, 0, 1);
        $map->setLore(["Назад"]);

        $invMenu->getInventory()->setContents([$platform, $ender_pearl, $ladder, $come_back, $rocket, $villager, $thread, $map]);
        $listener = InvMenu::readonly(function(DeterministicInvMenuTransaction $transaction) use ($invMenu): void {

            $holder = $transaction->getPlayer();
            if ($holder instanceof WarsPlayer) {

                $item = $transaction->getItemClicked();

                if (count($item->getLore()) == 1) {

                    $this->addMenu($holder, $invMenu);

                } else {

                    if($holder->getManager()->getResources() >= (int)$item->getLore()[0]){

                        $holder->getInventory()->addItem($item);
                        $holder->getManager()->resources -= (int)$item->getLore()[0];
                        $holder->getXpManager()->setXpLevel($holder->getManager()->getResources());
                        $holder->getWorld()->addSound($holder->getPosition(), new XpCollectSound());

                    }

                }

            }
        });
        $invMenu->setListener($listener);

    }

}