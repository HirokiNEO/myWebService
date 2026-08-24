<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\SharedCalendarController;
use App\Http\Controllers\TaskController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes (メール仮登録 → Signed URL本登録フロー)
|--------------------------------------------------------------------------
*/

// トップページ
Route::get('/', function () {
    return view('welcome');
})->name('home');

// ゲスト用ルート（未ログイン）
Route::middleware('guest')->group(function () {
    // ① メールアドレスのみで仮登録
    Route::get('/register', [RegisteredUserController::class, 'create'])->name('register');
    Route::post('/register', [RegisteredUserController::class, 'store'])->middleware('throttle:10,1');
    Route::get('/register/sent', [RegisteredUserController::class, 'sent'])->name('register.sent');

    // ② メール内署名付きURLによる本登録（アカウント名・パスワード設定）
    Route::get('/register/complete', [RegisteredUserController::class, 'showComplete'])
        ->middleware(['signed', 'throttle:10,1'])
        ->name('register.complete.form');
    Route::post('/register/complete', [RegisteredUserController::class, 'complete'])
        ->middleware(['signed', 'throttle:10,1'])
        ->name('register.complete');

    // ③ ログイン
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])->middleware('throttle:5,1');
});

// 認証済みルート（本登録完了・ログイン必須）
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', function () {
        return view('dashboard', ['user' => Auth::user()]);
    })->name('dashboard');

    // タスク・スケジュール管理 API (ダッシュボードのカレンダー画面連携)
    Route::get('/tasks/events', [TaskController::class, 'events'])->name('tasks.events');
    Route::post('/tasks', [TaskController::class, 'store'])->name('tasks.store');
    Route::put('/tasks/{task}', [TaskController::class, 'update'])->name('tasks.update');
    Route::delete('/tasks/{task}', [TaskController::class, 'destroy'])->name('tasks.destroy');

    // カレンダー共有設定 API
    Route::post('/calendar/share-settings', [SharedCalendarController::class, 'updateSettings'])->name('calendar.share.settings');

    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])
        ->name('logout');
});

// カレンダー共有・外部予約用 公開ルート（未ログインでもアクセス可能）
Route::get('/share/{token}', [SharedCalendarController::class, 'show'])->name('calendar.share');
Route::get('/share/{token}/events', [SharedCalendarController::class, 'events'])->name('calendar.share.events');
Route::post('/share/{token}/schedule', [SharedCalendarController::class, 'store'])
    ->middleware('throttle:10,1')
    ->name('calendar.share.store');

// ヘルスチェック用エンドポイント（内部情報を非公開化）
Route::get('/health', function () {
    try {
        DB::connection()->getPdo();

        return response()->json(['status' => 'ok'], 200);
    } catch (Throwable $e) {
        Log::error('Health check database failure: '.$e->getMessage());

        return response()->json(['status' => 'error'], 503);
    }
});
