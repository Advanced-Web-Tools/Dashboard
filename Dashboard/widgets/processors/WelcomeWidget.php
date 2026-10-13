<?php

namespace Dashboard\widgets\processors;

use admin\model\AdminModel;
use DateTimeImmutable;
use URPC\api\contracts\IURPCBlock;
use URPC\api\contracts\IURPCBlockRenderer;
use URPC\service\renderer\URPC;

class WelcomeWidget implements IURPCBlockRenderer
{
    public function process(IURPCBlock $block, URPC $urpc): void
    {
        $now = new DateTimeImmutable();
        $active = new AdminModel();

        $firstname = trim((string) ($active->firstname ?? ''));
        $greeting = $this->greeting($now);

        if ($firstname !== '') {
            $greeting .= ', ' . $firstname;
        }

        $values = [
            'date' => $now->format('l, F j, Y'),
            'greeting' => $greeting . '.',
            'message' => $this->welcomeMessage($now),
            'tip' => $this->dailyTip($now),
        ];

        $this->populate($block, $values, $now);
    }

    private function populate(
        IURPCBlock $block,
        array $values,
        DateTimeImmutable $now
    ): void {
        $role = $block->getBlockDescriptor()
            ->getBlockProps()['welcome_role'] ?? null;

        if (is_string($role) && array_key_exists($role, $values)) {
            $block->setBlockContent($values[$role]);

            if ($role === 'date') {
                $block->setBlockAttributes(array_replace(
                    $block->getBlockAttributes(),
                    ['datetime' => $now->format('Y-m-d')]
                ));
            }
        }

        foreach ($block->getBlockChildren() as $child) {
            $this->populate($child, $values, $now);
        }
    }

    private function pick(array $messages): string
    {
        return $messages[array_rand($messages)];
    }

    private function greeting(DateTimeImmutable $now): string
    {
        $hour = (int) $now->format('G');

        return match (true) {
            $hour < 5 => 'Hello',
            $hour < 12 => 'Good morning',
            $hour < 18 => 'Good afternoon',
            default => 'Good evening',
        };
    }

    private function welcomeMessage(DateTimeImmutable $now): string
    {
        $hour = (int) $now->format('G');
        $day = (int) $now->format('N');

        if ($hour < 5) {
            return $this->pick([
                'Working late? Pick up where you left off.',
                'A quiet moment to work on your next idea.',
                'Your workspace is ready whenever you are.',
            ]);
        }

        if ($day === 1) {
            return $this->pick([
                'A new week, a fresh start. What would you like to work on?',
                'Start the week with one small improvement to your website.',
                'Ready for the week ahead? Your workspace is right here.',
            ]);
        }

        if ($day === 5) {
            return $this->pick([
                'A good moment to review what you have worked on this week.',
                'Finish that last detail, or leave yourself a note for next week.',
                'What would you like to wrap up today?',
            ]);
        }

        if ($day >= 6) {
            return $this->pick([
                'Trying something new this weekend?',
                'A little time for your website can go a long way.',
                'Pick up an unfinished idea, or start something new.',
            ]);
        }

        return $this->pick([
            'Pick up where you left off, or start something new.',
            'What would you like to work on today?',
            'Your next update starts here.',
            'A small change today can make your website a little better.',
            'Take a look around and choose your next task.',
        ]);
    }

    private function dailyTip(DateTimeImmutable $now): string
    {
        $tips = [
            'Preview your changes before publishing them.',
            'Keep page titles short, clear, and easy to recognize.',
            'Check how your pages look on a smaller screen.',
            'Give images descriptive alternative text.',
            'Review your contact details from time to time.',
            'Clear navigation helps visitors find what they need.',
            'Make a backup before making major changes.',
            'Remove outdated content when it no longer helps your visitors.',
            'Optimize large images to help pages load faster.',
            'Check that your links still lead to the right pages.',
            'Use descriptive link text so visitors know what to expect.',
            'Keep important information easy to find.',
        ];

        $day = (int) $now->format('z');

        return $tips[$day % count($tips)];
    }
}