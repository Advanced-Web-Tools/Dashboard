<?php
namespace Dashboard;
final class install implements \installer\interfaces\package\actions\IPostInstall
{
    public function postInstall(int $packageId, string $packageName): bool
    {
        \Dashboard\service\WidgetSchema::install($packageId);
        return true;
    }
}
