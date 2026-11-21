<?php

namespace Dashboard\widgets;

use Dashboard\api\widget\DashboardWidget;
use URPC\api\{URPCBlock, URPCBlockDescriptor};

final class PinnedPagesWidget extends DashboardWidget
{
    public function widget(): DashboardWidget
    {
        $descriptor = new URPCBlockDescriptor('section');
        $descriptor->setSourceKey('Dashboard/widgets/pinned-pages');
        $this->setBlockDescriptor($descriptor);
        $this->setBlockAttributes(['class' => 'card min-w-0', 'data-pinned-pages' => '']);

        $title = new URPCBlock(new URPCBlockDescriptor('h2'));
        $title->setBlockAttributes(['class' => 'card-title']);
        $title->setBlockContent('Pinned pages');

        $list = new URPCBlock(new URPCBlockDescriptor('ul'));
        $list->setBlockAttributes(['class' => 'pinned-pages-list', 'data-pinned-list' => '', 'aria-label' => 'Pinned pages']);

        $select = new URPCBlock(new URPCBlockDescriptor('select'));
        $select->setBlockAttributes([
            'class' => 'form-select min-w-0', 'data-pinned-select' => '', 'aria-label' => 'Sidebar page to pin',
            'disabled' => 'disabled',
        ]);

        $button = new URPCBlock(new URPCBlockDescriptor('button'));
        $button->setBlockAttributes([
            'type' => 'button', 'class' => 'btn btn-outline', 'data-pinned-add' => '', 'disabled' => 'disabled',
        ]);
        $button->setBlockContent('Pin page');

        $controls = new URPCBlock(new URPCBlockDescriptor('div'));
        $controls->setBlockAttributes(['class' => 'd-flex flex-wrap align-items-center gap-2 mt-3']);
        $controls->setBlockChildren([$select, $button]);

        $status = new URPCBlock(new URPCBlockDescriptor('p'));
        $status->setBlockAttributes(['class' => 'text-small text-muted mt-2 mb-0', 'data-pinned-status' => '', 'role' => 'status']);
        $status->setBlockContent('Loading pinned pages…');

        $this->setBlockChildren([$title, $list, $controls, $status]);
        return $this;
    }
}
