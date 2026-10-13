<?php
namespace Dashboard\service;
use Dashboard\api\events\WidgetRegistryEvent;
use Dashboard\api\widget\{WidgetRegistry, WidgetUser, WidgetDocument};
use event\EventDispatcher;
final class WidgetService
{
    private array $definitions;
    public function __construct(
        private readonly EventDispatcher $dispatcher,
        private readonly WidgetUser $user,
        private readonly WidgetLayoutStore $store = new WidgetLayoutStore(),
    ) {
        $registry = new WidgetRegistry();
        $dispatcher->dispatch(new WidgetRegistryEvent($registry, $user));
        $this->definitions = $registry->availableFor($user);
    }
    public function state(): array
    {
        return $this->present($this->store->load($this->user->id));
    }
    private function present(array $raw): array
    {
        $order = [];
        foreach ($raw["order"] as $key) {
            if (is_string($key) && isset($this->definitions[$key]) && !in_array($key, $order, true)) {
                $order[] = $key;
            }
        }
        foreach ($this->definitions as $key => $def) {
            if (!in_array($key, $order, true)) {
                $order[] = $key;
            }
        }
        $visibility = [];
        $widgets = [];
        foreach ($order as $key) {
            $def = $this->definitions[$key];
            $visible = $raw["visibility"][$key] ?? $def->defaultVisible;
            $visibility[$key] = (bool) $visible;
            $widgets[] = [
                "key" => $key,
                "label" => $def->label,
                "description" => $def->description,
                "package" => $def->packageName,
                "size" => $def->size,
                "visible" => (bool) $visible,
                "defaultVisible" => $def->defaultVisible,
                "defaultPosition" => $def->defaultPosition,
            ];
        }
        return [
            "revision" => $raw["revision"],
            "order" => $order,
            "visibility" => $visibility,
            "widgets" => $widgets,
        ];
    }
    public function html(?array $state = null): string
    {
        $state ??= $this->state();
        $renderer = new WidgetRenderer(new WidgetDocument(), $this->dispatcher, $this->definitions, $state);
        return $renderer->widgets();
    }
    private function key(mixed $key): string
    {
        if (!is_string($key) || !isset($this->definitions[$key])) {
            throw new \InvalidArgumentException("This widget is not available to you.");
        }
        return $key;
    }
    public function visibility(mixed $key, bool $visible, int $revision): array
    {
        $key = $this->key($key);
        $raw = $this->store->load($this->user->id);
        $raw["visibility"][$key] = $visible;
        return $this->present($this->store->save($this->user->id, $revision, $raw));
    }
    public function layout(array $layout, int $revision): array
    {
        if (
            array_diff(array_keys($layout), ["order", "visibility"]) ||
            !is_array($layout["order"] ?? null) ||
            !array_is_list($layout["order"]) ||
            !is_array($layout["visibility"] ?? null)
        ) {
            throw new \InvalidArgumentException("Invalid widget layout.");
        }
        if (
            count($layout["order"]) !== count($this->definitions) ||
            count($layout["visibility"]) !== count($this->definitions)
        ) {
            throw new \InvalidArgumentException("The available widget list changed. Reload and try again.");
        }
        $order = [];
        foreach ($layout["order"] as $key) {
            $key = $this->key($key);
            if (in_array($key, $order, true)) {
                throw new \InvalidArgumentException("Duplicate widget key.");
            }
            $order[] = $key;
        }
        foreach ($layout["visibility"] as $key => $value) {
            $this->key($key);
            if (!is_bool($value)) {
                throw new \InvalidArgumentException("Visibility must be true or false.");
            }
        }
        $raw = $this->store->load($this->user->id);
        // Retain unavailable-package preferences, including their position in the sequence.
        $merged = [];
        $remaining = $order;
        foreach ($raw["order"] as $old) {
            if (isset($this->definitions[$old])) {
                if ($remaining !== []) {
                    $merged[] = array_shift($remaining);
                }
            } else {
                $merged[] = $old;
            }
        }
        $raw["order"] = array_values(array_unique([...$merged, ...$remaining]));
        $raw["visibility"] = array_replace($raw["visibility"], $layout["visibility"]);
        return $this->present($this->store->save($this->user->id, $revision, $raw));
    }
    public function reset(int $revision): array
    {
        return $this->present(
            $this->store->save($this->user->id, $revision, ["order" => [], "visibility" => []]),
        );
    }
}
