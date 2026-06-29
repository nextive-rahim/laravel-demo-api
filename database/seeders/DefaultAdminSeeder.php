<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DefaultAdminSeeder extends Seeder
{
    /**
     * Seed the default admin account used by the admin website.
     */
    public function run(): void
    {
        User::updateOrCreate(
            ['phone' => '01718663032'],
            [
                'name' => 'Admin',
                'password' => '123456',
                'is_admin' => true,
            ],
        );
    }
}
