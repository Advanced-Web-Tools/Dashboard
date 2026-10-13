<?php
/*
 * Copyright (c) 2026 Stefan Crkvenjakov.
 * Part of the Dashboard package for AWT CMS.
 * Github: https://github.com/Advanced-Web-Tools/Dashboard
 * File: DashboardController.php
 */

namespace Dashboard\controllers;
use admin\Admin;
use admin\AdminAuthentication;
use Dashboard\api\page\DashboardControllerBase;
use Dashboard\api\widget\WidgetDocument;
use Dashboard\service\WidgetRenderer;
use DOMException;
use Exception;
use redirect\Redirect;
use response\Response;
use router\http\Request;
use view\View;

class DashboardController extends DashboardControllerBase
{
    public string $navigation;
    public string $name;
    public string $fullname;
    public array $widgetState;
    public string $widgets;
    /**
     * @throws DOMException
     * @throws Exception
     */
    public function index(array|string $params): View
    {
        $this->checkAuth();
        $params["navigation"] = $this->buildNavigation();
        $params["name"] = $this->packageName;
        $admin = new Admin();
        $params["fullname"] = $admin->firstname . " " . $admin->lastname;
        $user = (new \Dashboard\service\WidgetAuthentication())->current();
        if ($user === null) {
            header("Location: /dashboard/login", true, 302);
            exit();
        }
        $widgets = new \Dashboard\service\WidgetService($this->eventDispatcher, $user);
        $params["widgetState"] = $widgets->state();
        $params["widgets"] = $widgets->html($params["widgetState"]);
        return $this->view($params);
    }

    public function login(array|string $params): View
    {
        $params["name"] = WEB_NAME;
        return $this->view($params);
    }

    public function loginAction(array|string $params): Redirect
    {
        $username = $_POST["username"];
        $password = $_POST["password"];

        $admin = new AdminAuthentication($username, $password);
        $res = $admin->authenticate();

        if ($res) {
            return (new Redirect())->redirect("/dashboard");
        }

        return (new Redirect())->redirect("/dashboard/login?error=invalid_credentials");
    }

    public function logoutAction(array|string $params): Redirect
    {
        $this->checkAuth();

        $admin = new Admin();
        $admin->logout();

        return (new Redirect())->redirect("/dashboard/login");
    }
}
