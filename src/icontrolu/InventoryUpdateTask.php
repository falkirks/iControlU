<?php
namespace icontrolu;

use pocketmine\scheduler\PluginTask;

class InventoryUpdateTask extends PluginTask{
    public function onRun($tick){
        foreach($this->getOwner()->s as $session){
            $session->syncInventory();
        }
    }
}