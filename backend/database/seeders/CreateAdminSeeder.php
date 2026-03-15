<?php

namespace Database\Seeders;

use App\Models\Admin;
use Illuminate\Database\Seeder;

class CreateAdminSeeder extends Seeder
{
    public function run(): void
    {
        $email = env('ADMIN_EMAIL');
        $password = env('ADMIN_PASSWORD');

        if (! is_string($email) || $email === '' || ! is_string($password) || $password === '') {
            $this->command?->warn('ADMIN_EMAIL or ADMIN_PASSWORD is missing; skipping admin seed.');

            return;
        }

        Admin::query()->updateOrCreate([
            'email' => $email,
        ], [
            'name' => 'Operator Admin',
            'password' => $password,
        ]);
    }
}
