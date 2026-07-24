<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        Role::firstOrCreate(['name' => 'admin']);
        Role::firstOrCreate(['name' => 'user']);

        $owner = User::query()->firstOrCreate(
            ['email' => config('jobhunter.owner.email')],
            [
                'name' => config('jobhunter.owner.name'),
                'password' => Hash::make(config('jobhunter.owner.password')),
            ],
        );

        if (! $owner->hasRole('admin')) {
            $owner->assignRole('admin');
        }
    }
}
