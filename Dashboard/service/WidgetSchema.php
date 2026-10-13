<?php
namespace Dashboard\service;
use database\creator\{TableWizard, ColumnCreator};
final class WidgetSchema
{
    public static function install(int $packageId, ?TableWizard $wizard = null): void
    {
        $wizard ??= TableWizard::create($packageId);
        if ($wizard->tableExists("dashboard_widget_layout")) {
            return;
        }
        $ok = $wizard
            ->addColumn(
                ColumnCreator::INT("admin_id")->primary()->foreignKey("awt_admin", "id", onDelete: "CASCADE"),
            )
            ->addColumn(ColumnCreator::LONGTEXT("payload"))
            ->addColumn(ColumnCreator::INT("revision")->unsigned()->default("0"))
            ->addColumn(ColumnCreator::DATETIME("updated_at"))
            ->createTable("dashboard_widget_layout");
        if (!$ok) {
            throw new \RuntimeException("Could not create dashboard widget layout table.");
        }
    }
}
