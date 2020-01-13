<?php
namespace icontrolu;

use pocketmine\scheduler\Task;

class InventoryUpdateTask extends Task{
    /** @var iControlU $owner */
    private $owner;
    /**
     * InventoryUpdateTask constructor.
     * @param iControlU $owner
     */
    public function __construct(iControlU $owner) {
        $this->owner = $owner;
    }
    public function onRun(int $tick) : void{
        /** @var iControlU $owner */
        $owner = $this->owner;
        foreach($owner->s as $session){
            $session->syncInventory();
        }
    }
}
