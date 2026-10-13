<?php

namespace Dashboard\widgets;

use Dashboard\api\widget\DashboardWidget;
use URPC\api\{URPCBlock, URPCBlockDescriptor};

final class PersonalNotesWidget extends DashboardWidget
{
    public function widget(): DashboardWidget
    {
        $descriptor = new URPCBlockDescriptor('section');
        $descriptor->setSourceKey('Dashboard/widgets/personal-notes');
        $this->setBlockDescriptor($descriptor);
        $this->setBlockAttributes(['class' => 'card min-w-0', 'data-personal-notes' => '']);

        $title = new URPCBlock(new URPCBlockDescriptor('h2'));
        $title->setBlockAttributes(['class' => 'card-title']);
        $title->setBlockContent('Personal notes');

        $description = new URPCBlock(new URPCBlockDescriptor('p'));
        $description->setBlockAttributes(['class' => 'card-subtitle text-small mb-3']);
        $description->setBlockContent('A reminder, an idea, or your next task. Saved in this browser.');

        $label = new URPCBlock(new URPCBlockDescriptor('label'));
        $label->setBlockAttributes(['for' => 'personal-notes-text', 'class' => 'visually-hidden']);
        $label->setBlockContent('Your personal notes');

        $input = new URPCBlock(new URPCBlockDescriptor('textarea'));
        $input->setBlockAttributes([
            'id' => 'personal-notes-text', 'class' => 'form-control',
            'data-notes-input' => '', 'rows' => '6', 'maxlength' => '4000',
            'placeholder' => 'Write something to remember…',
            'aria-describedby' => 'personal-notes-status',
        ]);

        $status = new URPCBlock(new URPCBlockDescriptor('p'));
        $status->setBlockAttributes([
            'id' => 'personal-notes-status', 'class' => 'text-small text-muted mt-2 mb-0',
            'data-notes-status' => '', 'role' => 'status', 'aria-live' => 'polite',
        ]);
        $status->setBlockContent('Loading your notes…');
        $this->setBlockChildren([$title, $description, $label, $input, $status]);
        return $this;
    }
}
