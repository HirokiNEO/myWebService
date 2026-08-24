<?php

namespace Tests\Feature;

use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SharedCalendarTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 未ログインのゲストが共有URLにアクセスできるテスト
     */
    public function test_guest_can_access_shared_calendar_page(): void
    {
        $user = User::factory()->create([
            'name' => 'suzuki_ichiro',
            'share_token' => 'test-token-12345',
            'is_calendar_shared' => true,
        ]);

        $response = $this->get("/share/{$user->share_token}");

        $response->assertStatus(200)
            ->assertSee('suzuki_ichiro さんのスケジュール予約')
            ->assertSee('test-token-12345');
    }

    /**
     * 【最重要】持ち主の既存スケジュールのタイトルやメモが一切漏洩せずマスクされるテスト
     */
    public function test_shared_events_masks_title_and_description(): void
    {
        $user = User::factory()->create([
            'share_token' => 'secret-mask-token',
            'is_calendar_shared' => true,
        ]);

        // 秘密の社内スケジュール
        Task::factory()->create([
            'user_id' => $user->id,
            'title' => '【極秘】新規大型M&A役員ミーティング',
            'description' => '極秘情報: 買収金額は10億円、対象企業はXXX社',
            'start_at' => '2026-08-25 14:00:00',
            'end_at' => '2026-08-25 15:30:00',
            'color' => '#dc2626',
        ]);

        $response = $this->getJson("/share/{$user->share_token}/events");

        $response->assertStatus(200);

        // タイトルが「予定あり (予約不可)」に置き換わっていること
        $response->assertJsonFragment([
            'title' => '予定あり (予約不可)',
            'backgroundColor' => '#94a3b8',
        ]);

        // 秘密のタイトルやメモが JSON 内に一切含まれていないこと
        $response->assertJsonMissing(['title' => '【極秘】新規大型M&A役員ミーティング']);
        $response->assertJsonMissing(['description' => '極秘情報: 買収金額は10億円、対象企業はXXX社']);
        $response->assertDontSee('【極秘】新規大型M&A役員ミーティング');
        $response->assertDontSee('買収金額は10億円');
    }

    /**
     * 昨日以前（過去）のイベントは共有カレンダーに常に表示されないテスト
     */
    public function test_past_events_are_not_displayed_in_shared_calendar(): void
    {
        $user = User::factory()->create([
            'share_token' => 'past-filter-token',
            'is_calendar_shared' => true,
        ]);

        // 過去のタスク
        Task::factory()->create([
            'user_id' => $user->id,
            'title' => '過去の予定',
            'start_at' => now()->subDays(3)->setTime(10, 0),
            'end_at' => now()->subDays(3)->setTime(11, 0),
        ]);

        // 今日以降のタスク
        Task::factory()->create([
            'user_id' => $user->id,
            'title' => '未来の予定',
            'start_at' => now()->addDays(1)->setTime(10, 0),
            'end_at' => now()->addDays(1)->setTime(11, 0),
        ]);

        $response = $this->getJson("/share/{$user->share_token}/events");
        $response->assertStatus(200)
            ->assertJsonCount(1)
            ->assertJsonFragment(['title' => '予定あり (予約不可)']);
    }

    /**
     * ゲストユーザーが空き時間にスケジュール予約を追加できるテスト
     */
    public function test_guest_can_book_schedule_slot(): void
    {
        $user = User::factory()->create([
            'share_token' => 'booking-token',
            'is_calendar_shared' => true,
        ]);

        $payload = [
            'guest_name' => '田中 太郎',
            'guest_email' => 'tanaka@guest.example.com',
            'start_at' => '2026-08-26 10:00:00',
            'end_at' => '2026-08-26 11:00:00',
            'notes' => '初回サービスの導入相談をお願いします。',
        ];

        $response = $this->postJson("/share/{$user->share_token}/schedule", $payload);

        $response->assertStatus(201)
            ->assertJsonPath('message', 'スケジュールの予約が完了しました！');

        // 持ち主の tasks に予約として登録されていること
        $this->assertDatabaseHas('tasks', [
            'user_id' => $user->id,
            'title' => '[予約] 田中 太郎 様',
            'start_at' => '2026-08-26 10:00:00',
            'end_at' => '2026-08-26 11:00:00',
            'color' => '#059669',
        ]);

        $createdTask = Task::where('user_id', $user->id)->first();
        $this->assertStringContainsString('田中 太郎', $createdTask->description);
        $this->assertStringContainsString('tanaka@guest.example.com', $createdTask->description);
        $this->assertStringContainsString('初回サービスの導入相談をお願いします。', $createdTask->description);
    }

    /**
     * 既存の予定と重複する時間帯への予約は拒否されるテスト (ダブルブッキング防止)
     */
    public function test_guest_cannot_book_conflicting_time_slot(): void
    {
        $user = User::factory()->create([
            'share_token' => 'conflict-test-token',
            'is_calendar_shared' => true,
        ]);

        // 14:00 〜 16:00 に既存の予定あり
        Task::factory()->create([
            'user_id' => $user->id,
            'start_at' => '2026-08-25 14:00:00',
            'end_at' => '2026-08-25 16:00:00',
        ]);

        // 15:00 〜 15:30（重複）で予約を試みる
        $response = $this->postJson("/share/{$user->share_token}/schedule", [
            'guest_name' => '重複 予約者',
            'guest_email' => 'guest@example.com',
            'start_at' => '2026-08-25 15:00:00',
            'end_at' => '2026-08-25 15:30:00',
        ]);

        $response->assertStatus(422)
            ->assertJsonFragment([
                'message' => '指定された時間帯にはすでに他の予定が入っているため予約できません。別の時間をお選びください。',
            ]);
    }

    /**
     * 持ち主に「終日」予定が入っている日の予約は拒絶されるテスト
     */
    public function test_guest_cannot_book_on_day_with_all_day_schedule(): void
    {
        $user = User::factory()->create([
            'share_token' => 'all-day-test-token',
            'is_calendar_shared' => true,
        ]);

        // 8月28日は「終日予定（社内ハッカソン）」
        Task::factory()->create([
            'user_id' => $user->id,
            'title' => '社内ハッカソン（終日）',
            'start_at' => '2026-08-28 00:00:00',
            'end_at' => '2026-08-28 23:59:59',
            'is_all_day' => true,
        ]);

        // 8月28日の 14:00 〜 15:00 に予約を試みる
        $response = $this->postJson("/share/{$user->share_token}/schedule", [
            'guest_name' => '予約希望者',
            'guest_email' => 'guest@example.com',
            'start_at' => '2026-08-28 14:00:00',
            'end_at' => '2026-08-28 15:00:00',
        ]);

        $response->assertStatus(422)
            ->assertJsonFragment([
                'message' => '指定された日には終日の予定が入っているため予約できません。別の日時をお選びください。',
            ]);
    }

    /**
     * 存在しないトークンへのアクセスは 404 Not Found になるテスト
     */
    public function test_invalid_share_token_returns_404(): void
    {
        $this->get('/share/non-existent-token')->assertStatus(404);
        $this->getJson('/share/non-existent-token/events')->assertStatus(404);
        $this->postJson('/share/non-existent-token/schedule', [
            'guest_name' => 'Guest',
            'guest_email' => 'guest@example.com',
            'start_at' => '2026-08-25 10:00:00',
            'end_at' => '2026-08-25 11:00:00',
        ])->assertStatus(404);
    }

    /**
     * カレンダー共有をOFFにしているユーザーのカレンダーは 404 になるテスト
     */
    public function test_disabled_calendar_sharing_returns_404(): void
    {
        $user = User::factory()->create([
            'share_token' => 'disabled-token',
            'is_calendar_shared' => false, // 共有無効
        ]);

        $this->get("/share/{$user->share_token}")->assertStatus(404);
        $this->getJson("/share/{$user->share_token}/events")->assertStatus(404);
    }

    /**
     * ログインユーザーはカレンダー共有設定（ON/OFF・トークン再生成）を更新できるテスト
     */
    public function test_user_can_update_share_settings_and_regenerate_token(): void
    {
        $user = User::factory()->create([
            'share_token' => 'old-token-value',
            'is_calendar_shared' => true,
        ]);

        // トークン再生成と共有OFF
        $response = $this->actingAs($user)->postJson('/calendar/share-settings', [
            'regenerate_token' => true,
            'is_calendar_shared' => false,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('is_calendar_shared', false);

        $user->refresh();
        $this->assertNotEquals('old-token-value', $user->share_token);
        $this->assertFalse($user->is_calendar_shared);
    }
}
