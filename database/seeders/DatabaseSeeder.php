<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(RolesAndPermissionsSeeder::class);

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
