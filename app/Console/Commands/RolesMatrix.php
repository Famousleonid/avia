<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Gate;
use App\Models\User;

class RolesMatrix extends Command
{
    protected $signature = 'role:matrix {--user=}';
    protected $description = '';

    public function handle()
    {
        $this->info("Role matrix\n");

        $usersQuery = User::with('role');

        if ($this->option('user')) {
            $usersQuery->where('id', $this->option('user'));
        }

        $users = $usersQuery->get();

        if ($users->isEmpty()) {
            $this->error("User not found");
            return Command::FAILURE;
        }

        $abilities = array_keys(Gate::abilities());

        foreach ($users as $user) {
            $this->newLine();
            $this->line('──────────────────────────────────────────────────────────');
            $this->info("User: {$user->name}  (role: {$user->role->name})");
            $this->line('──────────────────────────────────────────────────────────');

            $rows = [];

            foreach ($abilities as $ability) {
                $allowed = Gate::forUser($user)->allows($ability);
                $rows[] = [
                    'ability' => $ability,
                    'status'  => $allowed ? '✔' : '✖',
                ];
            }

            $this->table(['Action', 'access'], $rows);
        }

        return Command::SUCCESS;
    }
}
