<?php

/*
 * Copyright (c) 2026 Stefan Crkvenjakov.
 * Part of the Dashboard package for AWT CMS.
 * Github: https://github.com/Advanced-Web-Tools/Dashboard
 * File: DashboardControllerBase.php
 */

namespace Dashboard\api\page;

use admin\Admin;
use controller\Controller;
use Dashboard\api\events\NavigationRequestEvent;
use Dashboard\api\navigation\NavigationContainer;
use DOMException;
use redirect\Redirect;
use response\Response;
use view\View;

abstract class DashboardControllerBase extends Controller
{

    protected NavigationContainer $navigationContainer;
    protected string $navigationHtml;

    /**
     * @throws DOMException
     */
    protected function buildNavigation(): string
    {
        $this->navigationContainer = new NavigationContainer();
        $this->eventDispatcher->dispatch(new NavigationRequestEvent($this->navigationContainer));
        return $this->navigationHtml = $this->navigationContainer->buildContainer()->saveHTML();
    }

    protected function dispatchEvents(array $events): void
    {
        foreach ($events as $event) {
            $this->eventDispatcher->dispatch($event);
        }
    }

    protected function checkAuth(): void
    {
        $admin = new Admin();

        if (!$admin->checkAuthentication()) {
            header("Location: /dashboard/login", true, 302);
            exit;
        }
    }

    protected function setPermissionLevel(int $permissionLevel): void
    {
        $admin = new Admin();
        if ($admin->checkPermission($permissionLevel)) {
            return;
        }

        Response::make(403, "You don't have permission to view this page.")->send();
    }


    /**
     * @inheritDoc
     */
    abstract public function index(array|string $params): View|Redirect|Response;
}