@extends('layouts.app')

@section('content')
<div class="max-w-md mx-auto bg-white p-8 rounded-2xl shadow-sm border border-gray-200">
    <div class="text-center mb-6">
        <h1 class="text-2xl font-bold text-gray-900">ログイン</h1>
        <p class="text-sm text-gray-500 mt-1">
            メールアドレス または アカウント名 でログインできます。
        </p>
    </div>

    <form method="POST" action="{{ route('login') }}" class="space-y-4">
        @csrf

        <!-- メールアドレス または アカウント名 -->
        <div>
            <label for="login" class="block text-sm font-medium text-gray-700">メールアドレス または アカウント名</label>
            <input type="text" id="login" name="login" value="{{ old('login') }}" required autofocus
                class="mt-1 block w-full px-4 py-2.5 bg-gray-50 border border-gray-300 rounded-lg text-gray-900 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition"
                placeholder="user@example.com または taro_yamada">
        </div>

        <!-- パスワード -->
        <div>
            <label for="password" class="block text-sm font-medium text-gray-700">パスワード</label>
            <input type="password" id="password" name="password" required
                class="mt-1 block w-full px-4 py-2.5 bg-gray-50 border border-gray-300 rounded-lg text-gray-900 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition"
                placeholder="••••••••">
        </div>

        <!-- ログイン保持 -->
        <div class="flex items-center justify-between">
            <label class="flex items-center">
                <input type="checkbox" name="remember" class="w-4 h-4 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500">
                <span class="ml-2 text-sm text-gray-600">ログイン状態を保持する</span>
            </label>
        </div>

        <div class="pt-2">
            <button type="submit"
                class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-2.5 px-4 rounded-lg shadow transition duration-150">
                ログイン
            </button>
        </div>
    </form>

    <div class="text-center mt-6 pt-4 border-t border-gray-100 text-sm text-gray-600">
        アカウントをお持ちでないですか？
        <a href="{{ route('register') }}" class="text-indigo-600 font-semibold hover:underline">新規登録はこちら</a>
    </div>
</div>
@endsection
