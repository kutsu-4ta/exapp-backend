# 引き継ぎ資料 — exapp (tsumiki)

作成日: 2026-05-28

---

## 1. アプリ概要

**tsumiki** は、資格試験・受験勉強を管理するためのモバイル/iPad向けWebアプリ。

### 主要機能

| 機能 | 説明 |
|------|------|
| **Workspace** | 日次学習ログ（時間帯別セッション記録、振り返り）|
| **NoteList** | 問題・ミスのデータベース（習熟度・失敗属性タグ管理）|
| **BugFix** | フラッシュカード形式の復習モード（Morning / Flash / Deg）|
| **Exam** | 模試・過去問のセッション管理と得点分析 |
| **Practice** | 科目別フラッシュカード練習 |
| **Sprint** | スタディチケットのKanban管理（目標設定・振り返り）|
| **Dashboard** | 学習統計、AI アドバイス、ストップウォッチ |
| **Subject** | 科目設定、活動グラフ、月次目標 |

### BugFix モードの仕組み（2026-05 改修済み）

問題ノートの `#Definition` ハッシュタグを解析し、Gemini API でキーワードを抽出してフラッシュカードを生成する。

- **カード表**: Gemini が `#Definition` 本文から生成したキーワード質問
- **カード裏**: `#Definition` 本文 ＋ `#Formula`（存在する場合）
- **Gemini 失敗時のフォールバック**: 定義の先頭20文字 + "とは？"
- `#Definition` のないプロブレムは対象外

---

## 2. 技術スタック

### バックエンド
- **Framework**: Laravel 11 (PHP)
- **DB**: PostgreSQL
- **Auth**: Firebase Authentication + Laravel Sanctum
- **AI**: Google Gemini API
- **Architecture**: Controller → UseCase → Repository → Eloquent Model

### フロントエンド
- **Framework**: React 19 + TypeScript
- **Build**: Vite
- **Routing**: React Router v7
- **State**: Zustand v5
- **CSS**: Tailwind CSS v4
- **Chart**: Recharts
- **Auth**: Firebase SDK

---

## 3. バックエンド構成

### ディレクトリ構造

```
app/
├── Http/
│   ├── Controllers/   32 コントローラ
│   ├── Requests/      34 バリデーションクラス
│   └── Resources/     13 APIレスポンス整形
├── UseCases/          ドメイン別 UseCase（96クラス）
├── Models/            25 Eloquentモデル
├── Services/          GeminiService, NoteParser, AiAdvice など
├── Infrastructure/
│   └── Repositories/  19 Repositoryクラス
└── Enums/             12 Enum定義
```

### 主要APIエンドポイント一覧

| グループ | エンドポイント数 | 主なパス |
|---------|----------------|---------|
| Auth | 4 | `/auth/google`, `/auth/logout`, `/auth/me` |
| Profile | 2 | `/profile` |
| Subjects | 8+ | `/subjects`, `/subjects/{name}/settings` |
| Daily Logs | 8 | `/daily-logs`, `/daily-logs/{date}` |
| Study Sessions | 3 | `/study-sessions` |
| Problems | 9 | `/problems`, `/problems/{id}/quizzes` |
| BugFix | 2 | `/morning-bugfix`, `/deg-bugfix` |
| Practice | 4 | `/practice/sessions` |
| Exam | 8 | `/exam-sessions` |
| Sprints | 7 | `/sprints` |
| Tickets | 12 | `/tickets`, `/tickets/{id}/notes` |
| Stopwatch | 4 | `/stopwatch` |
| AI | 2 | `/ai/advice`, `/ai/analysis` |
| Snippets | 4 | `/snippets` |
| Materials | 3 | `/materials` |
| Dashboard | 1 | `/dashboard` |
| Settings | 4 | `/monthly-settings`, `/alert-settings`, `/gemini/settings` |

### 主要テーブル一覧

