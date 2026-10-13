<?php
namespace Dashboard;
final class update implements \installer\interfaces\package\actions\IPostUpdate
{
    public function postUpdate(int $packageId, string $packageName): void
    {
        \Dashboard\service\WidgetSchema::install($packageId);
    }
}
