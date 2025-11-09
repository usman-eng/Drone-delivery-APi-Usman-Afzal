<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Role::firstOrCreate(['name'=>'admin']);
        Role::firstOrCreate(['name'=>'enduser']);
        Role::firstOrCreate(['name'=>'drone']);
    }
}
