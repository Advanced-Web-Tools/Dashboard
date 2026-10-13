<?php
namespace Dashboard\service;
use Dashboard\api\widget\WidgetDocument;
use event\EventDispatcher;
use URPC\service\renderer\URPC;
final class WidgetRenderer extends URPC
{
    public function __construct(
        private readonly WidgetDocument $widgetDocument,
        public EventDispatcher $eventDispatcher,
        private readonly array $definitions,
        private readonly array $state,
    ) {
        $this->setSourceNamespace("Dashboard/pages/home/widgets");
    }
    public function widgets(): string
    {
        $this->widgetDocument->fromArray(["main" => []]);
        foreach ($this->state["order"] as $key) {
            if (!($this->state["visibility"][$key] ?? false) || !isset($this->definitions[$key])) {
                continue;
            }
            $this->widgetDocument->addBlock("main", $this->definitions[$key]->create());
        }
        $this->setDocument($this->widgetDocument);
        return $this->render();
    }
}
