<?php

/*
 * Copyright (c) 2026 Stefan Crkvenjakov.
 * Part of the Dashboard package for AWT CMS.
 * Github: https://github.com/Advanced-Web-Tools/Dashboard
 * File: NavigationItem.php
 */

namespace Dashboard\api\navigation;

class NavigationItem
{
    private string $label;
    private ?string $url;
    private ?string $icon;
    private array $children;
    private string $position = '';
    private ?int $permissionLevel;

    const START = "start";
    const END = "end";

    public function __construct(string $label = "", ?string $url = null, ?string $icon = null, array $children = [], ?int $permissionLevel = null)
    {
        $this->label = $label;
        $this->url = $url;
        $this->icon = $icon;
        $this->children = $children;
        $this->permissionLevel = $permissionLevel;
    }

    public function setLabel(string $label): void
    {
        $this->label = $label;
    }

    public function setUrl(?string $url): void
    {
        $this->url = $url;
    }

    public function setIcon(?string $icon): void
    {
        $this->icon = $icon;
    }

    public function addChild(NavigationItem $child): void
    {
        $this->children[] = $child;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function getIcon(): ?string
    {
        return $this->icon;
    }

    public function getChildren(): array
    {
        return $this->children;
    }

    /** @param NavigationItem[] $children */
    public function setChildren(array $children): void
    {
        $this->children = $children;
    }

    public function setPermissionLevel(?int $permissionLevel): void
    {
        $this->permissionLevel = $permissionLevel;
    }

    public function getPermissionLevel(): ?int
    {
        return $this->permissionLevel;
    }

    public function setPosition(string $position): void
    {
        $this->position = $position;
    }

    public function getPosition(): string
    {
        return $this->position;
    }
}
