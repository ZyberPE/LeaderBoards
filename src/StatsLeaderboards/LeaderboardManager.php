<?php

declare(strict_types=1);

namespace StatsLeaderboards;

use pocketmine\utils\Config;
use pocketmine\world\Position;

class LeaderboardManager{

    private Main $plugin;

    /** @var Leaderboard[] */
    private array $leaderboards = [];

    private Config $config;

    public function __construct(Main $plugin){
        $this->plugin = $plugin;

        $this->config = new Config(
            $plugin->getDataFolder() . "leaderboards.yml",
            Config::YAML,
            [
                "leaderboards" => []
            ]
        );
    }

    public function load() : void{

        $this->leaderboards = [];

        foreach($this->config->get("leaderboards", []) as $data){

            if(
                !isset(
                    $data["type"],
                    $data["world"],
                    $data["x"],
                    $data["y"],
                    $data["z"]
                )
            ){
                continue;
            }

            $world = $this->plugin
                ->getServer()
                ->getWorldManager()
                ->getWorldByName($data["world"]);

            if($world === null){
                continue;
            }

            $leaderboard = new Leaderboard(
                $this->plugin,
                $data["type"],
                new Position(
                    (float)$data["x"],
                    (float)$data["y"],
                    (float)$data["z"],
                    $world
                )
            );

            $this->leaderboards[] = $leaderboard;
        }

        $this->updateAll();
    }

    public function save() : void{

        $data = [];

        foreach($this->leaderboards as $leaderboard){
            $data[] = $leaderboard->toArray();
        }

        $this->config->set(
            "leaderboards",
            $data
        );

        $this->config->save();
    }

    public function createLeaderboard(
        string $type,
        Position $position
    ) : Leaderboard{

        $leaderboard = new Leaderboard(
            $this->plugin,
            $type,
            $position
        );

        $this->leaderboards[] = $leaderboard;

        $leaderboard->update();

        $this->save();

        return $leaderboard;
    }

    public function removeNearestLeaderboard(
        string $type,
        Position $position,
        float $radius = 5
    ) : bool{

        foreach($this->leaderboards as $index => $leaderboard){

            if(
                strtolower($leaderboard->getType()) !== strtolower($type)
            ){
                continue;
            }

            $lbPos = $leaderboard->getPosition();

            if(
                $lbPos->getWorld()->getFolderName() !==
                $position->getWorld()->getFolderName()
            ){
                continue;
            }

            if(
                $lbPos->distance($position) <= $radius
            ){

                $leaderboard->remove();

                unset(
                    $this->leaderboards[$index]
                );

                $this->leaderboards = array_values(
                    $this->leaderboards
                );

                $this->save();

                return true;
            }
        }

        return false;
    }

    public function updateAll() : void{

        foreach($this->leaderboards as $leaderboard){

            try{
                $leaderboard->update();
            }catch(\Throwable $e){
                $this->plugin
                    ->getLogger()
                    ->error(
                        "Failed updating leaderboard: " .
                        $e->getMessage()
                    );
            }
        }
    }

    /**
     * @return Leaderboard[]
     */
    public function getLeaderboards() : array{
        return $this->leaderboards;
    }

    /**
     * @return Leaderboard[]
     */
    public function getLeaderboardsByType(
        string $type
    ) : array{

        $result = [];

        foreach($this->leaderboards as $leaderboard){

            if(
                strtolower($leaderboard->getType()) ===
                strtolower($type)
            ){
                $result[] = $leaderboard;
            }
        }

        return $result;
    }

    public function getLeaderboardCount() : int{
        return count(
            $this->leaderboards
        );
    }

    public function clear() : void{

        foreach($this->leaderboards as $leaderboard){
            $leaderboard->remove();
        }

        $this->leaderboards = [];

        $this->save();
    }
}
