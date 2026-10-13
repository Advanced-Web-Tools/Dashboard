<?php

namespace Dashboard\widgets;

use Dashboard\api\widget\DashboardWidget;
use Dashboard\widgets\processors\WelcomeWidget as WelcomeProcessor;
use URPC\api\URPCBlock;
use URPC\api\URPCBlockDescriptor;

class WelcomeWidget extends DashboardWidget
{
    private function textBlock(string $tag, string $role, string $class): URPCBlock
    {
        $block = new URPCBlock(new URPCBlockDescriptor($tag, ["welcome_role" => $role]));

        $block->setBlockAttributes(["class" => $class]);

        return $block;
    }

    public function createChildren(): array
    {
        $header = new URPCBlock(new URPCBlockDescriptor("header"));
        $header->setBlockChildren([
            $this->textBlock("time", "date", "d-block text-small text-muted mb-3"),
            $this->textBlock("h2", "greeting", "card-title h3"),
            $this->textBlock("p", "message", "card-subtitle"),
        ]);

        $tipLabel = new URPCBlock(new URPCBlockDescriptor("strong"));
        $tipLabel->setBlockContent("Daily tip");
        $tipLabel->setBlockAttributes(["class" => "text-small"]);

        $footer = new URPCBlock(new URPCBlockDescriptor("footer"));
        $footer->setBlockAttributes([
            "class" => "d-flex flex-wrap align-items-start gap-1 mt-4 pt-3 border-top",
        ]);
        $footer->setBlockChildren([$tipLabel, $this->textBlock("p", "tip", "text-small text-muted m-0")]);

        return [$header, $footer];
    }

    public function widget(): DashboardWidget
    {
        $descriptor = new URPCBlockDescriptor("section");
        $descriptor->setSourceKey("Dashboard/widgets/welcome");
        $descriptor->setBlockRenderer(WelcomeProcessor::class);

        $this->setBlockDescriptor($descriptor);
        $this->setSize(self::SIZE_LARGE);

        $this->setBlockAttributes(array_replace($this->getBlockAttributes(), ["class" => "card min-w-0 w-100"]));

        $this->setBlockChildren($this->createChildren());

        return $this;
    }
}
