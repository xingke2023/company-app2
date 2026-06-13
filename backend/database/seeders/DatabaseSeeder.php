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
        // Create a demo user
        $demoUser = User::factory()->create([
            'name' => 'Demo User',
            'email' => 'demo@example.com',
        ]);

        // Create 5 more users
        $users = User::factory(5)->create();

        // Create posts for demo user
        \App\Models\Post::factory(5)->create([
            'user_id' => $demoUser->id,
        ]);

        // Create posts for other users
        foreach ($users as $user) {
            \App\Models\Post::factory(rand(2, 5))->create([
                'user_id' => $user->id,
            ]);
        }

        // 小程序「进销存及CRM」行业的菜单与标题（供小程序公开拉取）
        $this->call(QuickActionSeeder::class);
        $this->call(AppSettingSeeder::class);
    }
}
