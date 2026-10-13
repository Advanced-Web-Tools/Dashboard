<?php
namespace Dashboard\api\widget;
final class WidgetUser
{
    public function __construct(public readonly int $id, public readonly int $permissionLevel)
    {
        if ($id < 1) {
            throw new \InvalidArgumentException("An authenticated admin is required.");
        }
    }
}
