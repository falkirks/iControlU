<?php
namespace icontrolu;

use pocketmine\Player;
use pocketmine\plugin\Plugin;
use pocketmine\scheduler\Task;

class InvisibilityTask extends Task{
    private $p;
    public function __construct(Plugin $main, Player $p){
        $this->p = $p;
    }
    public function onRun(int $tick) : void{
        $this->p->sendMessage("You are no longer invisible.");
        foreach($this->getOwner()->getServer()->getOnlinePlayers() as $online){
            $online->showPlayer($this->p);
        }
    }
}
