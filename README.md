# myWebService - Googleカレンダー風 タスク・スケジュール管理 & 共有予約システム

Laravel 12 & PHP 8.3 をベースにした、セキュアな2段階仮登録・メール認証（Signed URL）、Googleカレンダー風のタスク・スケジュール管理、および**プライバシー保護されたカレンダー共有 & 外部予約機能**を備えた Web アプリケーションです。

## 主な機能と特徴

### 1. 今日以降（未来）のスケジュール表示 & 過去日ブロック
- **過去の予定非表示:** メインカレンダーおよび共有カレンダーの双方で、昨日以前の過去のスケジュールは自動的に非表示となり、今日以降のスケジュールのみを整理して表示
- **過去日時への予約禁止:** 共有カレンダー（`/share/{token}`）では、過去の日付・時間がグレーアウトされ、誤って過去の日時に予約を入れることを防止

### 2. プライバシー保護型 カレンダー共有 & 外部予約機能 (`/share/{token}`)
- **アカウント不要で予約受付:** 専用の共有URL（`/share/{token}`）を相手に送るだけで、アカウントのない外部ユーザーでも空き時間を確認してスケジュール予約を追加可能
- **完全なプライバシー保護（機密マスク）:**
  - 持ち主の既存のスケジュールは、タイトル・メモ（詳細）・色を一切非公開にし、一律 **「予定あり (予約不可)」** としてスレートグレーで表示
  - 持ち主が「終日予定」を入れている日は、時間軸全体（00:00〜24:00）が濃いグレーでブロックされ、その日全体が予約不可になります
- **ダブルブッキング防止:** 既存の予定や終日予定と重複する時間帯への予約は自動でブロック（422エラー）
- **ワンクリック共有URLコピー:** ダッシュボード上に共有URLとコピーボタンを常設

### 3. Googleカレンダー風 タスク・スケジュール管理 (FullCalendar v6)
- **日・週・月（Day / Week / Month）表示切替:** ワンクリックでビュー切り替え
- **直感的なスケジュール追加・編集:** 日付・時間枠クリックからのモーダル追加
- **ビジュアルカラーパレット:** 8色の円形カラーパレットによる直感的な色分け
- **24時間全時間帯表示 & 現在時刻ライン:** 早朝・深夜帯の管理と現在時刻インジケーター

### 4. 安全な2段階仮登録・認証基盤
- **仮登録 (Step 1):** メールアドレスのみを入力（`users` に未認証レコードを作らず事前登録攻撃を排除）
- **本登録 (Step 2):** 署名付きURL（60分有効）から本人が名前とパスワードを設定
- **メール自動正規化 & 強力なパスワードポリシー:** 小文字トリム正規化、大文字・小文字・数字必須
- **マルチログイン対応:** メールアドレス・アカウント名の両方でログイン可能

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

`php artisan db:seed` を実行すると、サンプルスケジュールおよび共有URLが登録されたテスト用アカウントが自動作成されます。

| アカウント名 (ID) | メールアドレス | パスワード | 共有URLトークン |
| :--- | :--- | :--- | :--- |
| `testuser` | `test@example.com` | `Password123` | `testuser-share-token-1234567890` |
| `taro_yamada` | `taro@example.com` | `Password123` | `taroyamada-share-token-1234567890` |

- **テスト用共有URL:** [`http://localhost:7777/share/testuser-share-token-1234567890`](http://localhost:7777/share/testuser-share-token-1234567890)

## テスト実行

```bash
# In-Memory SQLite で高速に全35テストを実行
php artisan test

# コードスタイル検査
./vendor/bin/pint --test

# セキュリティ監査
composer audit
npm audit --audit-level=high
```
