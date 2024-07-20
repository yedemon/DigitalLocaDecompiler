<?php

declare(strict_types=1);

namespace Digital\Item;

Class ItemOnEventFunc extends ItemProcedure implements IItem {
    protected int $eventType;

    public function getType() : int {
        return IItem::TYPE_ONEVENT;
    }

    public function setEventType($evt_type) {
        $this->eventType = $evt_type;
    }

    public function getEventType() : int {
        return $this->eventType;
    }
}
