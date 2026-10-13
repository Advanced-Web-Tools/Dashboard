<?php
/*
 * Copyright (c) 2026 Stefan Crkvenjakov.
 * Part of the Dashboard package for AWT CMS.
 * Github: https://github.com/Advanced-Web-Tools/Dashboard
 * File: DashboardControllerAPI.php
 */

use controller\Controller;
use object\ObjectFactory;
use runtime\api\RuntimeControllerAPI;
use runtime\enums\ERuntimeFlags;

final class DashboardControllerAPI extends RuntimeControllerAPI
{
    private ObjectFactory $factory;

    /**
     * @throws Exception
     */

    public function environmentSetup(): void
    {
        parent::environmentSetup();
        $this->setRuntimeFlag(ERuntimeFlags::EventDispatcher);
    }

    /**
     * @throws Exception
     */
    public function setup(): void
    {
        $props = [
            "controllerName" => "DashboardController",
            "localAssetPath" => "awt_packages/Dashboard/views/assets",
            "eventDispatcher" => $this->eventDispatcher,
        ];

        $methods = ["setRootPath", "setViewPath"];

        $methodArgs = [
            "setRootPath" => [$this->runtimePath],
            "setViewPath" => ["/views/"],
        ];

        $objectFactory = new ObjectFactory();
        $objectFactory->setType(Controller::class);
        $objectFactory->setClassPath($this->runtimePath . "/controllers/DashboardController.php");

        $objectFactory->setProperties($props);
        $objectFactory->setMethodCalls($methods);
        $objectFactory->setMethodArgs($methodArgs);

        $this->factory = $objectFactory;
    }

    /**
     * @throws Exception
     */
    public function main(): void
    {
        $this->addController($this->factory, "DashboardController");
        $widgets = (new ObjectFactory())
            ->setClassName(\Dashboard\controllers\WidgetController::class)
            ->setType(Controller::class)
            ->setProperties(["eventDispatcher" => $this->eventDispatcher]);
        $this->addController($widgets, "WidgetController");
    }
}
