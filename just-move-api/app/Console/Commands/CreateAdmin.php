<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class CreateAdmin extends Command
{
    protected $signature = 'admin:create {email} {--name=Admin}';

    protected $description = 'Create (or update) a staff account that can sign in at /admin';

    public function handle(): int
    {
        $password = $this->secret('Password (min 8 characters)');
        if (strlen((string) $password) < 8) {
            $this->error('Password must be at least 8 characters.');
            return self::FAILURE;
        }

        $user = User::firstOrNew(['email' => $this->argument('email')]);
        $user->forceFill([
            'name' => $user->exists ? $user->name : $this->option('name'),
            'password' => $password,
            'is_admin' => true,
        ])->save();

        $this->info("Admin ready: {$user->email}");
        return self::SUCCESS;
    }
}
