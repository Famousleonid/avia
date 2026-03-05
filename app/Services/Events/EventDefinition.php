<?php
// app/Services/Events/EventDefinition.php
namespace App\Services\Events;

use Illuminate\Support\Collection;

interface EventDefinition
{
    public function key(): string;

    /** Вернуть список “сабджектов” (моделей), у которых событие “наступило” */
    public function dueSubjects(): Collection;

    /** Кому слать (может быть null) */
    public function recipient($subject): ?\App\Models\User;

    /** Текст/данные уведомления */
    public function message($subject): array;

    /** Как часто можно повторять (0 = только один раз) */
    public function repeatEveryMinutes(): int;
}
