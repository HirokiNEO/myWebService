@extends('layouts.app')

@section('content')
<div class="max-w-6xl mx-auto space-y-6">
    <!-- ヘッダーエリア -->
    <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-200 flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
        <div>
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-emerald-50 text-emerald-700 text-xs font-semibold uppercase tracking-wider mb-2">
                <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                公開カレンダー・予約受付中
            </div>
            <h1 class="text-2xl font-bold text-gray-900 flex items-center gap-2">
                <span>{{ $ownerName }} さんのスケジュール予約</span>
            </h1>
            <p class="text-sm text-gray-500 mt-1">
                空いている時間枠（または日付）をクリックして、ミーティングやスケジュールの予約を追加できます。
            </p>
        </div>
        <div class="flex items-center gap-2 text-xs text-gray-500 bg-gray-50 px-4 py-2.5 rounded-xl border border-gray-200">
            <span class="w-3.5 h-3.5 rounded bg-slate-400 inline-block"></span>
            <span>「予定あり」の枠は予約できません</span>
        </div>
    </div>

    <!-- カレンダー表示エリア -->
    <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-200">
        <div id="sharedCalendar" data-token="{{ $shareToken }}"></div>
    </div>
</div>

<!-- ゲスト用 予約追加モーダル -->
<div id="guestBookingModal" class="fixed inset-0 bg-black/50 z-50 hidden items-center justify-center p-4">
    <div class="bg-white rounded-2xl max-w-lg w-full p-6 shadow-xl border-2 border-emerald-500 transform transition-all">
        <div class="flex items-center justify-between pb-4 mb-4 border-b border-gray-100">
            <div class="flex items-center gap-2.5">
                <h3 class="text-lg font-bold text-gray-900">スケジュール予約</h3>
                <span class="text-xs px-2.5 py-0.5 rounded-full font-semibold bg-emerald-50 text-emerald-700">新規予約</span>
            </div>
            <button type="button" onclick="closeGuestBookingModal()" class="text-gray-400 hover:text-gray-600 transition">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <form id="guestBookingForm" class="space-y-4">
            <!-- お名前 -->
            <div>
                <label for="guestName" class="block text-sm font-medium text-gray-700">お名前 <span class="text-red-500">*</span></label>
                <input type="text" id="guestName" required placeholder="例: 山田 太郎"
                    class="mt-1 block w-full px-3.5 py-2.5 bg-gray-50 border border-gray-300 rounded-lg text-gray-900 focus:ring-2 focus:ring-emerald-500 text-sm">
            </div>

            <!-- メールアドレス -->
            <div>
                <label for="guestEmail" class="block text-sm font-medium text-gray-700">メールアドレス (連絡先) <span class="text-red-500">*</span></label>
                <input type="email" id="guestEmail" required placeholder="例: yamada@example.com"
                    class="mt-1 block w-full px-3.5 py-2.5 bg-gray-50 border border-gray-300 rounded-lg text-gray-900 focus:ring-2 focus:ring-emerald-500 text-sm">
            </div>

            <!-- 時間範囲設定 -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="bookingStart" class="block text-sm font-medium text-gray-700">開始日時 <span class="text-red-500">*</span></label>
                    <input type="datetime-local" id="bookingStart" required
                        class="mt-1 block w-full px-3 py-2 bg-gray-50 border border-gray-300 rounded-lg text-gray-900 focus:ring-2 focus:ring-emerald-500 text-sm">
                </div>
                <div>
                    <label for="bookingEnd" class="block text-sm font-medium text-gray-700">終了日時 <span class="text-red-500">*</span></label>
                    <input type="datetime-local" id="bookingEnd" required
                        class="mt-1 block w-full px-3 py-2 bg-gray-50 border border-gray-300 rounded-lg text-gray-900 focus:ring-2 focus:ring-emerald-500 text-sm">
                </div>
            </div>

            <!-- ご用件・メモ -->
            <div>
                <label for="bookingNotes" class="block text-sm font-medium text-gray-700">ご用件・メモ (任意)</label>
                <textarea id="bookingNotes" rows="3" placeholder="面談の議題やご希望などをご記入ください"
                    class="mt-1 block w-full px-3.5 py-2.5 bg-gray-50 border border-gray-300 rounded-lg text-gray-900 focus:ring-2 focus:ring-emerald-500 text-sm"></textarea>
            </div>

            <!-- ボタンエリア -->
            <div class="flex items-center justify-end gap-2 pt-4 border-t border-gray-100">
                <button type="button" onclick="closeGuestBookingModal()"
                    class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium rounded-lg text-sm transition">
                    キャンセル
                </button>
                <button type="submit"
                    class="px-5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-semibold rounded-lg text-sm shadow transition">
                    予約を確定する
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
