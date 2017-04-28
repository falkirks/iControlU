<?php
namespace icontrolu;

use pocketmine\event\player\PlayerChatEvent;
use pocketmine\Player;

class ControlSession{
    private $p, $t, $inv, $m;
    function __construct(Player $p, Player $t, iControlU $m){
        $this->p = $p;
        $this->t = $t;
        $this->m = $m;
        /* Hide from others */
        foreach($this->m->getServer()->getOnlinePlayers() as $online){
            $online->hidePlayer($p);
        }
        /* Teleport to and hide target */
        $this->p->hidePlayer($this->t);
        $this->p->teleport($this->t->getPosition());
        /* Send Inventory */
        $this->inv = $this->p->getInventory()->getContents();
        $this->p->getInventory()->setContents($this->t->getInventory()->getContents());
    }
    public function getControl(){
        return $this->p;
    }
    public function getTarget(){
        return $this->t;
    }
    public function updatePosition(){
        $this->t->teleport($this->p->getPosition(), $this->p->yaw, $this->p->pitch);
    }
    public function sendChat(PlayerChatEvent $ev){
        $this->m->getServer()->broadcastMessage(sprintf($ev->getFormat(), $this->t->getDisplayName(), $ev->getMessage()), $ev->getRecipients());
    }
    public function syncInventory(){
        if($this->p->getInventory()->getContents() !== $this->t->getInventory()->getContents()){
            $this->t->getInventory()->setContents($this->p->getInventory()->getContents());
        }
    }
    public function stopControl(){
        /* Send back inventory */
        $this->p->getInventory()->setContents($this->inv);
        /* Reveal target */
        $this->p->showPlayer($this->t);
        /* Schedule Invisibility Effect */
        $this->m->getServer()->getScheduler()->scheduleDelayedTask(new InvisibilityTask($this->m, $this->p), 20*10);
    }
}
