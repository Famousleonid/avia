<?php

namespace App\Services\Events;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class BirthdayInTwoDaysEvent implements EventDefinition
{
    public function key(): string
    {
        return 'user.birthday_2days';
    }

    public function dueSubjects(): Collection
    {
        return User::query()
            ->whereNotNull('birthday')
            ->get(['id', 'name', 'birthday']);
    }

    public function recipients($subject): array
    {
        return [];
    }

    public function message($subject): array
    {
        if (! $subject instanceof User) {
            return [];
        }

        $today = now()->startOfDay();
        $birth = $subject->birthday instanceof Carbon ? $subject->birthday : Carbon::parse($subject->birthday);
        $nextBirthday = Carbon::create(
            (int) $today->year,
            (int) $birth->month,
            (int) $birth->day
        )->startOfDay();

        if ($nextBirthday->lt($today)) {
            $nextBirthday->addYear();
        }

        $age = max(0, $birth->diffInYears($nextBirthday));

        return [
            'fromUserId' => 0,
            'fromName' => 'System',
            'type' => 'birthday',
            'event' => 'birthday_2days',
            'severity' => 'info',
            'title' => 'Birthday in 2 days',
            'text' => "{$subject->name} has a birthday in 2 days.",
            'ui' => [
                'birthday' => [
                    'user' => ['id' => $subject->id, 'name' => $subject->name],
                    'age' => $age,
                    'days_until' => 2,
                ],
            ],
            'payload' => [],
            'url' => null,
        ];
    }

    public function repeatEveryMinutes(): int
    {
        return 0;
    }

    public function shouldRun($subject): bool
    {
        if (! $subject instanceof User || ! $subject->birthday) {
            return false;
        }

        $today = now()->startOfDay();
        $nextBirthday = Carbon::create(
            (int) $today->year,
            (int) $subject->birthday->month,
            (int) $subject->birthday->day
        )->startOfDay();

        if ($nextBirthday->lt($today)) {
            $nextBirthday->addYear();
        }

        return $today->diffInDays($nextBirthday, false) === 2;
    }
}
