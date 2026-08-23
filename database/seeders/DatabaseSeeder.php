<?php

namespace Database\Seeders;

use App\Models\Task;
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

        // テスト用初期スケジュール・タスク登録
        Task::updateOrCreate(
            ['user_id' => $user1->id, 'title' => 'プロジェクト定例ミーティング'],
            [
                'description' => '進捗報告および今後のスケジュール確認',
                'start_at' => now()->setTime(10, 0, 0),
                'end_at' => now()->setTime(11, 30, 0),
                'is_all_day' => false,
                'color' => '#4f46e5',
                'is_completed' => false,
            ]
        );

        Task::updateOrCreate(
            ['user_id' => $user1->id, 'title' => '新機能デザインレビュー'],
            [
                'description' => 'カレンダーUIの使いやすさ確認',
                'start_at' => now()->addDays(1)->setTime(14, 0, 0),
                'end_at' => now()->addDays(1)->setTime(15, 0, 0),
                'color' => '#2563eb',
                'is_completed' => false,
            ]
        );

        Task::updateOrCreate(
            ['user_id' => $user1->id, 'title' => '社内ハッカソン'],
            [
                'description' => '終日イベント',
                'start_at' => now()->addDays(3)->startOfDay(),
                'end_at' => now()->addDays(3)->endOfDay(),
                'is_all_day' => true,
                'color' => '#7c3aed',
                'is_completed' => false,
            ]
        );

        $this->command->info('----------------------------------------');
        $this->command->info(' [テスト用アカウント & スケジュール作成完了]');
        $this->command->info(' ① アカウント名: testuser / メール: test@example.com / パスワード: Password123');
        $this->command->info(' ② アカウント名: taro_yamada / メール: taro@example.com / パスワード: Password123');
        $this->command->info('----------------------------------------');
    }
}
