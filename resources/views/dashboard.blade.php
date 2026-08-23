@extends('layouts.app')

@section('content')
<div class="space-y-6">
    <!-- ヘッダーエリア -->
    <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-200 flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 flex items-center gap-2">
                <svg class="w-7 h-7 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
                <span>タスク &amp; スケジュール カレンダー</span>
            </h1>
            <p class="text-sm text-gray-500 mt-1">
                Googleカレンダーのように「日・週・月」で表示を切り替え、時間範囲やメモを設定してスケジュールを管理できます。
            </p>
        </div>
        <div class="flex items-center gap-3">
            <button type="button" onclick="openCreateTaskModal()"
                class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold px-4 py-2.5 rounded-xl shadow-sm transition text-sm">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                <span>スケジュール追加</span>
            </button>
        </div>
    </div>

    <!-- カレンダー表示エリア -->
    <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-200">
        <div id="calendar"></div>
    </div>
</div>

<!-- スケジュール追加・編集モーダル -->
<div id="taskModal" class="fixed inset-0 bg-black/50 z-50 hidden items-center justify-center p-4">
    <div id="taskModalCard" class="bg-white rounded-2xl max-w-lg w-full p-6 shadow-xl border-2 border-indigo-400 transform transition-all">
        <div class="flex items-center justify-between pb-4 mb-4 border-b border-gray-100">
            <div class="flex items-center gap-2.5">
                <h3 id="modalTitle" class="text-lg font-bold text-gray-900">スケジュール・タスク追加</h3>
                <span id="modalBadge" class="text-xs px-2.5 py-0.5 rounded-full font-semibold bg-indigo-50 text-indigo-700">新規追加</span>
            </div>
            <button type="button" onclick="closeTaskModal()" class="text-gray-400 hover:text-gray-600 transition">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <form id="taskForm" class="space-y-4">
            <input type="hidden" id="taskId" value="">

            <!-- タイトル -->
            <div>
                <label for="taskTitle" class="block text-sm font-medium text-gray-700">タイトル (タスク名) <span class="text-red-500">*</span></label>
                <input type="text" id="taskTitle" required placeholder="例: プロジェクト定例ミーティング"
                    class="mt-1 block w-full px-3.5 py-2.5 bg-gray-50 border border-gray-300 rounded-lg text-gray-900 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 text-sm">
            </div>

            <!-- 時間範囲設定 -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="taskStart" class="block text-sm font-medium text-gray-700">開始日時 <span class="text-red-500">*</span></label>
                    <input type="datetime-local" id="taskStart" required
                        class="mt-1 block w-full px-3 py-2 bg-gray-50 border border-gray-300 rounded-lg text-gray-900 focus:ring-2 focus:ring-indigo-500 text-sm">
                </div>
                <div>
                    <label for="taskEnd" class="block text-sm font-medium text-gray-700">終了日時 <span class="text-red-500">*</span></label>
                    <input type="datetime-local" id="taskEnd" required
                        class="mt-1 block w-full px-3 py-2 bg-gray-50 border border-gray-300 rounded-lg text-gray-900 focus:ring-2 focus:ring-indigo-500 text-sm">
                </div>
            </div>

            <!-- 終日フラグ -->
            <div class="flex items-center gap-2">
                <input type="checkbox" id="taskIsAllDay" class="w-4 h-4 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500">
                <label for="taskIsAllDay" class="text-sm text-gray-700">終日の予定にする</label>
            </div>

            <!-- メモ（詳細） -->
            <div>
                <label for="taskDescription" class="block text-sm font-medium text-gray-700">メモ・詳細説明</label>
                <textarea id="taskDescription" rows="3" placeholder="会議の議題やアジェンダ、タスクの備考など"
                    class="mt-1 block w-full px-3.5 py-2.5 bg-gray-50 border border-gray-300 rounded-lg text-gray-900 focus:ring-2 focus:ring-indigo-500 text-sm"></textarea>
            </div>

            <!-- カラー選択 (ビジュアルカラーパレット) -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">ラベルカラー</label>
                <input type="hidden" id="taskColor" value="#4f46e5">
                <div class="flex items-center gap-2.5 flex-wrap" id="colorPalette">
                    <button type="button" data-color="#4f46e5" title="インディゴ (通常)"
                        class="color-btn w-8 h-8 rounded-full bg-[#4f46e5] flex items-center justify-center text-white ring-2 ring-offset-2 ring-indigo-600 transition-transform transform hover:scale-110">
                        <svg class="w-4 h-4 checkmark" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" /></svg>
                    </button>
                    <button type="button" data-color="#2563eb" title="ブルー (仕事/MTG)"
                        class="color-btn w-8 h-8 rounded-full bg-[#2563eb] flex items-center justify-center text-white ring-offset-2 hover:scale-110 transition-transform">
                        <svg class="w-4 h-4 checkmark hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" /></svg>
                    </button>
                    <button type="button" data-color="#059669" title="エメラルド (完了/私用)"
                        class="color-btn w-8 h-8 rounded-full bg-[#059669] flex items-center justify-center text-white ring-offset-2 hover:scale-110 transition-transform">
                        <svg class="w-4 h-4 checkmark hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" /></svg>
                    </button>
                    <button type="button" data-color="#d97706" title="アンバー (重要)"
                        class="color-btn w-8 h-8 rounded-full bg-[#d97706] flex items-center justify-center text-white ring-offset-2 hover:scale-110 transition-transform">
                        <svg class="w-4 h-4 checkmark hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" /></svg>
                    </button>
                    <button type="button" data-color="#dc2626" title="レッド (緊急/締切)"
                        class="color-btn w-8 h-8 rounded-full bg-[#dc2626] flex items-center justify-center text-white ring-offset-2 hover:scale-110 transition-transform">
                        <svg class="w-4 h-4 checkmark hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" /></svg>
                    </button>
                    <button type="button" data-color="#7c3aed" title="パープル (イベント)"
                        class="color-btn w-8 h-8 rounded-full bg-[#7c3aed] flex items-center justify-center text-white ring-offset-2 hover:scale-110 transition-transform">
                        <svg class="w-4 h-4 checkmark hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" /></svg>
                    </button>
                    <button type="button" data-color="#0891b2" title="シアン (外出/移動)"
                        class="color-btn w-8 h-8 rounded-full bg-[#0891b2] flex items-center justify-center text-white ring-offset-2 hover:scale-110 transition-transform">
                        <svg class="w-4 h-4 checkmark hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" /></svg>
                    </button>
                    <button type="button" data-color="#4b5563" title="チャコール (リマインダー)"
                        class="color-btn w-8 h-8 rounded-full bg-[#4b5563] flex items-center justify-center text-white ring-offset-2 hover:scale-110 transition-transform">
                        <svg class="w-4 h-4 checkmark hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" /></svg>
                    </button>
                </div>
            </div>

            <!-- 完了チェック (編集時のみ表示) -->
            <div id="completedContainer" class="hidden items-center gap-2 pt-1 border-t border-gray-100">
                <input type="checkbox" id="taskIsCompleted" class="w-4 h-4 text-emerald-600 border-gray-300 rounded focus:ring-emerald-500">
                <label for="taskIsCompleted" class="text-sm font-medium text-gray-700">このタスクを「完了」にする</label>
            </div>

            <!-- ボタンエリア -->
            <div class="flex items-center justify-between pt-4 border-t border-gray-100">
                <button type="button" id="deleteTaskBtn"
                    class="hidden px-4 py-2 bg-red-50 hover:bg-red-100 text-red-600 font-medium rounded-lg text-sm transition">
                    削除
                </button>
                <div class="flex items-center gap-2 ml-auto">
                    <button type="button" onclick="closeTaskModal()"
                        class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium rounded-lg text-sm transition">
                        キャンセル
                    </button>
                    <button type="submit"
                        class="px-5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold rounded-lg text-sm shadow transition">
                        保存する
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection
