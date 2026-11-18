<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database with the demo accounts and one demo course.
     * Safe to run more than once: nothing is duplicated.
     */
    public function run(): void
    {
        $this->call(DemoSeeder::class);
    }
}
