<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UsersTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = [
            [
                'name' => 'amit',
                'email' => 'amit@email.com',
                'password' => bcrypt('12345678'),
                'view_password' => '12345678',
                'role' => 'admin'
            ],
            [
                'name' => 'kamal',
                'email' => 'kamal@gmail.com',
                'password' => bcrypt('12345678'),
                'view_password' => '12345678',
                'role' => 'chef'
            ],
            [
                'name' => 'vinay',
                'email' => 'vinay@gmail.com',
                'password' => bcrypt('12345678'),
                'view_password' => '12345678',
                'role' => 'user'
            ]
        ];

        foreach ($users as $user) {
            User::create($user);
        }
    }
}
