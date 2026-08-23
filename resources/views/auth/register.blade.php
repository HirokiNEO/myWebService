@extends('layouts.app')

@section('content')
<div class="max-w-md mx-auto bg-white p-8 rounded-2xl shadow-sm border border-gray-200">
    <div class="text-center mb-6">
        <h1 class="text-2xl font-bold text-gray-900">アカウント仮登録</h1>
        <p class="text-sm text-gray-500 mt-1">
            メールアドレスを入力してください。<br>本登録用のリンクをお送りします。
        </p>
    </div>

    <form method="POST" action="{{ route('register') }}" class="space-y-4">
        @csrf

        <!-- メールアドレス -->
        <div>
            <label for="email" class="block text-sm font-medium text-gray-700">メールアドレス</label>
            <input type="email" id="email" name="email" value="{{ old('email') }}" required autofocus
                class="mt-1 block w-full px-4 py-2.5 bg-gray-50 border border-gray-300 rounded-lg text-gray-900 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition"
                placeholder="user@example.com">
        </div>

        <div class="pt-2">
            <button type="submit"
                class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-2.5 px-4 rounded-lg shadow transition duration-150">
                認証メールを送信する
            </button>
        </div>
    </form>

    <div class="text-center mt-6 pt-4 border-t border-gray-100 text-sm text-gray-600">
        すでにアカウントをお持ちですか？
        <a href="{{ route('login') }}" class="text-indigo-600 font-semibold hover:underline">ログインはこちら</a>
    </div>
</div>
@endsection
