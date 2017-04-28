<?php
namespace icontrolu;

use pocketmine\scheduler\PluginTask;

class InventoryUpdateTask extends PluginTask{
    public function onRun($tick){
        /** @var iControlU $owner */
        $owner = $this->getOwner();
        foreach($owner->s as $session){
            $session->syncInventory();
        }
    }
}