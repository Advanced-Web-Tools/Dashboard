<?php
/*
 * Copyright (c) 2026 Stefan Crkvenjakov.
 * Part of the Dashboard package for AWT CMS.
 * GitHub: https://github.com/Advanced-Web-Tools/Dashboard
 * File: NavigationContainer.php
 */

namespace Dashboard\api\navigation;

use admin\Admin;
use DOMDocument;
use DOMException;

class NavigationContainer
{
    /** @var NavigationItem[] */
    private array $navigationItems;

    private string $currentPath;
    private int $groupId = 0;
    private ?Admin $admin;

    public function __construct(
        array $navigationItems = [],
        ?string $requestUri = null,
        ?Admin $admin = null,
    ) {
        $this->navigationItems = $navigationItems;
        $this->admin = $admin;

        $this->currentPath = $this->normalizePath(
            $requestUri ?? ($_SERVER['REQUEST_URI'] ?? '/')
        );
    }

    public function addNavigationItem(
        NavigationItem $navigationItem
    ): void {
        $this->navigationItems[] = $navigationItem;
    }

    /** @return NavigationItem[] */
    public function getNavigationItems(): array
    {
        return $this->orderItems($this->filterItems($this->navigationItems));
    }

    /**
     * @throws DOMException
     */
    public function getHtml(): string
    {
        $dom = $this->buildContainer();

        return $dom->saveHTML($dom->documentElement) ?: '';
    }

    /**
     * @throws DOMException
     */
    public function buildContainer(): DOMDocument
    {
        $this->groupId = 0;

        $dom = new DOMDocument('1.0', 'UTF-8');

        $nav = $dom->createElement('nav');
        $nav->setAttribute('class', 'dashboard-nav');
        $nav->setAttribute('aria-label', 'Main navigation');

        $dom->appendChild($nav);

        [$list] = $this->buildList($dom, $this->getNavigationItems());

        $nav->appendChild($list);

        return $dom;
    }

    /**
     * @param NavigationItem[] $items
     * @return array{0: \DOMElement, 1: bool}
     * @throws DOMException
     */
    private function buildList(DOMDocument $dom, array $items): array
    {
        $list = $dom->createElement('ul');
        $list->setAttribute('class', 'nav-list');

        $containsActive = false;

        foreach ($this->orderItems($items) as $item) {
            $url = $item->getUrl();
            $hasUrl = $url !== null && $url !== '';

            $active = $hasUrl
                && str_starts_with($url, '/')
                && !str_starts_with($url, '//')
                && $this->normalizePath($url) === $this->currentPath;

            $children = $item->getChildren();

            [$childList, $childActive] = $this->buildList(
                $dom,
                $children
            );

            $expanded = $active || $childActive;
            $containsActive = $containsActive || $expanded;

            $li = $dom->createElement('li');
            $li->setAttribute('class', 'nav-item');

            $row = $dom->createElement('div');
            $row->setAttribute('class', 'nav-row');

            $tag = $hasUrl ? 'a' : ($children ? 'button' : 'span');

            $label = $dom->createElement($tag);
            $label->setAttribute(
                'class',
                $hasUrl ? 'nav-link' : 'nav-parent'
            );

            if ($hasUrl) {
                $label->setAttribute('href', $url);

                if ($active) {
                    $label->setAttribute('aria-current', 'page');
                }
            }

            if ($item->getIcon() !== null) {
                $icon = $dom->createElement('img');
                $icon->setAttribute('src', $item->getIcon());
                $icon->setAttribute('class', 'nav-icon');
                $icon->setAttribute('alt', '');

                $label->appendChild($icon);
            }

            $text = $dom->createElement('span');
            $text->setAttribute('class', 'nav-label');
            $text->appendChild(
                $dom->createTextNode($item->getLabel())
            );

            $label->appendChild($text);
            $row->appendChild($label);
            $li->appendChild($row);

            if ($children) {
                $id = 'nav-group-' . ++$this->groupId;

                $toggle = $hasUrl
                    ? $dom->createElement('button')
                    : $label;

                $toggle->setAttribute(
                    'class',
                    $hasUrl ? 'nav-toggle' : 'nav-parent nav-toggle'
                );

                $toggle->setAttribute('type', 'button');
                $toggle->setAttribute('aria-controls', $id);
                $toggle->setAttribute(
                    'aria-expanded',
                    $expanded ? 'true' : 'false'
                );

                if ($hasUrl) {
                    $toggle->setAttribute(
                        'aria-label',
                        'Toggle ' . $item->getLabel()
                    );
                }

                $chevron = $dom->createElement('i');
                $chevron->setAttribute('class', 'nav-chevron fa-solid fa-chevron-right');
                $chevron->setAttribute('aria-hidden', 'true');

                $toggle->appendChild($chevron);

                if ($hasUrl) {
                    $row->appendChild($toggle);
                }

                $wrapper = $dom->createElement('div');
                $wrapper->setAttribute('id', $id);
                $wrapper->setAttribute('class', 'nav-children');

                if (!$expanded) {
                    $wrapper->setAttribute('hidden', '');
                }

                $wrapper->appendChild($childList);
                $li->appendChild($wrapper);
            }

            $list->appendChild($li);
        }

        return [$list, $containsActive];
    }

    /**
     * @param NavigationItem[] $items
     * @return NavigationItem[]
     */
    private function filterItems(array $items): array
    {
        $visible = [];
        foreach ($items as $item) {
            $level = $item->getPermissionLevel();
            if ($level !== null) {
                $admin = $this->admin ??= new Admin();
                if (!$admin->checkAuthentication() || $admin->permission_level === null || !$admin->checkPermission($level)) {
                    continue;
                }
            }

            $children = $this->filterItems($item->getChildren());
            if ($item->getChildren() !== [] && $children === [] && !$item->getUrl()) {
                continue;
            }

            // Event listeners may reuse items; keep their original children intact.
            $filtered = clone $item;
            $filtered->setChildren($children);
            $visible[] = $filtered;
        }
        return $visible;
    }

    /** @param NavigationItem[] $items @return NavigationItem[] */
    private function orderItems(array $items): array
    {
        $start = [];
        $middle = [];
        $end = [];

        foreach ($items as $item) {
            if ($item->getPosition() === NavigationItem::START) {
                $start[] = $item;
            } elseif ($item->getPosition() === NavigationItem::END) {
                $end[] = $item;
            } else {
                $middle[] = $item;
            }
        }

        return array_merge($start, $middle, $end);
    }

    private function normalizePath(string $uri): string
    {
        $path = parse_url($uri, PHP_URL_PATH);

        return is_string($path)
            ? (rtrim($path, '/') ?: '/')
            : '/';
    }
}