| テーブル | 説明 |
|---------|------|
| `users` | Firebase UID でログイン |
| `user_profiles` | ニックネーム・目標・Gemini トークン |
| `ai_user_profiles` | AI アドバイス用プロフィール（正規化済み） |
| `subjects` | 科目（ユーザー定義） |
| `sub_categories` | 論点（科目配下） |
| `materials` | 教材名 |
| `daily_logs` | 日次ログ（振り返り・完了フラグ） |
| `study_sessions` | 学習セッション（時間帯・科目・分数・教材） |
| `problems` | 問題・ミスのレコード |
| `problem_quizzes` | 問題に紐づくクイズ |
| `practice_sessions` | 練習セッション履歴 |
| `practice_session_drafts` | 練習途中の下書き |
| `exam_sessions` | 模試・過去問セッション |
| `exam_questions` | 模試の設問詳細 |
| `sprints` | スプリント |
| `study_tickets` | チケット |
| `ticket_sub_categories` | チケット×論点（多対多） |
| `ticket_notes` | チケットのメモ |
| `stopwatches` | ストップウォッチ状態 |
| `snippets` | クイックメモ |
| `alert_settings` | アラート設定（グローバル） |
| `subject_alert_settings` | アラート設定（科目別） |
| `monthly_settings` | 月次目標（分数レンジ） |
| `subject_settings` | 科目設定（目標・テーマカラー） |
| `subject_monthly_goals` | 科目別月次目標 |
| `knowledge_digests` | ノートのハッシュタグ解析結果キャッシュ（→後述） |

---

## 4. フロントエンド構成

### ページ一覧

| ページ | ルート | 説明 |
|--------|--------|------|
| DashboardPage | `/` | 統計・AI アドバイス・ストップウォッチ |
| DailyWorkspacePage | `/workspace/:date` | 日次ワークスペース |
| DailyLogsPage | `/workspace/daily-logs` | 日次ログ一覧（2行レイアウト）|
| NoteListPage | `/notelist` | 問題データベース |
| PracticeSessionPage | `/practice/:subject` | フラッシュカード練習 |
| MorningBugfixPage | `/morning-bugfix` | Morning Bugfix |
| FlashBugfixPage | `/subjects/:name/flash-bugfix` | Flash / Deg Bugfix |
| ExamPage | `/exam` | 模試管理 |
| SubjectPage | `/subjects/:name` | 科目詳細 |
| SprintPage | `/sprint` | チケット Kanban |
| ProfilePage | `/profile` | プロフィール・設定 |
| ProblemGraphPage | `/problems/:id/graph` | 問題詳細（フルスクリーン）|

### 状態管理（Zustand）

| ストア | 永続化 | 内容 |
|--------|--------|------|
| `auth` | localStorage | ユーザー・トークン |
| `settings` | なし | 科目・教材・論点キャッシュ |
| `workspaceDraft` | localStorage | 日次ログ下書き |
| `ticketTemplates` | localStorage | チケットテンプレート |
| `sprintStore` | なし | スプリント・チケットキャッシュ |
| `practiceStore` | なし | 練習セッション状態 |
| `apiTraffic` | なし | API レート制限トラッキング |

### キーコンポーネント

- **`FlashCardSessionView`** — BugFix・Practice 共通のカード UI
- **`useFlashCardSession`** — フラッシュカードセッション状態 hook
- **`BottomSheet`** — モーダルの共通基盤
- **`NoteEditor`** — `#Definition` 等ハッシュタグ対応ノートエディタ

---

## 5. 不要なAPI・テーブル・カラムの洗い出し

### 5-1. 確実に不要（削除候補）

#### `knowledge_digests` テーブル

- `ProblemObserver` が Problem の保存時に自動書き込みしている
- しかし**どのAPIエンドポイントからも読み出されていない**
- BugFix カード生成は `GenerateBugfixCardUseCase` が `Problem.note` を直接 `NoteParser` で解析するため、このテーブルは不要
- 削除対象: `knowledge_digests` テーブル、`KnowledgeDigest` モデル、`ProblemObserver`、`ExtractKnowledgeDigestUseCase`

