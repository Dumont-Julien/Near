<?php

namespace NewTheBuilder\Near\Command;

use NewTheBuilder\Near\Main;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\network\mcpe\protocol\PlaySoundPacket;
use pocketmine\player\Player;
use pocketmine\utils\Config;


class NearCommand extends Command {

    private static array $near;

    public function __construct() {
        parent::__construct("");
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args) {

        $config = new Config(Main::getInstance()->getDataFolder() . "Config.yml", Config::YAML);

        if ($sender instanceof Player) {
            if (!$sender->hasPermission("near.command.use")) {
                $sender->sendMessage($config->get("NoPermission"));
                return true;
            }

            $boundingBox = $sender->getBoundingBox();
            $count = 0;
            $playerMessage = "";
            $pos = $sender->getPosition();
            if ($config->get("cooldown") === "enable") {
                $time = time() + 60 * $config->get("cooldown_time");
            }
            if (!isset(self::$near[$sender->getName()]) or self::$near[$sender->getName()] - time() <= 0) {
                foreach ($sender->getWorld()->getNearbyEntities($boundingBox->expandedCopy($config->get("block"), $config->get("block"), $config->get("block")), $sender) as $entity) {
                    if ($entity instanceof Player) {
                        $count++;
                        $playerMessage .= "\n" . $config->get("Color") . $entity->getName() . " --> " . $config->get("Color") . (int)($pos->distance($entity->getPosition())) . " block(s)§f.";
                    }
                }
                if ($count === 0) {
                    $sender->sendMessage($config->get("Prefix") . str_replace("{BLOCK}", $config->get("block"), $config->get("No_Player_Around_You")));
                    return;
                }
                $sender->sendMessage($config->get("Prefix") . str_replace("{COUNT}", $count, $config->get("Player_Around")));
                $sender->sendMessage($playerMessage);
                if ($config->get("cooldown") === "enable"){
                    self::$near[$sender->getName()] = $time;
                }
            } else {
                $timer = intval(self::$near[$sender->getName()] - time());
                $minutes = intval(abs($timer / 60));
                $secondes = intval(abs($timer - $minutes * 60));
                if ($minutes > 0) {
                    $TempRestant = $config->get("Color") . "{$minutes} minute(s)";
                } else {
                    $TempRestant = $config->get("Color") . "{$secondes} seconde(s)";
                }
                $sender->sendMessage($config->get("Prefix") . $config->get("Color") . str_replace("{TIME}", $TempRestant, $config->get("Time_remaining")));
                $pos = $sender->getPosition();
                $sender->getNetworkSession()->sendDataPacket(PlaySoundPacket::create(
                    "note.bass",
                    $pos->x, $pos->y, $pos->z,
                    1.0, 1.0
                ));
            }
        }else{
            $sender->sendMessage($config->get("Console_Command"));
        }
        return true;
    }
}