<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // テスト用ユーザー1 (メインアカウント)
        $user1 = User::updateOrCreate(
            ['email' => 'test@example.com'],
            [
                'name' => 'testuser',
                'password' => Hash::make('Password123'),
                'email_verified_at' => now(),
            ]
        );

        // テスト用ユーザー2 (サブアカウント)
        $user2 = User::updateOrCreate(
            ['email' => 'taro@example.com'],
            [
                'name' => 'taro_yamada',
                'password' => Hash::make('Password123'),
                'email_verified_at' => now(),
            ]
        );

        $this->command->info('----------------------------------------');
        $this->command->info(' [テスト用アカウント作成完了]');
        $this->command->info(' ① アカウント名: testuser / メール: test@example.com / パスワード: Password123');
        $this->command->info(' ② アカウント名: taro_yamada / メール: taro@example.com / パスワード: Password123');
        $this->command->info('----------------------------------------');
    }
}
