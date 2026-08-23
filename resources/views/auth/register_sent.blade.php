@extends('layouts.app')

@section('content')
<div class="max-w-md mx-auto bg-white p-8 rounded-2xl shadow-sm border border-gray-200 text-center">
    <div class="w-16 h-16 bg-indigo-50 text-indigo-600 rounded-full flex items-center justify-center mx-auto mb-4">
        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
        </svg>
    </div>

    <h1 class="text-2xl font-bold text-gray-900 mb-2">仮登録が完了しました</h1>
    
    <p class="text-sm text-gray-600 mb-6 leading-relaxed">
        @if (session('registered_email'))
            <strong>{{ session('registered_email') }}</strong> 宛に認証メールを送信しました。<br>
        @else
            ご入力いただいたメールアドレス宛に認証メールを送信しました。<br>
        @endif
        メール内のリンクをクリックして本登録を完了してください。
    </p>

    <div class="space-y-3 pt-2">
        <a href="{{ route('login') }}" class="block w-full bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-2.5 px-4 rounded-xl shadow transition duration-150 text-sm">
            ログイン画面へ
        </a>
        <a href="{{ url('/') }}" class="block text-sm text-gray-500 hover:text-gray-700">
            トップページに戻る
        </a>
    </div>
</div>
@endsection
