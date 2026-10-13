<?php

namespace Dashboard\api\events;

use Dashboard\api\navigation\NavigationContainer;
use event\interfaces\IEvent;

class NavigationRequestEvent implements IEvent
{
    public NavigationContainer $navigationContainer;

    public function __construct(NavigationContainer $navigationContainer)
    {
        $this->navigationContainer = $navigationContainer;
    }

    /**
     * @inheritDoc
     */
    public function getName(): string
    {
        return "dashboard.navigation.request";
    }

    /**
     * @inheritDoc
     */
    public function bundle(): array
    {
        return ["navigationContainer" => $this->navigationContainer];
    }
}