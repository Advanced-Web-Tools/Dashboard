<?php
/*
 * Copyright (c) 2026 Stefan Crkvenjakov.
 * Part of the Dashboard package for AWT CMS.
 * GitHub: https://github.com/Advanced-Web-Tools/Dashboard
 * File: DashboardWidget.php
 */

namespace Dashboard\api\widget;
use URPC\api\URPCBlock;

abstract class DashboardWidget extends URPCBlock
{
    public const SIZE_SMALL = "small";
    public const SIZE_MEDIUM = "medium";
    public const SIZE_LARGE = "large";
    private string $size = self::SIZE_LARGE;

    public function setSize(string $size): void
    {
        if (!in_array($size, [self::SIZE_SMALL, self::SIZE_MEDIUM, self::SIZE_LARGE], true)) {
            throw new \InvalidArgumentException("Invalid widget size.");
        }
        $this->size = $size;
        $this->setBlockAttributes(array_replace($this->getBlockAttributes(), ["data-widget-size" => $size]));
    }

    public function getSize(): string
    {
        return $this->size;
    }

    abstract public function widget(): self;
}
