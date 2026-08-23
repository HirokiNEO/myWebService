<?php

namespace App\Http\Controllers;

use App\Models\Task;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TaskController extends Controller
{
    /**
     * カレンダー用イベント一覧の取得 (FullCalendar互換JSON)
     */
    public function events(Request $request): JsonResponse
    {
        $query = $request->user()->tasks();

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

        $events = $tasks->map(function (Task $task) {
            return [
                'id' => $task->id,
                'title' => $task->title,
                'start' => $task->start_at->format('Y-m-d\TH:i:s'),
                'end' => $task->end_at->format('Y-m-d\TH:i:s'),
                'allDay' => $task->is_all_day,
                'backgroundColor' => $task->is_completed ? '#9ca3af' : $task->color,
                'borderColor' => $task->is_completed ? '#9ca3af' : $task->color,
                'extendedProps' => [
                    'description' => $task->description ?? '',
                    'color' => $task->color,
                    'is_completed' => $task->is_completed,
                    'start_formatted' => $task->start_at->format('Y/m/d H:i'),
                    'end_formatted' => $task->end_at->format('Y/m/d H:i'),
                ],
            ];
        });

        return response()->json($events);
    }

    /**
     * 新規タスク・スケジュールの作成
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'start_at' => ['required', 'date'],
            'end_at' => ['required', 'date', 'after_or_equal:start_at'],
            'is_all_day' => ['boolean'],
            'color' => ['nullable', 'string', 'max:20'],
        ]);

        $task = $request->user()->tasks()->create([
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'start_at' => $validated['start_at'],
            'end_at' => $validated['end_at'],
            'is_all_day' => $request->boolean('is_all_day'),
            'color' => $validated['color'] ?? '#4f46e5',
            'is_completed' => false,
        ]);

        return response()->json([
            'message' => 'タスクを作成しました。',
            'task' => $task,
        ], 201);
    }

    /**
     * タスク・スケジュールの更新
     */
    public function update(Request $request, Task $task): JsonResponse
    {
        if ($task->user_id !== $request->user()->id) {
            abort(403, '権限がありません。');
        }

        $validated = $request->validate([
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'start_at' => ['sometimes', 'required', 'date'],
            'end_at' => ['sometimes', 'required', 'date', 'after_or_equal:start_at'],
            'is_all_day' => ['boolean'],
            'color' => ['nullable', 'string', 'max:20'],
            'is_completed' => ['boolean'],
        ]);

        $task->update($validated);

        return response()->json([
            'message' => 'タスクを更新しました。',
            'task' => $task,
        ]);
    }

    /**
     * タスク・スケジュールの削除
     */
    public function destroy(Request $request, Task $task): JsonResponse
    {
        if ($task->user_id !== $request->user()->id) {
            abort(403, '権限がありません。');
        }

        $task->delete();

        return response()->json([
            'message' => 'タスクを削除しました。',
        ]);
    }
}
