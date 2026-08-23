@extends('layouts.app')

@section('content')
<div class="text-center py-16 px-4">
    <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-indigo-50 text-indigo-700 text-xs font-semibold uppercase tracking-wider mb-6">
        <span class="w-2 h-2 rounded-full bg-indigo-500 animate-pulse"></span>
        Laravel 12 &amp; Nginx on Port 7777
    </div>

    <h1 class="text-4xl sm:text-5xl font-extrabold text-gray-900 tracking-tight mb-4">
        Web サービス認証システム
    </h1>

    <p class="text-lg text-gray-600 max-w-2xl mx-auto mb-10">
        メールアドレスで安全に仮登録を行い、認証メールのリンクからアカウント名とパスワードを設定して本登録を完了します。<br>
        ログインはメールアドレス・アカウント名の両方に対応しています。
    </p>

    <div class="flex flex-col sm:flex-row justify-center items-center gap-4">
        @auth
            <a href="{{ route('dashboard') }}" class="w-full sm:w-auto bg-indigo-600 hover:bg-indigo-700 text-white font-semibold px-8 py-3.5 rounded-xl shadow-md transition duration-150">
                ダッシュボードへ移動
            </a>
        @else
            <a href="{{ route('register') }}" class="w-full sm:w-auto bg-indigo-600 hover:bg-indigo-700 text-white font-semibold px-8 py-3.5 rounded-xl shadow-md transition duration-150">
                メールアドレスで仮登録
            </a>
            <a href="{{ route('login') }}" class="w-full sm:w-auto bg-white hover:bg-gray-50 text-gray-800 font-semibold px-8 py-3.5 rounded-xl border border-gray-300 shadow-sm transition duration-150">
                ログイン
            </a>
        @endauth
    </div>

    <!-- 機能ハイライト -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 max-w-4xl mx-auto mt-20 text-left">
        <div class="bg-white p-6 rounded-xl border border-gray-200 shadow-sm">
            <div class="w-10 h-10 rounded-lg bg-indigo-50 flex items-center justify-center text-indigo-600 font-bold mb-3">
                1
            </div>
            <h3 class="font-bold text-gray-900 mb-1">公式メール認証</h3>
            <p class="text-sm text-gray-500">暗号化署名付きURL（signed URL）を用いて安全にメールアドレスの有効性を検証。</p>
        </div>

        <div class="bg-white p-6 rounded-xl border border-gray-200 shadow-sm">
            <div class="w-10 h-10 rounded-lg bg-indigo-50 flex items-center justify-center text-indigo-600 font-bold mb-3">
                2
            </div>
            <h3 class="font-bold text-gray-900 mb-1">マルチログイン対応</h3>
            <p class="text-sm text-gray-500">メールアドレスでもアカウント名（ASCII英数字）でもパスワードと組み合わせて安全にログイン。</p>
        </div>

        <div class="bg-white p-6 rounded-xl border border-gray-200 shadow-sm">
            <div class="w-10 h-10 rounded-lg bg-indigo-50 flex items-center justify-center text-indigo-600 font-bold mb-3">
                3
            </div>
            <h3 class="font-bold text-gray-900 mb-1">安全なセッション管理</h3>
            <p class="text-sm text-gray-500">ログイン・ログアウト時のセッション再生成（Session Fixation防止）とCSRF保護を徹底。</p>
        </div>
    </div>
</div>
@endsection
