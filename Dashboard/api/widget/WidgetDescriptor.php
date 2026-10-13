<?php
/*
 * Copyright (c) 2026 Stefan Crkvenjakov.
 * Part of the Dashboard package for AWT CMS.
 * GitHub: https://github.com/Advanced-Web-Tools/Dashboard
 * File: WidgetDescriptor.php
 */

namespace Dashboard\api\widget;

use URPC\api\URPCBlockDescriptor;

class WidgetDescriptor extends URPCBlockDescriptor
{
    public function __construct(string $name = '', array $props = [], string $renderer = '', string $sourceKey = '')
    {
        parent::__construct($name, $props, $renderer, $sourceKey);
        $this->setSourceKey($sourceKey);
    }
}