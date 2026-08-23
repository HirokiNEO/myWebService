<?php

namespace Tests\Feature;

use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskCalendarTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 未認証ユーザーはタスク一覧やAPIにアクセスできないテスト
     */
    public function test_guest_cannot_access_tasks(): void
    {
        $this->getJson('/tasks/events')->assertStatus(401);
        $this->postJson('/tasks', ['title' => 'Test'])->assertStatus(401);
    }

    /**
     * ログインユーザーはカレンダー用イベント一覧を取得できるテスト（期間フィルタ含む）
     */
    public function test_user_can_get_calendar_events_within_range(): void
    {
        $user = User::factory()->create();

        // 範囲内タスク (2026-08-25)
        $inRangeTask = Task::factory()->create([
            'user_id' => $user->id,
            'title' => '定例ミーティング',
            'description' => 'プロジェクトの進捗確認',
            'start_at' => '2026-08-25 10:00:00',
            'end_at' => '2026-08-25 11:00:00',
            'color' => '#4f46e5',
            'is_all_day' => false,
            'is_completed' => false,
        ]);

        // 範囲外タスク (2026-09-10)
        $outRangeTask = Task::factory()->create([
            'user_id' => $user->id,
            'title' => '来月のイベント',
            'start_at' => '2026-09-10 10:00:00',
            'end_at' => '2026-09-10 11:00:00',
        ]);

        $response = $this->actingAs($user)->getJson('/tasks/events?start=2026-08-01T00:00:00&end=2026-08-31T23:59:59');

        $response->assertStatus(200)
            ->assertJsonCount(1)
            ->assertJsonFragment([
                'id' => $inRangeTask->id,
                'title' => '定例ミーティング',
                'backgroundColor' => '#4f46e5',
            ]);
    }

    /**
     * ユーザーは新規タスク・スケジュールを作成できるテスト
     */
    public function test_user_can_create_task_with_time_range_and_notes(): void
    {
        $user = User::factory()->create();

        $payload = [
            'title' => '新規プロジェクト企画',
            'description' => '詳細メモ: 要件定義とスケジュール策定',
            'start_at' => '2026-08-24 14:00:00',
            'end_at' => '2026-08-24 15:30:00',
            'is_all_day' => false,
            'color' => '#2563eb',
        ];

        $response = $this->actingAs($user)->postJson('/tasks', $payload);

        $response->assertStatus(201)
            ->assertJsonPath('task.title', '新規プロジェクト企画');

        $this->assertDatabaseHas('tasks', [
            'user_id' => $user->id,
            'title' => '新規プロジェクト企画',
            'description' => '詳細メモ: 要件定義とスケジュール策定',
            'color' => '#2563eb',
        ]);
    }

    /**
     * 期間をまたぐタスク（開始が範囲前・終了が範囲後など）も正確に取得できるテスト
     */
    public function test_calendar_events_spanning_across_range(): void
    {
        $user = User::factory()->create();

        // 8月中旬から9月中旬にまたがる長期タスク
        $spanningTask = Task::factory()->create([
            'user_id' => $user->id,
            'title' => '長期プロジェクト期間',
            'start_at' => '2026-08-15 00:00:00',
            'end_at' => '2026-09-15 23:59:59',
            'is_all_day' => true,
        ]);

        // 9月1日〜9月30日の取得でヒットすること
        $response = $this->actingAs($user)->getJson('/tasks/events?start=2026-09-01T00:00:00&end=2026-09-30T23:59:59');

        $response->assertStatus(200)
            ->assertJsonCount(1)
            ->assertJsonFragment(['id' => $spanningTask->id]);
    }

    /**
     * 終日（All-Day）イベントの作成と取得ができるテスト
     */
    public function test_user_can_create_all_day_event(): void
    {
        $user = User::factory()->create();

        $payload = [
            'title' => '社内ハッカソン（終日）',
            'start_at' => '2026-08-28 00:00:00',
            'end_at' => '2026-08-28 23:59:59',
            'is_all_day' => true,
            'color' => '#7c3aed',
        ];

        $response = $this->actingAs($user)->postJson('/tasks', $payload);
        $response->assertStatus(201);

        $this->assertDatabaseHas('tasks', [
            'user_id' => $user->id,
            'title' => '社内ハッカソン（終日）',
            'is_all_day' => true,
        ]);

        $eventsRes = $this->actingAs($user)->getJson('/tasks/events');
        $eventsRes->assertStatus(200)
            ->assertJsonFragment([
                'title' => '社内ハッカソン（終日）',
                'allDay' => true,
            ]);
    }

    /**
     * 終了日時が開始日時より前の場合はバリデーションエラーになるテスト
     */
    public function test_task_end_date_must_be_after_or_equal_to_start_date(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/tasks', [
            'title' => '不正な時間設定',
            'start_at' => '2026-08-24 15:00:00',
            'end_at' => '2026-08-24 14:00:00', // 開始より前
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('end_at');
    }

    /**
     * 開始日時と終了日時が同じ（同日時）タスクは作成可能であるテスト
     */
    public function test_same_start_and_end_time_task_is_allowed(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/tasks', [
            'title' => 'ピンポイント予定',
            'start_at' => '2026-08-24 15:00:00',
            'end_at' => '2026-08-24 15:00:00',
        ]);

        $response->assertStatus(201);
    }

    /**
     * ユーザーは自分のタスクを更新できるテスト (時間変更・色・メモ・完了切り替え)
     */
    public function test_user_can_update_task(): void
    {
        $user = User::factory()->create();
        $task = Task::factory()->create([
            'user_id' => $user->id,
            'title' => '更新前タスク',
            'color' => '#4f46e5',
            'is_completed' => false,
        ]);

        $response = $this->actingAs($user)->putJson("/tasks/{$task->id}", [
            'title' => '更新後タスク',
            'description' => '更新されたメモ内容',
            'color' => '#dc2626',
            'is_completed' => true,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('task.title', '更新後タスク')
            ->assertJsonPath('task.color', '#dc2626')
            ->assertJsonPath('task.is_completed', true);

        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'title' => '更新後タスク',
            'description' => '更新されたメモ内容',
            'color' => '#dc2626',
            'is_completed' => true,
        ]);
    }

    /**
     * 完了済みタスクは背景色がグレーでカレンダーに返却されるテスト
     */
    public function test_completed_task_shows_gray_background_in_events(): void
    {
        $user = User::factory()->create();

        $task = Task::factory()->create([
            'user_id' => $user->id,
            'title' => '完了したタスク',
            'color' => '#dc2626',
            'is_completed' => true,
            'start_at' => '2026-08-25 10:00:00',
            'end_at' => '2026-08-25 11:00:00',
        ]);

        $response = $this->actingAs($user)->getJson('/tasks/events');

        $response->assertStatus(200)
            ->assertJsonFragment([
                'id' => $task->id,
                'backgroundColor' => '#9ca3af',
            ]);
    }

    /**
     * ユーザーは自分のタスクを削除できるテスト
     */
    public function test_user_can_delete_task(): void
    {
        $user = User::factory()->create();
        $task = Task::factory()->create([
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($user)->deleteJson("/tasks/{$task->id}");

        $response->assertStatus(200);
        $this->assertDatabaseMissing('tasks', ['id' => $task->id]);
    }

    /**
     * 他人のタスクは更新・削除できないテスト (403 Forbidden)
     */
    public function test_user_cannot_update_or_delete_other_users_task(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();

        $task = Task::factory()->create([
            'user_id' => $owner->id,
            'title' => '他人のタスク',
        ]);

        // 他人が更新を試みる (403)
        $this->actingAs($otherUser)
            ->putJson("/tasks/{$task->id}", ['title' => '勝手に変更'])
            ->assertStatus(403);

        // 他人が削除を試みる (403)
        $this->actingAs($otherUser)
            ->deleteJson("/tasks/{$task->id}")
            ->assertStatus(403);

        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'title' => '他人のタスク',
        ]);
    }

    /**
     * 他人のタスクはイベント一覧に漏洩しないテスト
     */
    public function test_other_users_tasks_are_isolated(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();

        Task::factory()->create([
            'user_id' => $userA->id,
            'title' => 'ユーザーAのプライベートタスク',
        ]);

        $response = $this->actingAs($userB)->getJson('/tasks/events');

        $response->assertStatus(200)
            ->assertJsonCount(0);
    }
}
