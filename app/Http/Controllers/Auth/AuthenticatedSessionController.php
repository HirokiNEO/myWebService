<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    private const DUMMY_HASH = '$2y$12$e8Vd9.mK9e94FjA87nQv3eR1zF2X1a0Kq7bY4c0P5m8J6g5K1L9u.';

    /**
     * ログイン画面の表示
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * ログイン処理（アカウント名 / メールアドレス両対応 & 未認証ガード）
     */
    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'login' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $rawLogin = trim($credentials['login']);
        $isEmail = filter_var($rawLogin, FILTER_VALIDATE_EMAIL) !== false;
        $loginInput = $isEmail ? Str::lower($rawLogin) : $rawLogin;
        $fieldType = $isEmail ? 'email' : 'name';

        $user = User::where($fieldType, $loginInput)->first();
        $hashToVerify = $user ? $user->password : self::DUMMY_HASH;
        $isValidPassword = Hash::check($credentials['password'], $hashToVerify);

        if (! $user || ! $isValidPassword) {
            return back()->withErrors([
                'login' => '指定されたログイン情報が正しくありません。',
            ])->onlyInput('login');
        }

        // メール未認証（仮登録）状態のガード
        if (! $user->hasVerifiedEmail()) {
            return back()->withErrors([
                'login' => 'アカウントは仮登録の状態です。ご登録のメールアドレスに送信された認証メールから本登録を完了してください。',
            ])->with('unverified_email', $user->email)->onlyInput('login');
        }

        Auth::login($user, $request->boolean('remember'));
        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }

    /**
     * ログアウト処理
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('status', 'ログアウトしました。');
    }
}
