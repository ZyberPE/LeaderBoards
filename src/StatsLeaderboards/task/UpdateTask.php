<?php

declare(strict_types=1);

namespace StatsLeaderboards\task;

use pocketmine\scheduler\Task;
use StatsLeaderboards\Main;

class UpdateTask extends Task{

    public function __construct(
        private Main $plugin
    ){}

    public function onRun() : void{
        $this->plugin
            ->getLeaderboardManager()
            ->updateAll();
    }
}
