<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\SystemRole;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

class CreateSuperadminCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'komikini:create-superadmin
                            {--name= : The name of the superadmin}
                            {--email= : The email of the superadmin}
                            {--password= : The password (optional, prompted securely if omitted)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create or assign a superadmin user securely without default credentials';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $name = (string) ($this->option('name') ?: $this->ask('Nama superadmin'));
        $email = (string) ($this->option('email') ?: $this->ask('Email superadmin'));

        $validator = Validator::make([
            'name' => $name,
            'email' => $email,
        ], [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255'],
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        $user = User::where('email', $email)->first();

        if ($user) {
            if ($user->hasRole(SystemRole::SUPERADMIN->value)) {
                $this->info("User [{$email}] sudah memiliki role superadmin.");

                return self::SUCCESS;
            }

            $user->assignRole(SystemRole::SUPERADMIN->value);
            $this->info("Role superadmin berhasil diberikan kepada user terdaftar: {$email}");

            activity('rbac')
                ->performedOn($user)
                ->withProperties(['assigned_role' => SystemRole::SUPERADMIN->value])
                ->log('user.superadmin_assigned_cli');

            return self::SUCCESS;
        }

        $password = (string) ($this->option('password') ?: $this->secret('Password'));

        $passwordValidator = Validator::make([
            'password' => $password,
        ], [
            'password' => ['required', Password::defaults()],
        ]);

        if ($passwordValidator->fails()) {
            foreach ($passwordValidator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        $newUser = User::create([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make($password),
            'email_verified_at' => now(),
            'status' => 'active',
        ]);

        $newUser->assignRole(SystemRole::SUPERADMIN->value);

        activity('rbac')
            ->performedOn($newUser)
            ->withProperties(['created_superadmin' => $email])
            ->log('user.superadmin_created_cli');

        $this->info("Superadmin [{$email}] berhasil dibuat.");

        return self::SUCCESS;
    }
}
