<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

/**
 * Creates the first (or an additional) admin account from the command line.
 * The password is typed hidden, never stored in .env or the repository.
 *
 *   php artisan admin:create
 */
class CreateAdminCommand extends Command
{
    protected $signature = 'admin:create';

    protected $description = 'Create an administrator account (interactive, password hidden)';

    public function handle(): int
    {
        $this->components->info('Create an administrator account');

        $name = $this->ask('Full name');
        $email = $this->ask('Email address');

        $password = $this->secret('Password (min 8 characters)');
        $confirm = $this->secret('Confirm password');

        $validator = Validator::make(
            ['name' => $name, 'email' => $email, 'password' => $password, 'password_confirmation' => $confirm],
            [
                'name' => ['required', 'string', 'max:120'],
                'email' => ['required', 'email', 'max:191', 'unique:users,email'],
                'password' => ['required', 'string', 'min:8', 'confirmed'],
            ]
        );

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $message) {
                $this->components->error($message);
            }

            return self::FAILURE;
        }

        User::create([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make($password),
            'role' => User::ROLE_ADMIN,
            'is_active' => true,
        ]);

        $this->components->info("Admin account created for {$email}. You can now log in.");

        return self::SUCCESS;
    }
}
