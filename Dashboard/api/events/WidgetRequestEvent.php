<?php
/*
 * Copyright (c) 2026 Stefan Crkvenjakov.
 * Part of the Dashboard package for AWT CMS.
 * GitHub: https://github.com/Advanced-Web-Tools/Dashboard
 * File: WidgetRequestEvent.php
 */

namespace Dashboard\api\events;

use Dashboard\api\widget\WidgetDocument;
use event\interfaces\IEvent;

class WidgetRequestEvent implements IEvent
{
    public function __construct(private WidgetDocument $document)
    {
    }

    public function getDocument(): WidgetDocument
    {
        return $this->document;
    }

    public function getName(): string
    {
        return 'dashboard.widget.request';
    }

    public function bundle(): array
    {
        return [
            'document' => $this->document
        ];
    }

}