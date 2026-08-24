<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Illuminate\View\View;

class SharedCalendarController extends Controller
{
    /**
     * ゲスト用カレンダー公開・予約画面
     */
    public function show(string $token): View
    {
        $user = User::where('share_token', $token)
            ->where('is_calendar_shared', true)
            ->firstOrFail();

        return view('shared_calendar', [
            'ownerName' => $user->name,
            'shareToken' => $user->share_token,
        ]);
    }

    /**
     * ゲスト用カレンダーイベント取得 (タイトル・メモを完全マスクし「予定あり」のみ返却)
     */
    public function events(Request $request, string $token): JsonResponse
    {
        $user = User::where('share_token', $token)
            ->where('is_calendar_shared', true)
            ->firstOrFail();

        $query = $user->tasks()
            ->whereDate('end_at', '>=', now()->toDateString());

        if ($request->filled('start') && $request->filled('end')) {
            $start = $request->query('start');
            $end = $request->query('end');

            $query->where(function ($q) use ($start, $end) {
                $q->whereBetween('start_at', [$start, $end])
                    ->orWhereBetween('end_at', [$start, $end])
                    ->orWhere(function ($sub) use ($start, $end) {
                        $sub->where('start_at', '<=', $start)
                            ->where('end_at', '>=', $end);
                    });
            });
        }

        $tasks = $query->orderBy('start_at')->get();

        // 持ち主のタイトル・メモを一切露出せず、安全にマスク
        $events = [];

        foreach ($tasks as $task) {
            if ($task->is_all_day) {
                // ① 上部の終日スロット用イベント
                $events[] = [
                    'id' => 'allday_'.$task->id,
                    'title' => '予定あり (終日・予約不可)',
                    'start' => $task->start_at->format('Y-m-d'),
                    'end' => $task->end_at->copy()->addDay()->format('Y-m-d'),
                    'allDay' => true,
                    'backgroundColor' => '#475569',
                    'borderColor' => '#334155',
                    'display' => 'block',
                    'extendedProps' => [
                        'is_busy' => true,
                        'is_all_day' => true,
                    ],
                ];

                // ② その日の時間軸全体 (00:00〜24:00) を覆うブロックイベント
                $events[] = [
                    'id' => 'block_'.$task->id,
                    'title' => '終日 予約不可',
                    'start' => $task->start_at->format('Y-m-d').'T00:00:00',
                    'end' => $task->end_at->format('Y-m-d').'T24:00:00',
                    'allDay' => false,
                    'backgroundColor' => '#475569',
                    'borderColor' => '#334155',
                    'display' => 'block',
                    'extendedProps' => [
                        'is_busy' => true,
                        'is_all_day' => true,
                    ],
                ];
            } else {
                // 通常の時間帯予定
                $events[] = [
                    'id' => $task->id,
                    'title' => '予定あり (予約不可)',
                    'start' => $task->start_at->format('Y-m-d\TH:i:s'),
                    'end' => $task->end_at->format('Y-m-d\TH:i:s'),
                    'allDay' => false,
                    'backgroundColor' => '#94a3b8',
                    'borderColor' => '#64748b',
                    'display' => 'block',
                    'extendedProps' => [
                        'is_busy' => true,
                        'is_all_day' => false,
                    ],
                ];
            }
        }

        return response()->json($events);
    }

    /**
     * ゲストによるスケジュール追加 (予約登録)
     */
    public function store(Request $request, string $token): JsonResponse
    {
        $user = User::where('share_token', $token)
            ->where('is_calendar_shared', true)
            ->firstOrFail();

        $validated = $request->validate([
            'guest_name' => ['required', 'string', 'max:100'],
            'guest_email' => ['required', 'email', 'max:255'],
            'start_at' => ['required', 'date'],
            'end_at' => ['required', 'date', 'after:start_at'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ], [
            'guest_name.required' => 'お名前を入力してください。',
            'guest_email.required' => 'メールアドレスを入力してください。',
            'guest_email.email' => '正しいメールアドレスの形式で入力してください。',
            'start_at.required' => '開始日時を指定してください。',
            'end_at.required' => '終了日時を指定してください。',
            'end_at.after' => '終了日時は開始日時より後の時間を指定してください。',
        ]);

        $startAt = $validated['start_at'];
        $endAt = $validated['end_at'];
        $startDate = Carbon::parse($startAt)->toDateString();
        $endDate = Carbon::parse($endAt)->toDateString();

        // ① 終日予定との重複チェック（その日に終日予定があれば予約不可）
        $hasAllDayConflict = $user->tasks()
            ->where('is_all_day', true)
            ->where(function ($q) use ($startDate, $endDate) {
                $q->whereDate('start_at', '<=', $endDate)
                    ->whereDate('end_at', '>=', $startDate);
            })
            ->exists();

        if ($hasAllDayConflict) {
            return response()->json([
                'message' => '指定された日には終日の予定が入っているため予約できません。別の日時をお選びください。',
            ], 422);
        }

        // ② 通常の時間帯重複チェック
        $isConflicting = $user->tasks()
            ->where('is_all_day', false)
            ->where(function ($q) use ($startAt, $endAt) {
                $q->where('start_at', '<', $endAt)
                    ->where('end_at', '>', $startAt);
            })
            ->exists();

        if ($isConflicting) {
            return response()->json([
                'message' => '指定された時間帯にはすでに他の予定が入っているため予約できません。別の時間をお選びください。',
            ], 422);
        }

        // 持ち主のタスクとして安全に作成
        $task = $user->tasks()->create([
            'title' => "[予約] {$validated['guest_name']} 様",
            'description' => "【外部カレンダー予約】\n予約者名: {$validated['guest_name']}\n連絡先: {$validated['guest_email']}\nご用件・メモ:\n".($validated['notes'] ?? 'なし'),
            'start_at' => $startAt,
            'end_at' => $endAt,
            'is_all_day' => false,
            'color' => '#059669', // 予約はエメラルドグリーン
            'is_completed' => false,
        ]);

        return response()->json([
            'message' => 'スケジュールの予約が完了しました！',
            'task_id' => $task->id,
        ], 201);
    }

    /**
     * ログインユーザーによる共有設定の変更 (ON/OFF / トークン再生成)
     */
    public function updateSettings(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($request->has('regenerate_token') && $request->boolean('regenerate_token')) {
            $user->share_token = Str::random(32);
        }

        if ($request->has('is_calendar_shared')) {
            $user->is_calendar_shared = $request->boolean('is_calendar_shared');
        }

        $user->save();

        return response()->json([
            'message' => 'カレンダー共有設定を更新しました。',
            'share_token' => $user->share_token,
            'is_calendar_shared' => $user->is_calendar_shared,
            'share_url' => route('calendar.share', ['token' => $user->share_token]),
        ]);
    }
}
