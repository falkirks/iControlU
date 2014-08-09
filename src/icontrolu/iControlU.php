<?php
namespace icontrolu;

use pocketmine\command\Command;
use pocketmine\command\CommandExecutor;
use pocketmine\command\CommandSender;
use pocketmine\event\entity\EntityMoveEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\Player;
use pocketmine\plugin\PluginBase;

class iControlU extends PluginBase implements CommandExecutor, Listener{
    public $s, $b, $inv;
    public function onEnable(){
        $this->s = [];
        $this->b = [];
        $this->inv = new InventoryUpdateTask($this);
        $this->getServer()->getScheduler()->scheduleRepeatingTask($this->inv, 5);
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
    }
    public function onCommand(CommandSender $sender, Command $cmd, $label, array $args){
        if($sender instanceof Player){
            if(isset($args[0])){
                switch($args[0]){
                    case 'stop':
                        if($this->isControl($sender)){
                            $this->s[$sender->getName()]->stopControl();
                            unset($this->b[$this->s[$sender->getName()]->getTarget()->getName()]);
                            unset($this->s[$sender->getName()]);
                            $sender->sendMessage("Control stopped. You have invisibility for 10 seconds.");
                            return true;
                        }
                        else{
                            $sender->sendMessage("You are not controlling anyone.");
                        }
                        break;
                    case 'control':
                        if(isset($args[1])){
                            if(($p = $this->getServer()->getPlayer($args[1])) instanceof Player){
                                if($p->isOnline()){
                                    if(isset($this->s[$p->getName()]) || isset($this->b[$p->getName()])){
                                        $sender->sendMessage("You are already bound to a control session.");
                                        return true;
                                    }
                                    else{
                                        if($p->hasPermission("icu.exempt") || $p->getName() === $sender->getName()){
                                            $sender->sendMessage("You can't control this player.");
                                            return true;

                                        }
                                        else{
                                            $this->s[$sender->getName()] = new ControlSession($sender, $p);
                                            $sender->sendMessage("You are now controlling " . $p->getName());
                                            return true;
                                        }
                                    }
                                }
                                else{
                                    $sender->sendMessage("Player not online.");
                                    return true;
                                }
                            }
                            else{
                                $sender->sendMessage("Player not found.");
                                return true;
                            }
                        }
                        break;
                    default:
                        return false;
                        break;
                }
            }
        }
        else{
            $sender->sendMessage("Please run command in game.");
            return true;
        }
    }
    public function onMove(EntityMoveEvent $event){
        if($event->getEntity() instanceof Player){
            if($this->isBarred($event->getEntity())){
                $event->setCancelled();
            }
            elseif($this->isControl($event->getEntity())){
                $this->s[$event->getEntity()->getName()]->updatePosition();
            }
        }
    }
    public function onMessage(PlayerChatEvent $event){
        if($this->isBarred($event->getPlayer())){
            $event->setCancelled();
        }
        elseif($this->isControl($event->getPlayer())){
            $this->s[$event->getEntity()->getName()]->sendChat($event);
        }
    }
    public function onQuit(PlayerQuitEvent $event){
        if($this->isControl($event->getPlayer())){
            unset($this->b[$this->s[$event->getPlayer()->getName()]->getTarget()->getName()]);
            unset($this->s[$event->getPlayer()->getName()]);
        }
        elseif($this->isBarred($event->getPlayer())){
            foreach($this->s as $i){
                if($i->getTarget()->getName() == $event->getPlayer()->getName()){
                    $i->getControl()->sendMessage($event->getPlayer()->getName() . " has left the game. Your session has been closed.");
                    unset($this->b[$event->getPlayer()->getName()]);
                    unset($this->s[$i->getControl()->getName()]);
                    break;
                }
            }
        }
    }
    public function isControl(Player $p){
        return (isset($this->s[$p->getName()]));
    }
    public function isBarred(Player $p){
        return (isset($this->b[$p->getName()]));
    }
}