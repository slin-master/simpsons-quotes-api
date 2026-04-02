<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::query()->updateOrCreate(
            ['username' => 'springfield-demo'],
            [
                'name' => 'Springfield Demo',
                'email' => 'springfield-demo@example.test',
                'password' => 'Springfield123!',
            ],
        );

        User::query()->updateOrCreate(
            ['username' => 'springfield-demo-2'],
            [
                'name' => 'Springfield Demo Two',
                'email' => 'springfield-demo-2@example.test',
                'password' => 'Springfield123!',
            ],
        );
    }
}
