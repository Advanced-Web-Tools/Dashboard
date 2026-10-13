<?php
namespace Dashboard\api\widget;
final class WidgetRegistry
{
    private array $definitions = [];
    public function register(WidgetDefinition $definition): void
    {
        if (isset($this->definitions[$definition->key])) {
            throw new \LogicException("Duplicate widget key: " . $definition->key);
        }
        $this->definitions[$definition->key] = $definition;
    }
    public function availableFor(WidgetUser $user): array
    {
        $items = array_filter($this->definitions, fn($d) => $d->availableFor($user));
        uasort($items, fn($a, $b) => [$a->defaultPosition, $a->key] <=> [$b->defaultPosition, $b->key]);
        return $items;
    }
}
