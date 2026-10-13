<?php
namespace Dashboard\api\events;
use Dashboard\api\widget\WidgetDefinition;
final class WidgetRegistrationListener implements \event\interfaces\IEventListener
{
    public function __construct(private readonly WidgetDefinition $definition) {}
    public function handle(\event\interfaces\IEvent $event): array
    {
        if (!($event instanceof WidgetRegistryEvent)) {
            throw new \InvalidArgumentException("Expected WidgetRegistryEvent.");
        }
        $event->getRegistry()->register($this->definition);
        return [];
    }
}
