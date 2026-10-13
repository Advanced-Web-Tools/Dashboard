<?php

/*
 * Copyright (c) 2026 Stefan Crkvenjakov.
 * Part of the Dashboard package for AWT CMS.
 * Github: https://github.com/Advanced-Web-Tools/Dashboard
 * File: main.php
 */
namespace Dashboard;

use Dashboard\api\events\NavigationResponseEvent;
use Dashboard\api\events\WidgetRegistrationListener;
use Dashboard\api\events\WidgetRegistryEvent;
use Dashboard\api\navigation\NavigationItem;
use Dashboard\api\widget\DashboardWidget;
use Dashboard\api\widget\WidgetDefinition;
use Dashboard\cli\AdminCreationCommand;
use Dashboard\widgets\PersonalNotesWidget;
use Dashboard\widgets\RecentlyVisitedWidget;
use Dashboard\widgets\WelcomeWidget;
use object\ObjectFactory;
use runtime\api\RuntimeLinkerAPI;
use runtime\enums\ERuntimeFlags;
use vfs\resource\exceptions\ResourceException;

final class main extends RuntimeLinkerAPI
{
    private NavigationItem $navigationItem;

    public function environmentSetup(): void
    {
        parent::environmentSetup();
        $this->setRuntimeFlag(ERuntimeFlags::EventDispatcher);
        $this->setRuntimeFlag(ERuntimeFlags::CommandProvider);
        $this->waitForPackage("URPC");
    }

    /**
     * @throws ResourceException
     */
    public function setup(): void
    {
        $icon = "/awt_packages/Dashboard/data/icon/icon.png";
        $this->navigationItem = new NavigationItem("Dashboard", "/dashboard", $icon);
        $this->navigationItem->setPosition(NavigationItem::START);
    }

    public function main(): void
    {
        $this->addCommand(new AdminCreationCommand());

        $this->registerLinks();
        $this->registerNavigation();
        $this->registerWidgets();
    }

    private function registerLinks(): void
    {
        $this->createLink("DashboardControllerAPI", "/runtime/DashboardControllerAPI.php");
        $this->createLink("DashboardRouter", "/runtime/DashboardRouter.php");
    }

    private function registerNavigation(): void
    {
        $this->eventDispatcher->addListener(
            NavigationResponseEvent::NAME,
            new NavigationResponseEvent($this->navigationItem),
        );
    }

    private function registerWidgets(): void
    {
        $definitions = [
            new WidgetDefinition(
                key: "Dashboard/welcome",
                packageName: "Dashboard",
                label: "Welcome",
                description: "A personal greeting, today’s date and a useful daily tip.",
                factory: $this->widgetFactory(WelcomeWidget::class),
                defaultVisible: true,
                defaultPosition: 0,
                size: DashboardWidget::SIZE_LARGE,
            ),
            new WidgetDefinition(
                key: "Dashboard/personal-notes",
                packageName: "Dashboard",
                label: "Personal notes",
                description: "Your personal reminders and ideas, saved in this browser.",
                factory: $this->widgetFactory(PersonalNotesWidget::class),
                defaultVisible: true,
                defaultPosition: 10,
                size: DashboardWidget::SIZE_MEDIUM,
            ),
            new WidgetDefinition(
                key: "Dashboard/recently-visited",
                packageName: "Dashboard",
                label: "Recently visited",
                description: "Your six most recently visited navigation pages in this browser.",
                factory: $this->widgetFactory(RecentlyVisitedWidget::class),
                defaultVisible: true,
                defaultPosition: 20,
                size: DashboardWidget::SIZE_MEDIUM,
            ),
        ];

        foreach ($definitions as $definition) {
            $this->eventDispatcher->addListener(
                WidgetRegistryEvent::NAME,
                new WidgetRegistrationListener($definition),
            );
        }
    }

    /** @param class-string<DashboardWidget> $class */
    private function widgetFactory(string $class): ObjectFactory
    {
        return (new ObjectFactory())
            ->setClassName($class)
            ->setType(DashboardWidget::class);
    }
}
