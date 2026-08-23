@extends('layouts.app')

@section('content')
<div class="max-w-md mx-auto bg-white p-8 rounded-2xl shadow-sm border border-gray-200">
    <div class="text-center mb-6">
        <h1 class="text-2xl font-bold text-gray-900">本登録（アカウント設定）</h1>
        <p class="text-sm text-gray-500 mt-1">
            アカウント名とパスワードを設定して、本登録を完了してください。
        </p>
    </div>

    <form method="POST" action="{{ $signedUrl }}" class="space-y-4">
        @csrf

        <!-- メールアドレス (固定表示) -->
        <div>
            <label class="block text-sm font-medium text-gray-700">メールアドレス</label>
            <div class="mt-1 px-4 py-2.5 bg-gray-100 border border-gray-200 rounded-lg text-gray-700 text-sm font-medium">
                {{ $email }}
            </div>
        </div>

        <!-- アカウント名 -->
        <div>
            <label for="name" class="block text-sm font-medium text-gray-700">アカウント名 (ユーザー名)</label>
            <input type="text" id="name" name="name" value="{{ old('name') }}" required autofocus
                class="mt-1 block w-full px-4 py-2.5 bg-gray-50 border border-gray-300 rounded-lg text-gray-900 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition"
                placeholder="taro_yamada">
            <p class="text-xs text-gray-500 mt-1">※半角英数字、ハイフン(-)、アンダースコア(_)のみ</p>
        </div>

        <!-- パスワード -->
        <div>
            <label for="password" class="block text-sm font-medium text-gray-700">パスワード</label>
            <input type="password" id="password" name="password" required
                class="mt-1 block w-full px-4 py-2.5 bg-gray-50 border border-gray-300 rounded-lg text-gray-900 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition"
                placeholder="••••••••">
            <p class="text-xs text-gray-500 mt-1">※8文字以上（英大文字・小文字・数字必須）</p>
        </div>

        <!-- パスワード確認 -->
        <div>
            <label for="password_confirmation" class="block text-sm font-medium text-gray-700">パスワード（確認用）</label>
            <input type="password" id="password_confirmation" name="password_confirmation" required
                class="mt-1 block w-full px-4 py-2.5 bg-gray-50 border border-gray-300 rounded-lg text-gray-900 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition"
                placeholder="••••••••">
        </div>

        <div class="pt-2">
            <button type="submit"
                class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-2.5 px-4 rounded-lg shadow transition duration-150">
                本登録を完了する
            </button>
        </div>
    </form>
</div>
@endsection
