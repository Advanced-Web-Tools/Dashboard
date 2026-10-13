<?php
namespace Dashboard\api\widget;
use object\ObjectFactory;
final class WidgetDefinition
{
    public function __construct(
        public readonly string $key,
        public readonly string $packageName,
        public readonly string $label,
        public readonly string $description,
        public readonly ObjectFactory $factory,
        public readonly bool $defaultVisible = true,
        public readonly int $defaultPosition = 100,
        public readonly string $size = DashboardWidget::SIZE_LARGE,
        public readonly ?int $permissionLevel = null,
    ) {
        if (
            !preg_match('~^[A-Za-z][A-Za-z0-9_]{0,63}/[a-zA-Z0-9][a-zA-Z0-9._-]{0,63}$~D', $key) ||
            explode("/", $key)[0] !== $packageName
        ) {
            throw new \InvalidArgumentException("Widget key must be PackageName/stable-key.");
        }
        if (trim($label) === "" || strlen($label) > 120 || strlen($description) > 600) {
            throw new \InvalidArgumentException("Invalid widget metadata.");
        }
        if (
            !in_array(
                $size,
                [DashboardWidget::SIZE_SMALL, DashboardWidget::SIZE_MEDIUM, DashboardWidget::SIZE_LARGE],
                true,
            )
        ) {
            throw new \InvalidArgumentException("Invalid widget size.");
        }
    }
    public function availableFor(WidgetUser $user): bool
    {
        return $this->permissionLevel === null || $user->permissionLevel <= $this->permissionLevel;
    }
    public function create(): DashboardWidget
    {
        $widget = (clone $this->factory)->create();
        if (!($widget instanceof DashboardWidget)) {
            throw new \LogicException("Widget factory must return DashboardWidget.");
        }
        $result = $widget->widget();
        $result->setSize($this->size);
        return $result;
    }
}
