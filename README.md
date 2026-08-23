# myWebService - Googleカレンダー風 タスク・スケジュール管理アプリ

Laravel 12 & PHP 8.3 をベースにした、セキュアな2段階仮登録・メール認証（Signed URL）および Googleカレンダー風のタスク・スケジュール管理を備えた Web アプリケーションです。

## 主な機能と特徴

### 1. Googleカレンダー風 タスク・スケジュール管理 (FullCalendar v6)
- **日・週・月（Day / Week / Month）表示切替:** ワンクリックで「月間ビュー」「週間タイムグリッド」「日別タイムグリッド」をスムーズに切り替え
- **直感的なスケジュール追加:** カレンダー上の日付や時間枠を直接クリック/選択してモーダルから簡単追加
- **時間範囲指定 & 終日対応:** 開始日時・終了日時の範囲指定、終日イベントフラグに対応
- **メモ・詳細説明:** アジェンダやタスクの備忘録をリッチに記録
- **色分けラベル & 完了管理:** 6色のカラーラベル（インディゴ、ブルー、エメラルド、アンバー、レッド、パープル）による分類とタスク完了トグル
- **ドラッグ＆ドロップ移動・リサイズ:** スケジュールの時間変更や日付移動をマウス操作で即時更新

### 2. 安全な2段階仮登録・認証基盤
- **仮登録 (Step 1):** ユーザーは「メールアドレス」のみを入力。この時点では `users` テーブルにレコードを作成せず、暗号署名付きURL（Signed URL, 60分有効）を記載した認証メールを送信
- **本登録 (Step 2):** メールを受け取った本人がリンクを開いた時のみ、アカウント名とパスワードを設定してアカウントを新規作成（事前登録・乗っ取り攻撃を完全排除）
- **メールアドレスの自動正規化:** 登録・ログイン時にメール入力を小文字・トリム正規化
- **厳格なパスワードポリシー:** 8文字以上、英大文字・英小文字・数字の混在必須
- **マルチログイン & タイミング攻撃対策:** メールアドレス・アカウント名の両対応、ダミーハッシュによる定数時間比較
- **Laravel 12 最新ネイティブ構成:** 旧来の Kernel / Handler を廃止し、`bootstrap/app.php` に集約

## 技術スタック
- **Backend:** PHP 8.3 / Laravel 12.x
- **Database:** MySQL 8.0 / SQLite (Testing: In-Memory)
- **Frontend:** Blade / Tailwind CSS 4 / Vite 6 / FullCalendar v6
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

`php artisan db:seed` を実行すると、サンプルスケジュールが登録されたテスト用アカウントが自動作成されます。

| アカウント名 (ID) | メールアドレス | パスワード |
| :--- | :--- | :--- |
| `testuser` | `test@example.com` | `Password123` |
| `taro_yamada` | `taro@example.com` | `Password123` |

## テスト実行

```bash
# In-Memory SQLite で高速に全21テストを実行
php artisan test

# コードスタイル検査
./vendor/bin/pint --test

# セキュリティ監査
composer audit
npm audit --audit-level=high
```
