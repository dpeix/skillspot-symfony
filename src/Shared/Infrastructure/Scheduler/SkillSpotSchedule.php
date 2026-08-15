<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Scheduler;

use App\Shared\Application\Message\CompleteEndedSessions;
use App\Shared\Application\Message\SendBookingReminders;
use Symfony\Component\Scheduler\Attribute\AsSchedule;
use Symfony\Component\Scheduler\RecurringMessage;
use Symfony\Component\Scheduler\Schedule;
use Symfony\Component\Scheduler\ScheduleProviderInterface;

#[AsSchedule('skillspot')]
final readonly class SkillSpotSchedule implements ScheduleProviderInterface
{
    public function getSchedule(): Schedule
    {
        return (new Schedule())
            ->add(RecurringMessage::every('10 minutes', new CompleteEndedSessions()))
            ->add(RecurringMessage::every('10 minutes', new SendBookingReminders()));
    }
}
