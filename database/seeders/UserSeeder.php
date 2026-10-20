<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public const USERS = [
        [
            'name' => 'Alice Agent',
            'email' => 'alice@example.com',
            'token' => 'alice-9f2c4d7e1b6a8c3d5e7f9a1b2c4d6e8f',
        ],
        [
            'name' => 'Bob Broker',
            'email' => 'bob@example.com',
            'token' => 'bob-3a7b9c1d5e2f4a6b8c0d1e3f5a7b9c2d',
        ],
    ];

    public function run(): void
    {
        foreach (self::USERS as $data) {
            $user = User::query()->updateOrCreate(
                ['email' => $data['email']],
                ['name' => $data['name'], 'password' => Hash::make('password')]
            );

            $user->tokens()->updateOrCreate(
                ['name' => 'api'],
                ['token' => hash('sha256', $data['token']), 'abilities' => ['*']]
            );
        }
    }
}
