<?php
namespace icontrolu;

use pocketmine\scheduler\Task;

class InventoryUpdateTask extends Task{
    public function onRun(int $tick) : void{
        /** @var iControlU $owner */
        $owner = $this->getOwner();
        foreach($owner->s as $session){
            $session->syncInventory();
        }
    }
}
