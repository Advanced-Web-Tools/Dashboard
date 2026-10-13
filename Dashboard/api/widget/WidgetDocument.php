<?php
/*
 * Copyright (c) 2026 Stefan Crkvenjakov.
 * Part of the Dashboard package for AWT CMS.
 * GitHub: https://github.com/Advanced-Web-Tools/Dashboard
 * File: WidgetDocument.php
 */

namespace Dashboard\api\widget;

use URPC\api\contracts\IURPCBlock;
use URPC\api\URPCDocument;

class WidgetDocument extends URPCDocument
{
    public function __construct()
    {
        $this->addSection("main");
    }
}