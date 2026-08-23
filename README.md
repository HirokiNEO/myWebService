# myWebService

Laravel 12 & PHP 8.3 をベースにした、セキュアな2段階仮登録・メール認証（Signed URL）およびマルチログインを備えた Web 認証サービスです。

## 主な特徴とセキュリティ設計

### 1. 安全な2段階仮登録フロー（事前登録・乗っ取り攻撃の完全遮断）
- **仮登録 (Step 1):** ユーザーは「メールアドレス」のみを入力。この時点では `users` テーブルにレコードを作成せず、暗号署名付きURL（Signed URL, 60分有効）を記載した認証メールを送信します。
- **本登録 (Step 2):** メールを受け取った本人がリンクを開いた時のみ、アカウント名（ユーザー名）とパスワードを設定してアカウントを新規作成します。
- **セキュリティ上の利点:** 攻撃者が他人のメールアドレスと任意のパスワードで事前登録して乗っ取る脆弱性を根本から排除しています。また、未認証データがデータベースを汚染することもありません。

### 2. その他のセキュリティ対策
- **メールアドレスの自動正規化:** 登録・ログイン時にメール入力を小文字・トリム正規化（大文字混じりでも自然にログイン可能）
- **厳格なパスワードポリシー:** `Password::defaults()` により 8文字以上・英大文字・英小文字・数字の混在を必須化（本番環境では漏洩パスワード検知 `uncompromised()` を自動有効化）
- **アカウント名の ASCII 強制:** `alpha_dash:ascii` により半角英数字・ハイフン・アンダースコアのみに限定（日本語や特殊記号を完全排除）
- **マルチログイン & タイミング攻撃対策:** メールアドレス・アカウント名の両方でログイン可能。存在しないユーザーに対してもダミーハッシュを検証し、応答時間差によるユーザー列挙を防止
- **Laravel 12 最新ネイティブ構成:** 旧来の Kernel / Handler を廃止し、`bootstrap/app.php` に集約した洗練された設計

## 技術スタック
- **Backend:** PHP 8.3 / Laravel 12.x
- **Database:** MySQL 8.0 / SQLite (Testing: In-Memory)
- **Frontend:** Blade / Tailwind CSS 4 / Vite 6
- **CI:** GitHub Actions (Automated Tests, Laravel Pint Code Style, NPM/Composer Security Audit)

## セットアップ手順

```bash
# 1. 依存関係のインストール & フロントエンドビルド
composer install
npm install
npm run build

# 2. 環境設定
cp .env.example .env
php artisan key:generate

# 3. .env の DB / メール設定を調整後、マイグレーション & テストデータ投入
php artisan migrate --seed
```

### 開発・検証用テストアカウント (Seeder)

`php artisan db:seed` を実行すると、即座にログイン検証可能なテスト用アカウントが自動作成されます。

| アカウント名 (ID) | メールアドレス | パスワード | 状態 |
| :--- | :--- | :--- | :--- |
| `testuser` | `test@example.com` | `Password123` | 本登録完了済み |
| `taro_yamada` | `taro@example.com` | `Password123` | 本登録完了済み |

## テスト実行

```bash
# In-Memory SQLite で高速に全13テストを実行
php artisan test

# コードスタイル検査
./vendor/bin/pint --test

# セキュリティ監査
composer audit
npm audit --audit-level=high
```
