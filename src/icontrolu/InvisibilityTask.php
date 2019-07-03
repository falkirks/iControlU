<?php
namespace icontrolu;

use pocketmine\Player;
use pocketmine\plugin\Plugin;
use pocketmine\scheduler\Task;

class InvisibilityTask extends Task{
    /** @var iControlU $plugin */
    private $plugin;
    /** @var Player $player */
    private $player;
    public function __construct(Plugin $main, Player $player) {
        $this->plugin = $main;
        $this->player = $player;
    }
    public function onRun(int $tick) : void{
        $this->player->sendMessage("You are no longer invisible.");
        foreach($this->plugin->getServer()->getOnlinePlayers() as $online){
            $online->showPlayer($this->player);
        }
    }
}
