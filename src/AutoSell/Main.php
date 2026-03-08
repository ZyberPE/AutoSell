<?php

declare(strict_types=1);

namespace AutoSell;

use pocketmine\plugin\PluginBase;
use pocketmine\event\Listener;
use pocketmine\event\block\BlockBreakEvent;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;

use pocketmine\player\Player;

use onebone\economyapi\EconomyAPI;

class Main extends PluginBase implements Listener {

    private array $enabled = [];

    public function onEnable(): void{
        $this->saveDefaultConfig();
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
    }

    /* -------------------------
       COMMAND
    --------------------------*/

    public function onCommand(CommandSender $sender, Command $cmd, string $label, array $args): bool{

        if(!$sender instanceof Player){
            return true;
        }

        $msg = $this->getConfig()->get("messages");

        if(!$sender->hasPermission("autosell.use")){
            $sender->sendMessage($msg["no-permission"]);
            return true;
        }

        if(!isset($args[0])){
            $sender->sendMessage($msg["usage"]);
            return true;
        }

        switch(strtolower($args[0])){

            case "on":
                $this->enabled[$sender->getName()] = true;
                $sender->sendMessage($msg["enabled"]);
            break;

            case "off":
                unset($this->enabled[$sender->getName()]);
                $sender->sendMessage($msg["disabled"]);
            break;

            default:
                $sender->sendMessage($msg["usage"]);
            break;
        }

        return true;
    }

    /* -------------------------
       BLOCK SELL
    --------------------------*/

    public function onBreak(BlockBreakEvent $event): void{

        $player = $event->getPlayer();

        if(!isset($this->enabled[$player->getName()])){
            return;
        }

        $block = $event->getBlock();

        // convert block name to config format
        $name = strtolower(str_replace(" ", "_", $block->getName()));

        $prices = $this->getConfig()->get("sell-prices");

        if(!isset($prices[$name])){
            return;
        }

        $price = (float)$prices[$name];

        // prevent drops completely
        $event->setDrops([]);
        $event->setXpDropAmount(0);

        // give money
        EconomyAPI::getInstance()->addMoney($player, $price);

        // subtitle message
        $subtitle = str_replace(
            ["{block}", "{amount}"],
            [$block->getName(), $price],
            $this->getConfig()->get("subtitle-format")
        );

        // send subtitle correctly for API 5
        $player->sendTitle("", $subtitle, 5, 20, 5);
    }
}
