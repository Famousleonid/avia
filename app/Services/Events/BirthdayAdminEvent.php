<?php

namespace App\Services\Events;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class BirthdayAdminEvent implements EventDefinition
{
    public function key(): string
    {
        return 'user.birthday.admin.' . now()->year;
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
        if (!$subject instanceof User) return [];

        return User::query()
            ->whereHas('role', fn ($q) => $q->where('name', 'Admin'))
            ->where('id', '!=', $subject->id)
            ->get(['id', 'name'])
            ->all();
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
            'event' => 'birthday.admin',
            'severity' => 'info',
            'title' => 'Birthday',
            'text' => "{$subject->name} has a birthday today 🎉 ({$age})",
            'ui' => [
                'birthday' => [
                    'user' => ['id' => $subject->id, 'name' => $subject->name],
                    'age' => $age,
                    'for' => 'admin',
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

