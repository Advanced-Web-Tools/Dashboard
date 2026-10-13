<?php
/*
 * Copyright (c) 2026 Stefan Crkvenjakov.
 * Part of the Dashboard package for AWT CMS.
 * GitHub: https://github.com/Advanced-Web-Tools/Dashboard
 * File: DashboardRouter.php
 */

namespace Dashboard\runtime;

use router\Router;
use runtime\api\RuntimeControllerAPI;
use runtime\api\RuntimeRouterAPI;

final class DashboardRouter extends RuntimeRouterAPI
{
    private RuntimeControllerAPI $dashboardControllerAPI;

    public function setup(): void
    {
        parent::setup();
        $this->dashboardControllerAPI = $this->getPassable("DashboardControllerAPI");
    }

    public function main(): void
    {
        $this->addRouter(
            new Router(
                "/dashboard",
                "index",
                $this->dashboardControllerAPI->getController("DashboardController"),
            ),
        );

        $login = new Router(
            "/dashboard/action/login",
            "loginAction",
            $this->dashboardControllerAPI->getController("DashboardController"),
        );

        $login->setMethod("POST");
        $this->addRouter($login);

        $loginView = new Router(
            "/dashboard/login",
            "login",
            $this->dashboardControllerAPI->getController("DashboardController"),
        );

        $loginView->setMethod("GET");
        $this->addRouter($loginView);

        $logout = new Router(
            "/dashboard/logout",
            "logoutAction",
            $this->dashboardControllerAPI->getController("DashboardController"),
        );

        $this->addRouter($logout);

        $factory = $this->dashboardControllerAPI->getController("WidgetController");
        $this->addRouter((new Router("/dashboard/widgets", "index", $factory))->setMethod("GET"));
        foreach (["visibility", "layout", "reset"] as $action) {
            $this->addRouter(
                (new Router("/dashboard/widgets/" . $action, $action, clone $factory))->setMethod("POST"),
            );
        }
    }
}
