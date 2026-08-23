<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Notifications\ProvisionalRegisterNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    /**
     * ① 仮登録画面の表示（メールアドレスのみ入力）
     */
    public function create(): View
    {
        return view('auth.register');
    }

    /**
     * ② 仮登録処理（署名付き本登録URLの生成 & メール送信）
     */
    public function store(Request $request): RedirectResponse
    {
        if ($request->has('email')) {
            $request->merge([
                'email' => Str::lower(trim((string) $request->email)),
            ]);
        }

        $request->validate([
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
        ], [
            'email.unique' => 'このメールアドレスは既に登録されています。',
        ]);

        $email = $request->email;

        // 60分間有効な署名付き本登録URLを生成
        $verificationUrl = URL::temporarySignedRoute(
            'register.complete.form',
            now()->addMinutes(60),
            ['email' => $email]
        );

        // 認証メールを送信
        Notification::route('mail', $email)->notify(new ProvisionalRegisterNotification($verificationUrl));

        return redirect()->route('register.sent')->with('registered_email', $email);
    }

    /**
     * ③ 仮登録完了・案内画面の表示
     */
    public function sent(): View
    {
        return view('auth.register_sent');
    }

    /**
     * ④ メール内署名付きURLクリック時の本登録フォーム表示
     */
    public function showComplete(Request $request): View|RedirectResponse
    {
        $email = Str::lower(trim((string) $request->query('email')));

        // 既に本登録済みの場合はログインへリダイレクト
        if (User::where('email', $email)->exists()) {
            return redirect()->route('login')->with('status', 'このメールアドレスは既に本登録が完了しています。ログインしてください。');
        }

        return view('auth.register_complete', [
            'email' => $email,
            'signedUrl' => $request->fullUrl(),
        ]);
    }

    /**
     * ⑤ アカウント名・パスワードを設定して本登録完了
     */
    public function complete(Request $request): RedirectResponse
    {
        $email = Str::lower(trim((string) $request->query('email')));

        if (empty($email) || User::where('email', $email)->exists()) {
            return redirect()->route('login')->withErrors([
                'login' => '無効なリクエスト、または既に本登録済みのメールアドレスです。',
            ]);
        }

        $request->validate([
            'name' => ['required', 'string', 'max:255', 'alpha_dash:ascii', 'unique:users,name'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ], [
            'name.alpha_dash' => 'アカウント名は半角英数字、ダッシュ(-)、アンダースコア(_)のみ使用できます。',
        ]);

        $user = new User([
            'name' => $request->name,
            'email' => $email,
            'password' => $request->password,
        ]);
        $user->forceFill(['email_verified_at' => now()])->save();

        return redirect()->route('login')->with('status', '本登録が完了しました！メールアドレス（またはアカウント名）とパスワードでログインしてください。');
    }
}
