<?php
/*
 * Copyright (c) 2026 Stefan Crkvenjakov.
 * Part of the Dashboard package for AWT CMS.
 * GitHub: https://github.com/Advanced-Web-Tools/Dashboard
 * File: WidgetResponseEvent.php
 */

namespace Dashboard\api\events;

use Dashboard\api\widget\DashboardWidget;
use event\interfaces\IEvent;
use event\interfaces\IEventListener;
use runtime\exceptions\RuntimeException;

class WidgetResponseEvent implements IEventListener
{
    public const NAME = 'dashboard.widget.request';

    public function __construct(private DashboardWidget $widget)
    {
        $this->widget = $widget;
    }

    public function handle(IEvent $event): array
    {
        if (!($event instanceof WidgetRequestEvent)) {
            throw new RuntimeException("Dashboard widget response event received an invalid event type " . get_class($event));
        }

        $this->widget->widget();
        $event->bundle()["document"]->addBlock("main", $this->widget);
        return [];
    }
}