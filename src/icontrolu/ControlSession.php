<?php
namespace icontrolu;

use pocketmine\event\player\PlayerChatEvent;
use pocketmine\Player;
use pocketmine\Server;

class ControlSession{
    /**
     * @var \pocketmine\Player
     */
    private $p, $t, $inv;
    function __construct(Player $p, Player $t){
        $this->p = $p;
        $this->t = $t;
        /* Hide from others */
        foreach(Server::getInstance()->getOnlinePlayers() as $online){
            $online->hidePlayer($p);
        }
        /* Teleport to and hide target */
        $this->p->hidePlayer($this->t);
        $this->p->teleport($this->t->getPosition());
        /* Send Inventory */
        $this->inv = $this->p->getInventory()->getContents();
        $this->p->getInventory()->setContents($this->t->getInventory()->getContents());
    }
    /**
     * @return mixed
     */
    public function getControl(){
        return $this->p;
    }
    /**
     * @return mixed
     */
    public function getTarget(){
        return $this->t;
    }
    public function updatePosition(){
        $this->t->teleport($this->p->getPosition(), $this->p->yaw, $this->p->pitch);
    }
    /**
     * @param PlayerChatEvent $ev
     */
    public function sendChat(PlayerChatEvent $ev){
        Server::getInstance()->broadcastMessage(sprintf($ev->getFormat(), $this->t->getDisplayName(), $ev->getMessage()), $ev->getRecipients());
    }
    public function syncInventory(){
        if($this->p->getInventory()->getContents() != $this->t->getInventory()->getContents()){
            $this->p->getInventory()->setContents($this->t->getInventory()->getContents());
        }
    }
    public function stopControl(){
        /* Send back inventory */
        $this->p->getInventory()->setContents($this->inv);
        /* Reveal target */
        $this->p->showPlayer($this->t);
        /* Schedule Invisibility Effect */
        Server::getInstance()->getScheduler()->scheduleDelayedTask(new InvisibilityTask(Server::getInstance()->getPluginManager()->getPlugin("iControlU"), $this->p), 20*10);
    }
}
