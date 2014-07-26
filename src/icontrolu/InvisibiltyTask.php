<?php
namespace icontrolu;

use pocketmine\Player;
use pocketmine\plugin\Plugin;
use pocketmine\scheduler\PluginTask;
use pocketmine\Server;

class InvisibilityTask extends PluginTask{
    private $p;
    public function __construct(Plugin $main, Player $p){
        parent::__construct($main);
        $this->p = $p;
    }
    public function onRun($tick){
        $this->p->sendMessage("You are now longer invisible.");
        foreach(Server::getInstance()->getOnlinePlayers() as $online){
            $online->showPlayer($this->p);
        }
    }
}