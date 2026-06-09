<?php

declare(strict_types=1);

namespace StatsLeaderboards;

use pocketmine\player\Player;
use pocketmine\utils\Config;

class PlayerDataManager{

    private Main $plugin;

    private Config $config;

    /**
     * @var array<string, array{
     *     kills:int,
     *     deaths:int,
     *     blocks:int,
     *     playtime:int
     * }>
     */
    private array $players = [];

    public function __construct(Main $plugin){
        $this->plugin = $plugin;

        $this->config = new Config(
            $plugin->getDataFolder() . "players.yml",
            Config::YAML,
            [
                "players" => []
            ]
        );
    }

    public function load() : void{
        $this->players = $this->config->get("players", []);
    }

    public function save() : void{
        $this->config->set("players", $this->players);
        $this->config->save();
    }

    private function initPlayer(string $player) : void{
        $player = strtolower($player);

        if(!isset($this->players[$player])){
            $this->players[$player] = [
                "kills" => 0,
                "deaths" => 0,
                "blocks" => 0,
                "playtime" => 0
            ];
        }
    }

    public function addKill(string $player, int $amount = 1) : void{
        $this->initPlayer($player);

        $this->players[strtolower($player)]["kills"] += $amount;
    }

    public function addDeath(string $player, int $amount = 1) : void{
        $this->initPlayer($player);

        $this->players[strtolower($player)]["deaths"] += $amount;
    }

    public function addBlock(string $player, int $amount = 1) : void{
        $this->initPlayer($player);

        $this->players[strtolower($player)]["blocks"] += $amount;
    }

    public function addPlaytime(string $player, int $seconds) : void{
        $this->initPlayer($player);

        $this->players[strtolower($player)]["playtime"] += $seconds;
    }

    public function addPlaytimeToOnlinePlayers(int $seconds = 60) : void{
        foreach($this->plugin->getServer()->getOnlinePlayers() as $player){
            $this->addPlaytime(
                $player->getName(),
                $seconds
            );
        }
    }

    public function getKills(string $player) : int{
        $this->initPlayer($player);

        return $this->players[strtolower($player)]["kills"];
    }

    public function getDeaths(string $player) : int{
        $this->initPlayer($player);

        return $this->players[strtolower($player)]["deaths"];
    }

    public function getBlocks(string $player) : int{
        $this->initPlayer($player);

        return $this->players[strtolower($player)]["blocks"];
    }

    public function getPlaytime(string $player) : int{
        $this->initPlayer($player);

        return $this->players[strtolower($player)]["playtime"];
    }

    public function setKills(string $player, int $value) : void{
        $this->initPlayer($player);

        $this->players[strtolower($player)]["kills"] = $value;
    }

    public function setDeaths(string $player, int $value) : void{
        $this->initPlayer($player);

        $this->players[strtolower($player)]["deaths"] = $value;
    }

    public function setBlocks(string $player, int $value) : void{
        $this->initPlayer($player);

        $this->players[strtolower($player)]["blocks"] = $value;
    }

    public function setPlaytime(string $player, int $value) : void{
        $this->initPlayer($player);

        $this->players[strtolower($player)]["playtime"] = $value;
    }

    public function getPlayerData(string $player) : array{
        $this->initPlayer($player);

        return $this->players[strtolower($player)];
    }

    public function getAllData() : array{
        return $this->players;
    }

    /**
     * Returns:
     * [
     *   "playername" => value,
     *   ...
     * ]
     */
    public function getTop(string $type, int $limit = 10) : array{

        $data = [];

        foreach($this->players as $player => $stats){

            if(!isset($stats[$type])){
                continue;
            }

            $data[$player] = (int)$stats[$type];
        }

        arsort($data, SORT_NUMERIC);

        return array_slice(
            $data,
            0,
            $limit,
            true
        );
    }

    public function getTopKills(int $limit = 10) : array{
        return $this->getTop("kills", $limit);
    }

    public function getTopDeaths(int $limit = 10) : array{
        return $this->getTop("deaths", $limit);
    }

    public function getTopBlocks(int $limit = 10) : array{
        return $this->getTop("blocks", $limit);
    }

    public function getTopPlaytime(int $limit = 10) : array{
        return $this->getTop("playtime", $limit);
    }

    public function playerExists(string $player) : bool{
        return isset($this->players[strtolower($player)]);
    }

    public function getPlayerCount() : int{
        return count($this->players);
    }

    public function removePlayer(string $player) : void{
        unset($this->players[strtolower($player)]);
    }
}
