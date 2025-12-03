<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class CreateAdminUser extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'user:create-admin
                            {--name= : The name of the user}
                            {--email= : The email address of the user}
                            {--password= : The password for the user}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new admin user';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        try {
            // Get parameters or prompt for them
            $name = $this->option('name') ?: $this->ask('What is the user\'s name?');
            $email = $this->option('email') ?: $this->ask('What is the user\'s email?');
            $password = $this->option('password') ?: $this->secret('What is the user\'s password?');

            // Validate inputs
            $validator = Validator::make([
                'name' => $name,
                'email' => $email,
                'password' => $password,
            ], [
                'name' => 'required|string|max:255',
                'email' => 'required|email|max:255',
                'password' => 'required|string|min:8',
            ]);

            if ($validator->fails()) {
                foreach ($validator->errors()->all() as $error) {
                    $this->error($error);
                }
                return 1;
            }

            // Check if email already exists
            if (User::where('email', $email)->exists()) {
                $this->error("A user with email {$email} already exists");
                return 1;
            }

            // Create user with hashed password
            $user = User::create([
                'name' => $name,
                'email' => $email,
                'password' => Hash::make($password),
            ]);

            // Display confirmation message (excluding password)
            $this->info('User created successfully!');
            $this->line('');
            $this->line("Name: {$user->name}");
            $this->line("Email: {$user->email}");
            $this->line("ID: {$user->id}");

            return 0;
        } catch (\Exception $e) {
            $this->error('An error occurred while creating the user: ' . $e->getMessage());
            return 1;
        }
    }
}
