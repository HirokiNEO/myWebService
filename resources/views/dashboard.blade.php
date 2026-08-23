@extends('layouts.app')

@section('content')
<div class="max-w-4xl mx-auto space-y-6">
    <!-- ユーザーウェルカムカード -->
    <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-200 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
            <span class="text-xs font-semibold uppercase tracking-wider text-green-700 bg-green-50 border border-green-200 px-2.5 py-1 rounded-full inline-flex items-center gap-1">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
                本登録完了（認証済み）
            </span>
            <h1 class="text-2xl font-bold text-gray-900 mt-2">ようこそ、{{ $user->name }} さん</h1>
            <p class="text-sm text-gray-500">{{ $user->email }}</p>
        </div>
        <form action="{{ route('logout') }}" method="POST">
            @csrf
            <button type="submit" class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-4 py-2 rounded-lg font-medium text-sm transition flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                </svg>
                ログアウト
            </button>
        </form>
    </div>

    <!-- アカウント情報カード -->
    <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-200">
        <h2 class="text-lg font-bold text-gray-900 mb-4 flex items-center gap-2">
            <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
            </svg>
            アカウント情報
        </h2>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div class="bg-gray-50 p-4 rounded-xl border border-gray-200">
                <span class="block text-xs text-gray-500 font-medium">アカウント名 (ログインID)</span>
                <span class="text-base font-semibold text-gray-900 mt-1 block">{{ $user->name }}</span>
            </div>

            <div class="bg-gray-50 p-4 rounded-xl border border-gray-200">
                <span class="block text-xs text-gray-500 font-medium">メールアドレス</span>
                <span class="text-base font-semibold text-gray-900 mt-1 block">{{ $user->email }}</span>
            </div>

            <div class="bg-gray-50 p-4 rounded-xl border border-gray-200">
                <span class="block text-xs text-gray-500 font-medium">メールアドレス認証日時</span>
                <span class="text-sm font-semibold text-green-700 mt-1 block">
                    {{ $user->email_verified_at ? $user->email_verified_at->format('Y-m-d H:i:s') : '未認証' }}
                </span>
            </div>

            <div class="bg-gray-50 p-4 rounded-xl border border-gray-200">
                <span class="block text-xs text-gray-500 font-medium">登録日時</span>
                <span class="text-sm font-semibold text-gray-700 mt-1 block">
                    {{ $user->created_at->format('Y-m-d H:i:s') }}
                </span>
            </div>
        </div>
    </div>
</div>
@endsection
