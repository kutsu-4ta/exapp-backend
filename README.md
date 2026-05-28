# exapp (tsumiki) — バックエンド

資格試験・受験勉強を管理する Web アプリ **tsumiki** のバックエンドリポジトリ。

対応するフロントエンド: `exapp-frontend`

---

## 目次

1. [アプリ概要](#アプリ概要)
2. [技術スタック](#技術スタック)
3. [セットアップ](#セットアップ)
4. [アーキテクチャ](#アーキテクチャ)
5. [API 一覧](#api-一覧)
6. [主要ドメインの説明](#主要ドメインの説明)
7. [不要コードの洗い出し](#不要コードの洗い出し)

---

## アプリ概要

### 主要機能

| 機能 | 説明 |
|------|------|
| **Workspace** | 日次学習ログ（時間帯別セッション記録・振り返り・完了フラグ） |
| **NoteList** | 問題・ミスのデータベース（習熟度・失敗属性タグ・`#Definition` などのハッシュタグ） |
| **BugFix** | フラッシュカード形式の復習（Morning / Flash / Deg の 3 モード） |
| **Exam** | 模試・過去問セッション管理と得点分析 |
| **Practice** | 科目別フラッシュカード練習 |
| **Sprint** | スタディチケットの Kanban 管理（目標設定・振り返り） |
| **Dashboard** | 学習統計・AI アドバイス・ストップウォッチ |
| **Subject** | 科目設定・活動グラフ・月次目標 |

### BugFix の仕組み

問題ノートの `#Definition` ハッシュタグを解析し、Gemini API でキーワードを生成してフラッシュカードを作る。

```
GET /morning-bugfix または /deg-bugfix
  ↓
#Definition を持つ問題を選出
  ↓
NoteParser で #Definition / #Formula を抽出
  ↓
Gemini API（一括）でキーワード質問を生成
  ↓
quiz: { question: <キーワード質問>, explanation: <定義 + 公式> }
```

Gemini 失敗時のフォールバック: 定義の先頭 20 文字 + "とは？"

---

## 技術スタック

| レイヤー | 技術 |
|---------|------|
| Framework | Laravel 11 (PHP 8.3) |
| DB | PostgreSQL 17 |
| Auth | Firebase Authentication + Laravel Sanctum |
| AI | Google Gemini API（ユーザーが API キーを登録） |
| インフラ | Docker Compose（開発）/ Cloud Run（本番） |
| テスト | PHPUnit |

---

## セットアップ

### 前提条件

- Docker / Docker Compose
- Firebase プロジェクト（Admin SDK サービスアカウント）

### 手順

```bash
# 1. リポジトリをクローン
git clone <repository-url>
cd exapp-backend

# 2. 環境変数ファイルを作成
cp .env.example .env

# 3. .env を編集
#    必須:
#      FIREBASE_PROJECT_ID   ... Firebase のプロジェクト ID
#      FIREBASE_CREDENTIALS  ... サービスアカウント JSON の内容（文字列）
#      APP_KEY               ... php artisan key:generate で生成

# 4. コンテナを起動
docker compose up -d

# 5. マイグレーション実行
docker compose exec backend php artisan migrate

# 6. APP_KEY の生成（未設定の場合）
docker compose exec backend php artisan key:generate
```

サーバーは `http://localhost:8000` で起動する。

### 主要な環境変数

| 変数 | 説明 |
|------|------|
| `FIREBASE_PROJECT_ID` | Firebase プロジェクト ID |
| `FIREBASE_CREDENTIALS` | サービスアカウント JSON（文字列） |
| `CORS_ALLOWED_ORIGINS` | フロントエンドの URL（例: `http://localhost:5173`） |
| `GEMINI_API_KEY` | ダミー値で可（実際のキーはユーザーが DB に登録） |
| `SANCTUM_TOKEN_EXPIRATION_DAYS` | トークン有効期限（日数） |

### 本番デプロイ（Cloud Run）

```bash
make deploy
# docker build → push → gcloud run deploy を一括実行
```

---

## アーキテクチャ

### ディレクトリ構成

```
app/
├── Http/
│   ├── Controllers/   # 32 コントローラ（薄い。UseCase に委譲）
│   ├── Requests/      # 34 バリデーションクラス
│   └── Resources/     # 13 API レスポンス整形
├── UseCases/          # ドメイン別 UseCase（1クラス1操作）
├── Models/            # 25 Eloquent モデル
├── Services/          # GeminiService, NoteParser, AiAdvice など
├── Infrastructure/
│   └── Repositories/  # 19 Repository（Eloquent 実装）
└── Enums/             # 12 Enum 定義
```

### 設計方針

```
Request → Controller → UseCase → Repository → Model
                  ↕
              Service（Gemini, NoteParser など）
```

- **Controller** はリクエストの受け取りとレスポンスの返却のみ
- **UseCase** に業務ロジックを集約（1クラス1操作）
- **Repository** でデータアクセスを抽象化
- DI はコンストラクタインジェクションで統一

### 主要モデルとリレーション

```
User
├── UserProfile          # ニックネーム・目標・Gemini トークン
├── AiUserProfile        # AI アドバイス用正規化プロフィール
├── Subject[]
│   ├── SubCategory[]
│   └── SubjectSetting
├── DailyLog[]
│   └── StudySession[]
├── Problem[]
│   └── ProblemQuiz[]
├── ExamSession[]
│   └── ExamQuestion[]
├── Sprint[]
│   └── StudyTicket[]
│       ├── TicketNote[]
│       └── SubCategory[] (多対多)
├── Stopwatch
├── Snippet[]
└── AlertSetting
```

---

## API 一覧

| グループ | 主なエンドポイント |
|---------|-----------------|
| Auth | `POST /auth/google`, `POST /auth/logout`, `GET /auth/me` |
| Profile | `GET/PUT /profile` |
| Subjects | `GET /subjects`, `PUT/DELETE /subjects/{name}` |
| Daily Logs | `GET/POST /daily-logs`, `GET/PUT/DELETE /daily-logs/{date}` |
| Study Sessions | `POST/PUT/DELETE /study-sessions` |
| Problems | `GET/POST/PUT/DELETE /problems/{id}`, `GET/POST/DELETE /problems/{id}/quizzes` |
| BugFix | `GET /morning-bugfix`, `GET /deg-bugfix` |
| Practice | `GET/PUT/DELETE /practice/sessions/draft/{subject}`, `POST /practice/sessions` |
| Exam | `GET/POST/PUT/DELETE /exam-sessions`, `PATCH /exam-sessions/{id}/questions/{order}` |
| Sprints | `GET/POST/PUT/DELETE /sprints/{id}`, `POST /sprints/{id}/complete` |
| Tickets | `GET/POST/PUT/DELETE /tickets/{id}`, `GET/POST/PUT/DELETE /tickets/{id}/notes` |
| Stopwatch | `GET /stopwatch`, `POST /stopwatch/{start\|stop\|reset}` |
| AI | `POST /ai/advice`, `POST /ai/analysis` |
| Settings | `GET/PUT /gemini/settings`, `GET/PUT /monthly-settings/{year}/{month}` |
| Snippets | `GET/POST/PUT/DELETE /snippets` |

詳細は `doc/openapi.yaml` を参照。

---

## 主要ドメインの説明

### Problem（問題）

ミス・苦手問題のレコード。フィールドの意味:

| カラム | 説明 |
|-------|------|
| `note` | マークダウン形式のノート。`#Definition` `#Formula` `#Keyword` などのハッシュタグが予約語 |
| `proficiency` | 習熟度（Basic / Intermediate / Advanced / Expert）|
| `failure_types` | 失敗属性 JSON（ケアレス / 理解不足 / 忘れ / その他）|
| `is_good_question` | 良問フラグ（Deg Bugfix の選出条件）|
| `is_formula` | 公式問題フラグ（NoteList フィルタで使用）|
| `last_touched_at` | 最終確認日（BugFix の選出で古い順/新しい順に使用）|

### BugFix の 3 モード

| モード | エンドポイント | 選出条件 |
|--------|--------------|---------|
| Morning Bugfix | `GET /morning-bugfix` | ランダム選出（習熟度・失敗属性・最終確認日でスコアリング）|
| Flash Bugfix | `GET /morning-bugfix?subject=...&...` | 科目・論点・属性・習熟度でフィルタ |
| Deg Bugfix | `GET /deg-bugfix` | `is_good_question=true` かつ `#Definition` あり |

すべての BugFix で `#Definition` を持つ問題のみが対象。

### AI 機能

- **Gemini API キー**: ユーザーが自身のキーをプロフィール画面で登録。DB に暗号化して保存。
- **API レート制限**: フロントエンド側で RPD/RPM を管理（`apiWeights.ts`）。
- **`AiUserProfile`**: ユーザープロフィールを AI に渡しやすい形に正規化したキャッシュ。プロフィール変更時に `UserProfileObserver` が自動更新。

---

## 不要コードの洗い出し

### 削除を推奨

#### `knowledge_digests` テーブル関連

```
app/Models/KnowledgeDigest.php
app/Observers/ProblemObserver.php
app/UseCases/KnowledgeDigest/ExtractKnowledgeDigestUseCase.php
database/migrations/2026_05_19_000001_create_knowledge_digests_table.php
```

**理由**: `ProblemObserver` が Problem 保存時に自動書き込みしているが、**どの API からも読み出されていない**。BugFix カード生成は `GenerateBugfixCardUseCase` が `Problem.note` を直接 `NoteParser` で解析するため完全に冗長。

#### `problem_quizzes.options` / `problem_quizzes.correct_index` カラム

**理由**: 廃止した4択生成モード（旧 BugFix の `multiple_choice`）用のカラム。現在の BugFix はカード形式のみのため、新規データは入らない。

### 要確認（UI での利用状況を確かめてから判断）

#### `problem_quizzes` テーブル全体

`/problems/{id}/quizzes` の CRUD エンドポイントは存在するが、現在のフロントエンド UI でどこから呼ばれているかを確認すること。使われていない場合はテーブルごと削除可能。

#### Flashcard エンドポイント

```
GET /flashcards
GET /subjects/{subject}/flashcards
```

`FlashcardController` に対応するルートが存在するが、フロントエンドで実際に表示しているページ・コンポーネントを確認すること。

#### `practice_sessions` テーブル

`POST /practice/sessions` で書き込まれるが、読み出す API が存在しない。ログ目的のみであれば整理を検討。

### 既に削除済み（2026-05 改修）

| 削除対象 | 理由 |
|---------|------|
| `BugfixSessionController` 他 3 コントローラ | BugFix リデザインで不要に |
| `GenerateMorningQuizUseCase` 他 5 UseCase | 4択生成廃止 |
| `/api/flash-card` エンドポイント | 同上 |
| `problems.defeat_reason` カラム | マイグレーションで DROP 済み |
