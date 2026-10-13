<?php
namespace Dashboard\api\events;
use Dashboard\api\widget\{WidgetRegistry, WidgetUser};
final class WidgetRegistryEvent implements \event\interfaces\IEvent
{
    public const NAME = "dashboard.widgets.register";
    public function __construct(
        private readonly WidgetRegistry $registry,
        public readonly WidgetUser $user,
    ) {}
    public function getRegistry(): WidgetRegistry
    {
        return $this->registry;
    }
    public function getName(): string
    {
        return self::NAME;
    }
    public function bundle(): array
    {
        return ["registry" => $this->registry, "user" => $this->user];
    }
}
