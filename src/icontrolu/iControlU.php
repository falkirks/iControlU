<?php
namespace icontrolu;

use pocketmine\command\Command;
use pocketmine\command\CommandExecutor;
use pocketmine\command\CommandSender;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\inventory\InventoryPickupItemEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerAnimationEvent;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\event\player\PlayerDropItemEvent;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\inventory\PlayerInventory;
use pocketmine\network\mcpe\protocol\AnimatePacket;
use pocketmine\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\TextFormat;

class iControlU extends PluginBase implements CommandExecutor, Listener{
    public $b;
    /** @var  ControlSession[] */
    public $s;
    /** @var  InventoryUpdateTask */
    public $inv;
    public function onEnable() : void{
        $this->s = [];
        $this->b = [];
        $this->inv = new InventoryUpdateTask($this);
        $this->getScheduler()->scheduleRepeatingTask($this->inv, 5);
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
    }
    public function onCommand(CommandSender $sender, Command $cmd, string $label, array $args): bool{
        if($sender instanceof Player){
            if(isset($args[0])){
                switch($args[0]){
                    case 'stop':
                    case 's':
                        if($this->isControl($sender)){
                            $this->s[$sender->getName()]->stopControl();
                            unset($this->b[$this->s[$sender->getName()]->getTarget()->getName()]);
                            unset($this->s[$sender->getName()]);
                            $sender->sendMessage("Control stopped. You have invisibility for 10 seconds.");
                        } else
                            $sender->sendMessage("You are not controlling anyone.");
                        return true;
                    case 'control':
                    case 'c':
                        if(isset($args[1])){
                            if(($p = $this->getServer()->getPlayer($args[1])) instanceof Player){
                                $pName = $p->getName();
                                $sName = $sender->getName();
                                if($p->isOnline()){
                                    if(isset($this->s[$p->getName()]) || isset($this->b[$pName])){
                                        $sender->sendMessage(TextFormat::RED . "You are already bound to a control session.");
                                        return true;
                                    }
                                    else{
                                        if($p->hasPermission("icu.exempt") || $pName === $sName){
                                            $sender->sendMessage(TextFormat::RED . "You can't control this player.");
                                            return true;

                                        }
                                        else{
                                            $this->s[$sName] = new ControlSession($sender, $p, $this);
                                            $this->b[$pName] = true;
                                            $sender->sendMessage(TextFormat::GREEN . "You are now controlling " . $pName);
                                            return true;
                                        }
                                    }
                                }
                                else{
                                    $sender->sendMessage(TextFormat::RED . "Player not online.");
                                    return true;
                                }
                            }
                            else{
                                $sender->sendMessage(TextFormat::RED . "Player not found.");
                                return true;
                            }
                        }
                        return true;
                    default:
                        return false;
                }
            } else
                return false;
        } else{
            $sender->sendMessage(TextFormat::RED . "Please run command in game.");
            return true;
        }
    }
    public function onMove(PlayerMoveEvent $event) : void{
        if($this->isBarred($event->getPlayer())){
            $event->setCancelled();
        }
        elseif($this->isControl($event->getPlayer())) {
            $this->s[$event->getPlayer()->getName()]->updatePosition();
        }
    }
    public function onMessage(PlayerChatEvent $event) : void{
        if($this->isBarred($event->getPlayer())){
            $event->setCancelled();
        }
        elseif($this->isControl($event->getPlayer())){
            $this->s[$event->getPlayer()->getName()]->sendChat($event);
            $event->setCancelled();
        }
    }
    public function onItemDrop(PlayerDropItemEvent $event) : void{
        if($this->isBarred($event->getPlayer())){
            $event->setCancelled();
        }
    }
    public function onItemPickup(InventoryPickupItemEvent $event) : void{
        $inv = $event->getInventory();
        if($inv instanceof PlayerInventory && $inv->getHolder() instanceof Player){
            if($this->isBarred($inv->getHolder())){
                $event->setCancelled();
            }
        }
    }
    public function onBreak(BlockBreakEvent $event) : void{
        if($this->isBarred($event->getPlayer())){
            $event->setCancelled();
        }
    }
    public function onPlace(BlockPlaceEvent $event) : void{
        if($this->isBarred($event->getPlayer())){
            $event->setCancelled();
        }
    }
    public function onQuit(PlayerQuitEvent $event) : void{
        $eventPlayer = $event->getPlayer();
        $eventPlayerName = $eventPlayer->getName();
        if($this->isControl($eventPlayer)){
            unset($this->b[$this->s[$eventPlayerName]->getTarget()->getName()]);
            unset($this->s[$eventPlayerName]);
        }
        elseif($this->isBarred($event->getPlayer())){
            foreach($this->s as $i){
                if($i->getTarget()->getName() === $eventPlayerName){
                    $iController = $i->getControl();
                    $iController->sendMessage($eventPlayerName . " has left the game. Your session has been closed.");
                    foreach($this->getServer()->getOnlinePlayers() as $online){
                        $online->showPlayer($iController);
                    }
                    $iController->showPlayer($i->getTarget()); //Will work if my PR is merged
                    unset($this->b[$eventPlayerName]);
                    unset($this->s[$iController->getName()]);
                    break;
                }
            }
        }
    }
    public function onPlayerAnimation(PlayerAnimationEvent $event): void{
        $eventPlayer = $event->getPlayer();
        if($this->isBarred($eventPlayer)){
            $event->setCancelled();
        }
        elseif($this->isControl($eventPlayer)){
            $event->setCancelled();
            $pk = new AnimatePacket();
            $eventPlayerName = $eventPlayer->getName();
            $target = $this->s[$eventPlayerName]->getTarget();
            $pk->eid = $target->getID();
            $pk->action = $event->getAnimationType();
            $this->getServer()->broadcastPacket($target->getViewers(), $pk);
        }
    }
    public function onDisable(): void{
        $this->getLogger()->info("Sessions are closing...");
        $players = $this->getServer()->getOnlinePlayers();
        foreach($this->s as $i){
            $iControl = $i->getControl();
            $iTarget = $i->getTarget();
            $iControl->sendMessage("iCU is disabling, you are visible.");
            foreach($players as $online){
                $online->showPlayer($iControl);
            }
            $iControl->showPlayer($iTarget);
            unset($this->b[$iTarget->getName()]);
            unset($this->s[$iControl->getName()]);
        }
    }
    public function isControl(Player $p): bool{
        return (isset($this->s[$p->getName()]));
    }
    public function isBarred(Player $p): bool{
        return (isset($this->b[$p->getName()]));
    }
}
