<?php

declare(strict_types=1);

namespace StatsLeaderboards\task;

use pocketmine\scheduler\Task;
use StatsLeaderboards\Main;

class PlaytimeTask extends Task{

    public function __construct(
        private Main $plugin
    ){}

    public function onRun() : void{

        $this->plugin
            ->getPlayerDataManager()
            ->addPlaytimeToOnlinePlayers(60);

        $this->plugin
            ->getPlayerDataManager()
            ->save();
    }
}
