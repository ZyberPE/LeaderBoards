<?php

declare(strict_types=1);

namespace StatsLeaderboards;

use pocketmine\plugin\PluginBase;
use pocketmine\event\Listener;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\player\PlayerDeathEvent;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\player\Player;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\utils\Config;
use StatsLeaderboards\task\UpdateTask;
use StatsLeaderboards\task\PlaytimeTask;

class Main extends PluginBase implements Listener{

    private static Main $instance;

    private PlayerDataManager $playerDataManager;
    private LeaderboardManager $leaderboardManager;

    private Config $leaderboardsConfig;

    public static function getInstance() : Main{
        return self::$instance;
    }

    public function onLoad() : void{
        self::$instance = $this;
    }

    public function onEnable() : void{
        $this->saveDefaultConfig();

        @mkdir($this->getDataFolder());

        if(!file_exists($this->getDataFolder() . "players.yml")){
            $cfg = new Config(
                $this->getDataFolder() . "players.yml",
                Config::YAML,
                ["players" => []]
            );
            $cfg->save();
        }

        if(!file_exists($this->getDataFolder() . "leaderboards.yml")){
            $cfg = new Config(
                $this->getDataFolder() . "leaderboards.yml",
                Config::YAML,
                ["leaderboards" => []]
            );
            $cfg->save();
        }

        $this->leaderboardsConfig = new Config(
            $this->getDataFolder() . "leaderboards.yml",
            Config::YAML
        );

        $this->playerDataManager = new PlayerDataManager($this);
        $this->leaderboardManager = new LeaderboardManager($this);

        $this->playerDataManager->load();
        $this->leaderboardManager->load();

        $this->getServer()->getPluginManager()->registerEvents($this, $this);

        $updateInterval = max(
            20,
            (int)$this->getConfig()->get("update-interval", 60) * 20
        );

        $this->getScheduler()->scheduleRepeatingTask(
            new UpdateTask($this),
            $updateInterval
        );

        $this->getScheduler()->scheduleRepeatingTask(
            new PlaytimeTask($this),
            1200 // 60 seconds
        );
    }

    public function onDisable() : void{
        $this->playerDataManager->save();
        $this->leaderboardManager->save();
    }

    public function getPlayerDataManager() : PlayerDataManager{
        return $this->playerDataManager;
    }

    public function getLeaderboardManager() : LeaderboardManager{
        return $this->leaderboardManager;
    }

    public function getLeaderboardsConfig() : Config{
        return $this->leaderboardsConfig;
    }

public function onDeath(PlayerDeathEvent $event) : void{

    $victim = $event->getPlayer();

    $this->playerDataManager->addDeath(
        $victim->getName()
    );

    $cause = $victim->getLastDamageCause();

    if(!$cause instanceof EntityDamageByEntityEvent){
        return;
    }

    $damager = $cause->getDamager();

    if(!$damager instanceof Player){
        return;
    }

    if($damager->getName() === $victim->getName()){
        return;
    }

    $this->playerDataManager->addKill(
        $damager->getName()
    );
}

        $damager = $cause->getDamager();

        if(!$damager instanceof Player){
            return;
        }

        if($damager->getName() === $victim->getName()){
            return;
        }

        $this->playerDataManager->addKill(
            $damager->getName()
        );
    }

    public function onBreak(BlockBreakEvent $event) : void{
        $this->playerDataManager->addBlock(
            $event->getPlayer()->getName()
        );
    }

    public function onCommand(
        CommandSender $sender,
        Command $command,
        string $label,
        array $args
    ) : bool{

        $name = strtolower($command->getName());

        switch($name){

            case "topkills":
                return $this->handleLeaderboardCommand(
                    $sender,
                    "kills",
                    $args
                );

            case "topdeaths":
                return $this->handleLeaderboardCommand(
                    $sender,
                    "deaths",
                    $args
                );

            case "topplaytime":
                return $this->handleLeaderboardCommand(
                    $sender,
                    "playtime",
                    $args
                );

            case "topblocks":
                return $this->handleLeaderboardCommand(
                    $sender,
                    "blocks",
                    $args
                );

            case "stats":

                if(isset($args[0])){
                    $target = $args[0];
                }else{
                    if(!$sender instanceof Player){
                        $sender->sendMessage("Specify a player.");
                        return true;
                    }

                    $target = $sender->getName();
                }

                $kills = $this->playerDataManager->getKills($target);
                $deaths = $this->playerDataManager->getDeaths($target);
                $blocks = $this->playerDataManager->getBlocks($target);
                $playtime = $this->playerDataManager->getPlaytime($target);

                $sender->sendMessage("§6Stats for §e" . $target);
                $sender->sendMessage("§7Kills: §f" . $kills);
                $sender->sendMessage("§7Deaths: §f" . $deaths);
                $sender->sendMessage("§7Blocks Broken: §f" . number_format($blocks));
                $sender->sendMessage("§7Playtime: §f" . $this->formatPlaytime($playtime));

                return true;
        }

        return false;
    }

    private function handleLeaderboardCommand(
        CommandSender $sender,
        string $type,
        array $args
    ) : bool{

        if(!$sender instanceof Player){
            return true;
        }

        if(!isset($args[0])){
            $sender->sendMessage("/" . $type . " <create|remove>");
            return true;
        }

        switch(strtolower($args[0])){

            case "create":

                $this->leaderboardManager->createLeaderboard(
                    $type,
                    $sender->getPosition()
                );

                $sender->sendMessage("§aLeaderboard created.");
                return true;

            case "remove":

                if(
                    $this->leaderboardManager->removeNearestLeaderboard(
                        $type,
                        $sender->getPosition(),
                        5
                    )
                ){
                    $sender->sendMessage("§cLeaderboard removed.");
                }else{
                    $sender->sendMessage("§cNo leaderboard found nearby.");
                }

                return true;
        }

        return true;
    }

    public function formatPlaytime(int $seconds) : string{

        $hours = floor($seconds / 3600);

        $minutes = floor(
            ($seconds % 3600) / 60
        );

        return $hours . "h " . $minutes . "m";
    }
}
