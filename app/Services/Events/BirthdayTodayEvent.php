<?php

namespace App\Services\Events;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class BirthdayTodayEvent implements EventDefinition
{
    public function key(): string
    {
        return 'user.birthday_today';
    }

    public function dueSubjects(): Collection
    {
        $today = now();

        return User::query()
            ->whereNotNull('birthday')
            ->whereMonth('birthday', (int) $today->month)
            ->whereDay('birthday', (int) $today->day)
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
        $age = max(0, $birth->diffInYears($today));

        return [
            'fromUserId' => 0,
            'fromName' => 'System',
            'type' => 'birthday',
            'event' => 'birthday_today',
            'severity' => 'success',
            'title' => 'Birthday today',
            'text' => "Today is {$subject->name}'s birthday.",
            'ui' => [
                'birthday' => [
                    'user' => ['id' => $subject->id, 'name' => $subject->name],
                    'age' => $age,
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
}
