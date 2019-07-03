<?php
namespace icontrolu;

use pocketmine\event\player\PlayerChatEvent;
use pocketmine\Player;

class ControlSession{
    private $p, $t, $inv, $m;
    function __construct(Player $p, Player $t, iControlU $m) {
        $this->p = $p;
        $this->t = $t;
        $this->m = $m;
        /* Hide from others */
        foreach($m->getServer()->getOnlinePlayers() as $online){
            $online->hidePlayer($p);
        }
        /* Teleport to and hide target */
        $p->hidePlayer($t);
        $p->teleport($t->getPosition());
        /* Send Inventory */
        $inv = $p->getInventory();
        $this->inv = $inv->getContents();
        $p->getInventory()->setContents($t->getInventory()->getContents());
    }
    public function getControl(): Player{
        return $this->p;
    }
    public function getTarget(): Player{
        return $this->t;
    }
    public function updatePosition(): void{
        $this->t->teleport($this->p->getPosition(), $this->p->yaw, $this->p->pitch);
    }
    public function sendChat(PlayerChatEvent $ev){
        $this->m->getServer()->broadcastMessage(sprintf($ev->getFormat(), $this->t->getDisplayName(), $ev->getMessage()), $ev->getRecipients());
    }
    public function syncInventory(): void{
        $pContents = $this->p->getInventory()->getContents();
        $tInventory = $this->t->getInventory();
        $tContents = $tInventory->getContents();
        if($pContents !== $tContents){
            $tInventory->setContents($pContents);
        }
    }
    public function stopControl(): void{
        /* Send back inventory */
        $this->p->getInventory()->setContents($this->inv);
        /* Reveal target */
        $this->p->showPlayer($this->t);
        /* Schedule Invisibility Effect */
        $this->m->getScheduler()->scheduleDelayedTask(new InvisibilityTask($this->m, $this->p), 20*10);
    }
}
