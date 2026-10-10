<?php

namespace Database\Seeders;

use App\Models\Department;
use Illuminate\Database\Seeder;

/**
 * Seeds only the reference data the app needs before anyone can use it.
 * Admin accounts are NOT seeded (no default passwords): create one with
 * "php artisan admin:create".
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        foreach (['Engineering', 'Human Resources', 'Finance', 'Sales', 'Operations'] as $name) {
            Department::firstOrCreate(['name' => $name]);
        }
    }
}
