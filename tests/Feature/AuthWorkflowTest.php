<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\ProvisionalRegisterNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class AuthWorkflowTest extends TestCase
{
    use RefreshDatabase;

    /**
     * ① メールアドレスのみで仮登録：認証メールが送信され、users テーブルにはまだ作成されないテスト
     */
    public function test_provisional_registration_sends_email_and_does_not_create_user(): void
    {
        Notification::fake();

        $response = $this->post('/register', [
            'email' => 'newuser@example.com',
        ]);

        $this->assertGuest();
        $response->assertRedirect(route('register.sent'));

        // まだ users にはレコードが作成されていないこと（事前登録攻撃の排除）
        $this->assertDatabaseMissing('users', ['email' => 'newuser@example.com']);

        // 本登録案内通知が送信されていること
        Notification::assertSentOnDemand(ProvisionalRegisterNotification::class);
    }

    /**
     * ② メールアドレスの大文字入力が自動で小文字に正規化されるテスト
     */
    public function test_email_is_normalized_to_lowercase_on_provisional_registration(): void
    {
        Notification::fake();

        $this->post('/register', [
            'email' => 'Uppercase@Example.COM',
        ]);

        Notification::assertSentOnDemand(
            ProvisionalRegisterNotification::class,
            function (ProvisionalRegisterNotification $notification, $channels, $notifiable) {
                return str_contains($notification->verificationUrl, 'email=uppercase%40example.com')
                    || str_contains($notification->verificationUrl, 'email=uppercase@example.com');
            }
        );
    }

    /**
     * ③ 既に登録済みのメールアドレスでの仮登録は拒否されるテスト
     */
    public function test_provisional_registration_rejects_already_registered_email(): void
    {
        User::factory()->create([
            'email' => 'existing@example.com',
        ]);

        $response = $this->post('/register', [
            'email' => 'existing@example.com',
        ]);

        $response->assertSessionHasErrors('email');
    }

    /**
     * ④ 有効な署名付きURLで本登録画面が表示されるテスト
     */
    public function test_show_complete_form_displays_for_valid_signed_url(): void
    {
        $signedUrl = URL::temporarySignedRoute(
            'register.complete.form',
            now()->addMinutes(60),
            ['email' => 'valid@example.com']
        );

        $response = $this->get($signedUrl);

        $response->assertStatus(200)
            ->assertSee('本登録（アカウント設定）')
            ->assertSee('valid@example.com');
    }

    /**
     * ⑤ 本登録処理：アカウント名・パスワードを設定して users 作成＆ログイン画面へ遷移するテスト
     */
    public function test_complete_registration_creates_user_and_allows_login(): void
    {
        $signedPostUrl = URL::temporarySignedRoute(
            'register.complete',
            now()->addMinutes(60),
            ['email' => 'complete@example.com']
        );

        $response = $this->post($signedPostUrl, [
            'name' => 'complete_user',
            'password' => 'Password123',
            'password_confirmation' => 'Password123',
        ]);

        // 本登録完了後は自動ログインせずログイン画面へ
        $this->assertGuest();
        $response->assertRedirect(route('login'));
        $response->assertSessionHas('status');

        // users テーブルに本登録レコードが存在すること
        $user = User::where('email', 'complete@example.com')->first();
        $this->assertNotNull($user);
        $this->assertEquals('complete_user', $user->name);
        $this->assertNotNull($user->email_verified_at);

        // その後、正常にログインできること
        $loginRes = $this->post('/login', [
            'login' => 'complete_user',
            'password' => 'Password123',
        ]);
        $loginRes->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($user);
    }

    /**
     * ⑥ 本登録時の強力なパスワードポリシーバリデーションテスト
     */
    public function test_password_requires_mixed_case_and_numbers_on_complete(): void
    {
        $signedPostUrl = URL::temporarySignedRoute(
            'register.complete',
            now()->addMinutes(60),
            ['email' => 'pwdtest@example.com']
        );

        // 1. 数字なし（NG）
        $res1 = $this->post($signedPostUrl, [
            'name' => 'pwd_user1',
            'password' => 'PasswordOnly',
            'password_confirmation' => 'PasswordOnly',
        ]);
        $res1->assertSessionHasErrors('password');

        // 2. 大文字なし（NG）
        $res2 = $this->post($signedPostUrl, [
            'name' => 'pwd_user2',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);
        $res2->assertSessionHasErrors('password');
    }

    /**
     * ⑦ 本登録時のアカウント名（alpha_dash:ascii）バリデーションテスト
     */
    public function test_name_rejects_non_ascii_and_special_chars_on_complete(): void
    {
        $signedPostUrl = URL::temporarySignedRoute(
            'register.complete',
            now()->addMinutes(60),
            ['email' => 'nametest@example.com']
        );

        // 1. 日本語ユーザー名の拒否
        $res1 = $this->post($signedPostUrl, [
            'name' => 'ねこ太郎',
            'password' => 'Password123',
            'password_confirmation' => 'Password123',
        ]);
        $res1->assertSessionHasErrors('name');

        // 2. 特殊記号（@）の拒否
        $res2 = $this->post($signedPostUrl, [
            'name' => 'neko@example.com',
            'password' => 'Password123',
            'password_confirmation' => 'Password123',
        ]);
        $res2->assertSessionHasErrors('name');
    }

    /**
     * ⑧ 本登録時のアカウント名重複拒否テスト
     */
    public function test_name_must_be_unique_on_complete(): void
    {
        User::factory()->create([
            'name' => 'existing_name',
        ]);

        $signedPostUrl = URL::temporarySignedRoute(
            'register.complete',
            now()->addMinutes(60),
            ['email' => 'unique@example.com']
        );

        $response = $this->post($signedPostUrl, [
            'name' => 'existing_name',
            'password' => 'Password123',
            'password_confirmation' => 'Password123',
        ]);

        $response->assertSessionHasErrors('name');
    }

    /**
     * ⑨ 改ざんされた署名や期限切れのURLでは本登録が拒否されるテスト (403 Forbidden)
     */
    public function test_complete_registration_rejected_with_invalid_or_tampered_signature(): void
    {
        // 1. 署名改ざん
        $validUrl = URL::temporarySignedRoute(
            'register.complete.form',
            now()->addMinutes(60),
            ['email' => 'tamper@example.com']
        );
        $tamperedUrl = $validUrl.'tampered';
        $res1 = $this->get($tamperedUrl);
        $res1->assertStatus(403);

        // 2. 期限切れ（10分前）
        $expiredUrl = URL::temporarySignedRoute(
            'register.complete.form',
            now()->subMinutes(10),
            ['email' => 'expired@example.com']
        );
        $res2 = $this->get($expiredUrl);
        $res2->assertStatus(403);
    }

    /**
     * ⑩ 本登録済みユーザーはメールアドレスでもアカウント名でもログインできるテスト
     */
    public function test_verified_user_can_login_with_email_or_name(): void
    {
        $user = User::factory()->create([
            'name' => 'tanaka_taro',
            'email' => 'tanaka@example.com',
            'password' => bcrypt('Secret1234'),
            'email_verified_at' => now(),
        ]);

        // メールアドレスでログイン
        $res1 = $this->post('/login', ['login' => 'tanaka@example.com', 'password' => 'Secret1234']);
        $res1->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($user);

        $this->post('/logout');

        // アカウント名でログイン
        $res2 = $this->post('/login', ['login' => 'tanaka_taro', 'password' => 'Secret1234']);
        $res2->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($user);
    }

    /**
     * DatabaseSeeder でテスト用ユーザーが正しく生成・ログインできるテスト
     */
    public function test_database_seeder_creates_test_users(): void
    {
        $this->seed();

        $this->assertDatabaseHas('users', [
            'name' => 'testuser',
            'email' => 'test@example.com',
        ]);

        $res = $this->post('/login', [
            'login' => 'testuser',
            'password' => 'Password123',
        ]);
        $res->assertRedirect('/dashboard');
        $this->assertAuthenticated();
    }
}
