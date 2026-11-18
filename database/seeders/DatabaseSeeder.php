<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::query()->updateOrCreate(
            ['email' => 'admin@provador.test'],
            ['name' => 'Super Admin', 'password' => 'password', 'is_super_admin' => true],
        );

        $this->call(DemoStoreSeeder::class);
    }
}
