<?php
namespace icontrolu;

use pocketmine\Player;
use pocketmine\plugin\Plugin;
use pocketmine\scheduler\Task;
use pocketmine\Server;

class InvisibilityTask extends Task{
    private $p;
    public function __construct(Plugin $main, Player $p){
        $this->p = $p;
    }
    public function onRun(int $tick) : void{
        $this->p->sendMessage("You are no longer invisible.");
        foreach(Server::getInstance()->getOnlinePlayers() as $online){
            $online->showPlayer($this->p);
        }
    }
}
