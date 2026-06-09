<?php

declare(strict_types=1);

namespace StatsLeaderboards;

use pocketmine\utils\TextFormat;
use pocketmine\world\particle\FloatingTextParticle;
use pocketmine\world\Position;

class Leaderboard{

    private Main $plugin;
    private string $type;
    private Position $position;
    private FloatingTextParticle $particle;

    public function __construct(
        Main $plugin,
        string $type,
        Position $position
    ){
        $this->plugin = $plugin;
        $this->type = strtolower($type);
        $this->position = $position;

        $this->particle = new FloatingTextParticle(
            "",
            ""
        );
    }

    public function getType() : string{
        return $this->type;
    }

    public function getPosition() : Position{
        return $this->position;
    }

    public function update() : void{

        $top = match($this->type){
            "kills" => $this->plugin->getPlayerDataManager()->getTopKills(),
            "deaths" => $this->plugin->getPlayerDataManager()->getTopDeaths(),
            "blocks" => $this->plugin->getPlayerDataManager()->getTopBlocks(),
            "playtime" => $this->plugin->getPlayerDataManager()->getTopPlaytime(),
            default => []
        };

        $title = (string)$this->plugin
            ->getConfig()
            ->getNested($this->type . ".title");

        $lines = (array)$this->plugin
            ->getConfig()
            ->getNested($this->type . ".lines", []);

        $players = array_keys($top);
        $values = array_values($top);

        $text = TextFormat::colorize($title);

        foreach($lines as $index => $line){

            $rank = $index + 1;

            $player = $players[$index] ?? "N/A";
            $value = $values[$index] ?? 0;

            if($this->type === "playtime"){
                $value = $this->formatPlaytime(
                    (int)$value
                );
            }else{
                $value = number_format(
                    (int)$value
                );
            }

            $line = str_replace(
                [
                    "{player{$rank}}",
                    "{value{$rank}}"
                ],
                [
                    $player,
                    (string)$value
                ],
                $line
            );

            $text .= "\n" . TextFormat::colorize($line);
        }

        $this->particle->setTitle($text);

        $this->position
            ->getWorld()
            ->addParticle(
                $this->position,
                $this->particle
            );
    }

    public function remove() : void{

        $this->particle->setInvisible(true);

        $this->position
            ->getWorld()
            ->addParticle(
                $this->position,
                $this->particle
            );
    }

    private function formatPlaytime(
        int $seconds
    ) : string{

        $hours = floor(
            $seconds / 3600
        );

        $minutes = floor(
            ($seconds % 3600) / 60
        );

        return $hours . "h " . $minutes . "m";
    }

    public function toArray() : array{
        return [
            "type" => $this->type,
            "world" => $this->position
                ->getWorld()
                ->getFolderName(),
            "x" => $this->position->getX(),
            "y" => $this->position->getY(),
            "z" => $this->position->getZ()
        ];
    }
}
