<?php
namespace Dashboard\service;
use admin\Admin;
use Dashboard\api\widget\WidgetUser;
final class WidgetAuthentication
{
    public function current(): ?WidgetUser
    {
        $admin = new Admin();
        if (!$admin->checkAuthentication() || !$admin->getKey() || $admin->permission_level === null) {
            return null;
        }
        return new WidgetUser($admin->getKey(), (int) $admin->permission_level);
    }
}