#### `subjects/{subject}/flashcards` エンドポイント / `flashcards` エンドポイント

- `FlashcardController` と `/flashcards`、`/subjects/{subject}/flashcards` が routes に存在
- フロントエンドの `subjects.ts` から呼ばれているが、実際に表示するページ・コンポーネントが存在するか要確認
- `ListFlashcardsUseCase`、`ListAllFlashcardsUseCase` も対象

#### `problem_quizzes.options` / `problem_quizzes.correct_index` カラム

- 4択クイズ（旧 BugFix の `multiple_choice` モード）用フィールド
- BugFix のリデザインで4択生成を廃止したため、新規データは入らない
- 既存データがある場合は移行を検討

### 5-2. 確認が必要（要レビュー）

#### `problem_quizzes` テーブル全体

- `/problems/{id}/quizzes` で CRUD は可能
- フロントエンドの `problem.ts` に API 呼び出しは残っているが、UI上でどこから呼ばれているかを確認
- もし問題別クイズ作成UI が存在しないなら、テーブルごと削除可能

#### `practice_sessions` テーブル

- `PracticeSession` モデルは存在し、`/practice/sessions` に POST している
- 作成したデータがどこかで分析・表示されているか要確認
- 読み出しエンドポイントが見当たらないため、書き込み専用（ログ目的）の可能性あり

#### `ai_user_profiles.normalized_prompt_json` / `translation_version`

- `AiProfileBuilder` が内部的に使用
- 翻訳キャッシュ目的のカラムで、外部公開はされていない
- 利用頻度が低い場合は整理対象

### 5-3. 既に削除済み（参考）

以下は今回の改修（2026-05）で削除・整理済み:

| 削除対象 | 理由 |
|---------|------|
| `BugfixSessionController` | BugFix リデザインで不要に |
| `DegWordCardController` | 同上 |
| `FlashCardSessionController` | 同上 |
| `GenerateMorningQuizUseCase` | 4択生成廃止 |
| `GenerateFlashCardQuizUseCase` | 同上 |
| `SelectDegWordCardQuizzesUseCase` | 同上 |
| `SelectSavedBugfixQuizzesUseCase` | 同上 |
| `SelectFlashCardProblemsUseCase` | 同上 |
| `FlashCardFilter` | 同上 |
| `/api/flash-card` エンドポイント | 同上 |
| `useQuizSession` hook (FE) | 4択UI廃止 |
| `QuizSessionView` component (FE) | 同上 |
| `problems.defeat_reason` カラム | マイグレーションで DROP 済み |

---

## 6. 注意事項・既知の設計判断

### BugFix カード生成フロー

```
GET /morning-bugfix (or /deg-bugfix)
  → SelectMorningProblemsUseCase (note LIKE '%#Definition%' でフィルタ)
  → GenerateBugfixCardUseCase
      → NoteParser::parse($problem->note) で #Definition, #Formula 抽出
      → Gemini API に一括送信（問題ID + 定義文）
      → 返却: [{ problem_id, front }]
      → カード裏: definition + formula
  → quiz: { question: front, explanation: back } 形式で返却
```

### フロントエンドの API レート制限

`src/lib/api/apiWeights.ts` でモデル別 RPD/RPM を管理。Gemini は 20 RPD / 5 RPM。

### PHP クロージャの `use` リスト

バックエンドで `function() use ($var)` を使う際、明示的なキャプチャが必要。アロー関数 `fn()` は自動キャプチャ。

### `is_formula` フラグ

`problems.is_formula` はフロントエンドの NoteList フィルタ・表示に現在も使用中。BugFix の `formulaOnly` フィルタ（廃止）とは別物のため削除しないこと。

---

## 7. 開発環境

### バックエンド

```bash
cd /Users/yamashita/Develop/exapp-backend
php artisan serve
```

### フロントエンド

```bash
cd /Users/yamashita/Develop/exapp-frontend
npm run dev
```

### OpenAPI 仕様書

`/Users/yamashita/Develop/exapp-backend/doc/openapi.yaml`
