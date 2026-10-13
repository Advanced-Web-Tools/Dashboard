<?php

namespace Dashboard\widgets;

use Dashboard\api\widget\DashboardWidget;
use URPC\api\{URPCBlock, URPCBlockDescriptor};

final class RecentlyVisitedWidget extends DashboardWidget
{
    public function widget(): DashboardWidget
    {
        $descriptor = new URPCBlockDescriptor('section');
        $descriptor->setSourceKey('Dashboard/widgets/recently-visited');
        $this->setBlockDescriptor($descriptor);
        $this->setBlockAttributes(['class' => 'card min-w-0', 'data-recently-visited' => '']);

        $title = new URPCBlock(new URPCBlockDescriptor('h2'));
        $title->setBlockAttributes(['class' => 'card-title']);
        $title->setBlockContent('Recently visited');

        $description = new URPCBlock(new URPCBlockDescriptor('p'));
        $description->setBlockAttributes(['class' => 'card-subtitle text-small mb-3']);
        $description->setBlockContent('Jump back to a page you recently opened.');

        $list = new URPCBlock(new URPCBlockDescriptor('ul'));
        $list->setBlockAttributes(['class' => 'recent-pages', 'data-recent-list' => '', 'aria-label' => 'Recently visited pages, newest first']);

        $status = new URPCBlock(new URPCBlockDescriptor('p'));
        $status->setBlockAttributes(['class' => 'recent-pages-status text-small text-muted mb-0', 'data-recent-status' => '', 'role' => 'status']);
        $status->setBlockContent('No recent pages yet. Open a page from the sidebar, then return here to quickly find it again.');

        $clear = new URPCBlock(new URPCBlockDescriptor('button'));
        $clear->setBlockAttributes(['type' => 'button', 'class' => 'btn btn-ghost btn-sm', 'data-recent-clear' => '', 'hidden' => 'hidden']);
        $clear->setBlockContent('Clear history');
        $undo = new URPCBlock(new URPCBlockDescriptor('button'));
        $undo->setBlockAttributes(['type' => 'button', 'class' => 'btn btn-ghost btn-sm', 'data-recent-undo' => '', 'hidden' => 'hidden']);
        $undo->setBlockContent('Undo clear');
        $hint = new URPCBlock(new URPCBlockDescriptor('small'));
        $hint->setBlockAttributes(['class' => 'text-muted']);
        $hint->setBlockContent('History on this browser');
        $footer = new URPCBlock(new URPCBlockDescriptor('footer'));
        $footer->setBlockAttributes(['class' => 'd-flex flex-wrap align-items-center justify-content-between gap-1 mt-3 pt-2 border-top']);
        $footer->setBlockChildren([$hint, $clear, $undo]);
        $this->setBlockChildren([$title, $description, $list, $status, $footer]);
        return $this;
    }
}
