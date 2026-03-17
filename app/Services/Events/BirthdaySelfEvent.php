<?php

namespace App\Services\Events;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class BirthdaySelfEvent implements EventDefinition
{
    public function key(): string
    {
        return 'user.birthday.self.' . now()->year;
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
        return $subject instanceof User ? [$subject] : [];
    }

    public function message($subject): array
    {
        if (!$subject instanceof User) return [];

        $today = now()->startOfDay();
        $birth = $subject->birthday instanceof Carbon ? $subject->birthday : Carbon::parse($subject->birthday);
        $age = max(0, $birth->diffInYears($today));

        return [
            'fromUserId' => 0,
            'fromName' => 'System',
            'type' => 'birthday',
            'event' => 'birthday.self',
            'severity' => 'success',
            'title' => 'Happy Birthday!',
            'text' => "Happy Birthday, {$subject->name}! 🎉",
            'ui' => [
                'birthday' => [
                    'user' => ['id' => $subject->id, 'name' => $subject->name],
                    'age' => $age,
                    'for' => 'self',
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

