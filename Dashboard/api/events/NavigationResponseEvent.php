<?php

namespace Dashboard\api\events;

use Dashboard\api\navigation\NavigationItem;
use event\interfaces\IEvent;
use event\interfaces\IEventListener;
use runtime\exceptions\RuntimeException;

class NavigationResponseEvent implements IEventListener
{
    const NAME = 'dashboard.navigation.request';
    public function __construct(private NavigationItem $navigationItem) {
        $this->navigationItem = $navigationItem;
    }
    public function handle(IEvent $event): array
    {
        if($event instanceof NavigationRequestEvent) {

            $container = $event->bundle()["navigationContainer"];
            $container->addNavigationItem($this->navigationItem);
            return [];
        }

        throw new RuntimeException("Dashboard navigation response event received an invalid event type " . get_class($event));
    }
}