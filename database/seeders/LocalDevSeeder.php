<?php

namespace Database\Seeders;

use App\Enums\FailureType;
use App\Enums\Proficiency;
use App\Enums\Rank;
use App\Enums\SprintStatus;
use App\Enums\SprintType;
use App\Enums\TicketPriority;
use App\Enums\TicketSource;
use App\Enums\TicketStatus;
use App\Enums\TicketType;
use App\Enums\TimeSlot;
use App\Models\DailyLog;
use App\Models\ExamQuestion;
use App\Models\ExamSession;
use App\Models\Material;
use App\Models\MonthlySetting;
use App\Models\Problem;
use App\Models\Sprint;
use App\Models\StudySession;
use App\Models\StudyTicket;
use App\Models\SubCategory;
use App\Models\Subject;
use App\Models\SubjectMonthlyGoal;
use App\Models\SubjectSetting;
use App\Models\Snippet;
use App\Models\TicketNote;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class LocalDevSeeder extends Seeder
{
    // 今日を固定して再現性を保つ
    private Carbon $today;
    private int $userId;

    public function run(): void
    {
        if (app()->environment('production')) {
            $this->command->error('本番環境では実行できません。');
            return;
        }

        $this->today = Carbon::create(2026, 5, 11);

        DB::transaction(function () {
            $this->seedUser();
            $this->seedMaterials();
            $this->seedSubCategories();
            $this->seedMonthlySettings();
            $this->seedSubjectSettings();
            $this->seedSubjectMonthlyGoals();
            $this->seedStudyHistory();
            $this->seedProblems();
            $this->seedExamSessions();
            $this->seedSprints();
            $this->seedSnippets();
        });

        $this->command->info('LocalDevSeeder 完了');
    }

    // ----------------------------------------------------------------
    // User / Profile
    // ----------------------------------------------------------------

    private function seedUser(): void
    {
        $user = User::firstOrCreate(
            ['email' => 'dev@example.com'],
            [
                'name' => '山田 太郎',
                'password' => Hash::make('password'),
            ],
        );

        $this->userId = $user->id;

        UserProfile::updateOrCreate(
            ['user_id' => $this->userId],
            [
                'nickname' => 'たろう',
                'occupation' => '中小企業診断士受験生（会社員）',
                'goal' => '2027年度の中小企業診断士一次試験合格。財務・会計と企業経営理論で高得点を狙いつつ、苦手の経済学を60点以上に引き上げる。',
                'weak_areas' => '経済学・経済政策（IS-LM分析、余剰分析）、経営情報システム（アルゴリズム系）',
                'strong_areas' => '財務・会計（財務諸表分析、CVP分析）',
                'interests' => 'マーケティング戦略、組織論',
            ],
        );

        // subjects は user_id 必須のためユーザー作成後に生成
        (new SubjectSeeder())->seedForUser($this->userId);
    }

    // ----------------------------------------------------------------
    // Materials
    // ----------------------------------------------------------------

    private function seedMaterials(): void
    {
        $materials = [
            ['name' => 'TAC過去問題集', 'display_order' => 1],
            ['name' => 'TACスピードテキスト', 'display_order' => 2],
            ['name' => '中小企業白書2025', 'display_order' => 3],
        ];

        foreach ($materials as $m) {
            Material::firstOrCreate(
                ['user_id' => $this->userId, 'name' => $m['name']],
                ['display_order' => $m['display_order']],
            );
        }
    }

    // ----------------------------------------------------------------
    // SubCategories (既存のSubCategorySeederと重複しないようにuser_id=dev user向けに追加)
    // ----------------------------------------------------------------

    private function seedSubCategories(): void
    {
        $subjectMap = Subject::pluck('id', 'name');

        $data = [
            '企業経営理論' => ['ドメイン・成長戦略', '経営資源戦略・VRIO', '組織構造・組織文化', '動機付け理論', '消費者行動論', 'マーケティング・ミックス'],
            '財務・会計' => ['財務諸表分析', 'CVP分析', '意思決定会計', '資本コスト・CAPM', '証券投資論'],
            '運営管理' => ['生産計画・工程管理', '在庫管理・JIT', '店舗施設・陳列', '物流・SCM'],
            '経済学・経済政策' => ['国民所得統計', 'IS-LM分析', 'AD-AS分析', '余剰分析', '市場の失敗'],
            '経営法務' => ['会社法', '知的財産権', '民法', '英文契約'],
            '経営情報システム' => ['データベース', 'ネットワーク', 'セキュリティ', 'アルゴリズム'],
            '中小企業経営・政策' => ['中小企業基本法', '中小企業白書', '中小企業施策'],
        ];

        foreach ($data as $subjectName => $names) {
            $subjectId = $subjectMap[$subjectName] ?? null;
            if (!$subjectId) continue;

            foreach ($names as $name) {
                SubCategory::firstOrCreate([
                    'user_id' => $this->userId,
                    'subject_id' => $subjectId,
                    'name' => $name,
                ]);
            }
        }
    }

    // ----------------------------------------------------------------
    // Monthly Settings（目標学習時間）
    // ----------------------------------------------------------------

    private function seedMonthlySettings(): void
    {
        // 3月: 始めたばかりで控えめ目標
        // 4月: 慣れてきて引き上げ
        // 5月: さらに引き上げ
        $settings = [
            ['year' => 2026, 'month' => 3, 'target_min' => 40.0, 'target_max' => 60.0],
            ['year' => 2026, 'month' => 4, 'target_min' => 60.0, 'target_max' => 80.0],
            ['year' => 2026, 'month' => 5, 'target_min' => 70.0, 'target_max' => 90.0],
        ];

        foreach ($settings as $s) {
            MonthlySetting::updateOrCreate(
                ['user_id' => $this->userId, 'year' => $s['year'], 'month' => $s['month']],
                ['target_min' => $s['target_min'], 'target_max' => $s['target_max']],
            );
        }
    }

    // ----------------------------------------------------------------
    // Subject Settings（科目最終目標）
    // ----------------------------------------------------------------

    private function seedSubjectSettings(): void
    {
        $subjectMap = Subject::pluck('id', 'name');

        $settings = [
            '財務・会計' => '80点以上を安定させ、得点源にする。連結会計・デリバティブを重点的に固める。',
            '企業経営理論' => '70点以上。マーケティングと組織論を落とさない。',
            '経済学・経済政策' => '60点確保。IS-LM・AD-AS分析を確実に得点できるようにする。',
            '運営管理' => '65点以上。生産管理の計算問題と店舗管理の暗記を両立する。',
            '経営法務' => '60点確保。会社法・知財を優先し、英文契約は深追いしない。',
            '経営情報システム' => '60点以上。過去問の出題パターンに慣れる。',
            '中小企業経営・政策' => '60点以上。白書データは直前期に集中して暗記。',
        ];

        foreach ($settings as $subjectName => $target) {
            $subjectId = $subjectMap[$subjectName] ?? null;
            if (!$subjectId) continue;

            SubjectSetting::updateOrCreate(
                ['user_id' => $this->userId, 'subject_id' => $subjectId],
                ['final_target' => $target],
            );
        }
    }

    // ----------------------------------------------------------------
    // Subject Monthly Goals
    // ----------------------------------------------------------------

    private function seedSubjectMonthlyGoals(): void
    {
        $subjectMap = Subject::pluck('id', 'name');

        $goals = [
            4 => [
                '財務・会計' => 'CVP分析と資本コストを完璧に。過去問10年分の財務を一周する。',
                '企業経営理論' => '組織論の動機付け理論を総復習。消費者行動論の頻出論点を整理する。',
                '経済学・経済政策' => 'IS-LM分析のグラフ操作を繰り返し練習する。',
            ],
            5 => [
                '財務・会計' => '連結会計・デリバティブの苦手箇所を集中的に潰す。模試を1回受ける。',
                '企業経営理論' => 'マーケティング・ミックスの4Pを実例とともに整理。直近3年の過去問を解く。',
                '経営法務' => '会社法の機関設計と知的財産権の存続期間を暗記カードで定着させる。',
                '経済学・経済政策' => '余剰分析（消費者余剰・生産者余剰）を計算で解けるようにする。',
            ],
        ];

        foreach ($goals as $month => $subjectGoals) {
            foreach ($subjectGoals as $subjectName => $goal) {
                $subjectId = $subjectMap[$subjectName] ?? null;
                if (!$subjectId) continue;

                SubjectMonthlyGoal::updateOrCreate(
                    ['user_id' => $this->userId, 'subject_id' => $subjectId, 'year' => 2026, 'month' => $month],
                    ['goal' => $goal],
                );
            }
        }
    }

    // ----------------------------------------------------------------
    // Study History（約2ヶ月分のデイリーログ＋学習セッション）
    // ----------------------------------------------------------------

    private function seedStudyHistory(): void
    {
        $subjectMap = Subject::pluck('id', 'name');
        $materialMap = Material::where('user_id', $this->userId)->pluck('id', 'name');
        $subCategoryMap = SubCategory::where('user_id', $this->userId)
            ->get(['id', 'name'])
            ->pluck('id', 'name');

        // 学習開始日: 3月10日
        $startDate = Carbon::create(2026, 3, 10);
        $endDate = $this->today->copy()->subDay(); // 前日まで

        // 科目ごとの重み（初月は財務・企業経営に集中、徐々に広げる）
        $current = $startDate->copy();

        while ($current->lte($endDate)) {
            $dayOfWeek = $current->dayOfWeek; // 0=Sun, 6=Sat
            $studyProb = $this->studyProbabilityForDay($current, $startDate);

            if (!$this->shouldStudy($studyProb)) {
                $current->addDay();
                continue;
            }

            $log = DailyLog::firstOrCreate(
                ['user_id' => $this->userId, 'date' => $current->toDateString()],
                [
                    'reflection' => $this->randomReflection($current),
                    'is_completed' => $current->lt($this->today->copy()->subDays(3)),
                ],
            );

            $sessions = $this->buildSessionsForDay($current, $startDate, $subjectMap, $subCategoryMap, $materialMap);

            foreach ($sessions as $s) {
                StudySession::firstOrCreate(
                    [
                        'daily_log_id' => $log->id,
                        'subject_id' => $s['subject_id'],
                        'time_slot' => $s['time_slot'],
                    ],
                    [
                        'sub_category_id' => $s['sub_category_id'],
                        'material_id' => $s['material_id'],
                        'minutes' => $s['minutes'],
                        'memo' => $s['memo'],
                    ],
                );
            }

            $current->addDay();
        }
    }

    private function studyProbabilityForDay(Carbon $date, Carbon $start): float
    {
        $daysIn = $start->diffInDays($date);
        // 最初の2週間: 週4-5日ペース（0.65）
        // 3-4週目: 少し落ちる（0.60）
        // 5週目以降: 安定（0.75）
        $base = match (true) {
            $daysIn < 14 => 0.65,
            $daysIn < 28 => 0.60,
            default => 0.75,
        };
        // 土日は少し下がる
        if (in_array($date->dayOfWeek, [0, 6])) {
            $base += 0.10; // 土日は少し上がる（まとまって勉強できる）
        }
        return $base;
    }

    private function shouldStudy(float $probability): bool
    {
        return (mt_rand(0, 99) / 100) < $probability;
    }

    private function buildSessionsForDay(
        Carbon $date,
        Carbon $start,
               $subjectMap,
               $subCategoryMap,
               $materialMap,
    ): array {
        $daysIn = $start->diffInDays($date);
        $isWeekend = in_array($date->dayOfWeek, [0, 6]);

        // 週末はより長く勉強
        $totalMinutes = $isWeekend
            ? $this->randMinutes(90, 180)
            : $this->randMinutes(45, 120);

        // 科目の優先度（学習が進むにつれ科目を広げる）
        $weights = $this->subjectWeights($daysIn);

        $sessions = [];
        $remaining = $totalMinutes;
        $timeSlots = $isWeekend
            ? [TimeSlot::Morning->value, TimeSlot::Night->value]
            : $this->dailyTimeSlots($date);

        $subjects = $this->pickSubjects($weights, min(count($timeSlots), mt_rand(1, 2)));

        foreach ($subjects as $i => $subjectName) {
            if ($remaining <= 0) break;
            $subjectId = $subjectMap[$subjectName] ?? null;
            if (!$subjectId) continue;

            $minutes = $i === array_key_last($subjects) ? $remaining : min($remaining, $this->randMinutes(30, 90));
            $remaining -= $minutes;

            $subCatName = $this->pickSubCategory($subjectName);
            $sessions[] = [
                'subject_id' => $subjectId,
                'sub_category_id' => $subCategoryMap[$subCatName] ?? null,
                'time_slot' => $timeSlots[$i] ?? TimeSlot::Night->value,
                'material_id' => $materialMap['TAC過去問題集'] ?? null,
                'minutes' => max(15, $minutes),
                'memo' => null,
            ];
        }

        return $sessions;
    }

    private function subjectWeights(int $daysIn): array
    {
        // 初期は財務・企業経営に集中
        if ($daysIn < 21) {
            return [
                '財務・会計' => 35,
                '企業経営理論' => 30,
                '経済学・経済政策' => 20,
                '運営管理' => 10,
                '経営法務' => 5,
            ];
        }
        // 中盤から全科目
        if ($daysIn < 42) {
            return [
                '財務・会計' => 25,
                '企業経営理論' => 22,
                '経済学・経済政策' => 18,
                '運営管理' => 15,
                '経営法務' => 10,
                '経営情報システム' => 7,
                '中小企業経営・政策' => 3,
            ];
        }
        // 後半は全科目バランス
        return [
            '財務・会計' => 22,
            '企業経営理論' => 20,
            '経済学・経済政策' => 16,
            '運営管理' => 14,
            '経営法務' => 12,
            '経営情報システム' => 10,
            '中小企業経営・政策' => 6,
        ];
    }

    private function pickSubjects(array $weights, int $count): array
    {
        $subjects = [];
        $pool = $weights;

        for ($i = 0; $i < $count; $i++) {
            if (empty($pool)) break;
            $total = array_sum($pool);
            $rand = mt_rand(1, $total);
            $cumul = 0;
            $picked = array_key_first($pool);

            foreach ($pool as $name => $weight) {
                $cumul += $weight;
                if ($rand <= $cumul) {
                    $picked = $name;
                    break;
                }
            }

            $subjects[] = $picked;
            unset($pool[$picked]);
        }

        return $subjects;
    }

    private function pickSubCategory(string $subjectName): string
    {
        $map = [
            '企業経営理論' => ['ドメイン・成長戦略', '経営資源戦略・VRIO', '組織構造・組織文化', '動機付け理論', '消費者行動論', 'マーケティング・ミックス'],
            '財務・会計' => ['財務諸表分析', 'CVP分析', '意思決定会計', '資本コスト・CAPM', '証券投資論'],
            '運営管理' => ['生産計画・工程管理', '在庫管理・JIT', '店舗施設・陳列', '物流・SCM'],
            '経済学・経済政策' => ['国民所得統計', 'IS-LM分析', 'AD-AS分析', '余剰分析', '市場の失敗'],
            '経営法務' => ['会社法', '知的財産権', '民法', '英文契約'],
            '経営情報システム' => ['データベース', 'ネットワーク', 'セキュリティ', 'アルゴリズム'],
            '中小企業経営・政策' => ['中小企業基本法', '中小企業白書', '中小企業施策'],
        ];

        $list = $map[$subjectName] ?? ['その他'];
        return $list[array_rand($list)];
    }

    private function dailyTimeSlots(Carbon $date): array
    {
        // 平日は通勤 or 夜、たまに昼
        $options = [
            [TimeSlot::Commute->value, TimeSlot::Night->value],
            [TimeSlot::Night->value],
            [TimeSlot::Lunch->value, TimeSlot::Night->value],
            [TimeSlot::Morning->value, TimeSlot::Night->value],
        ];
        return $options[array_rand($options)];
    }

    private function randomReflection(Carbon $date): ?string
    {
        if (mt_rand(0, 2) === 0) return null;

        $reflections = [
            'CVP分析の問題が解けるようになってきた。',
            'IS-LMのグラフがまだ混乱する。明日も復習する。',
            '財務諸表分析のROEの分解を完璧にした。',
            '会社法の機関設計、取締役会の要否が覚えられない。',
            '動機付け理論（ハーズバーグ、マズロー）の区別を整理できた。',
            '過去問をやると意外と解けた。自信になった。',
            '今日は集中できなかった。明日リカバリーする。',
            '余剰分析の計算がまだ怪しい。グラフを書いて確認すること。',
            '知的財産権の存続期間を整理した。特許20年、実用新案10年。',
            'CVPと損益分岐点の問題を5問連続で正解できた。',
            'マーケティング・ミックスの4Pを実例で説明できるようになった。',
        ];

        return $reflections[array_rand($reflections)];
    }

    private function randMinutes(int $min, int $max): int
    {
        // 15分単位に丸める
        $raw = mt_rand($min, $max);
        return (int)(round($raw / 15) * 15);
    }

    // ----------------------------------------------------------------
    // Problems（苦手問題）
    // ----------------------------------------------------------------

    private function seedProblems(): void
    {
        $subjectMap = Subject::pluck('id', 'name');
        $materialMap = Material::where('user_id', $this->userId)->pluck('id', 'name');
        $subCategoryMap = SubCategory::where('user_id', $this->userId)
            ->get(['id', 'name'])
            ->pluck('id', 'name');

        $materialId = $materialMap['TAC過去問題集'];

        $problems = $this->problemDefinitions();

        foreach ($problems as $p) {
            $subjectId = $subjectMap[$p['subject']] ?? null;
            if (!$subjectId) continue;

            $subCategoryId = null;
            if (isset($p['sub_category'])) {
                $subCategoryId = $subCategoryMap[$p['sub_category']] ?? null;
            }

            Problem::firstOrCreate(
                [
                    'user_id' => $this->userId,
                    'subject_id' => $subjectId,
                    'question_ref' => $p['question_ref'],
                    'solved_at' => $p['solved_at'],
                ],
                [
                    'sub_category_id' => $subCategoryId,
                    'material_id' => $materialId,
                    'note' => $p['note'],
                    'proficiency' => $p['proficiency'],
                    'failure_types' => $p['failure_types'],
                    'is_good_question' => $p['is_good_question'] ?? false,
                    'last_touched_at' => $p['last_touched_at'] ?? null,
                ],
            );
        }
    }

    private function problemDefinitions(): array
    {
        $yesterday = $this->today->copy()->subDay()->toDateString();   // 2026-05-10
        $twoDaysAgo = $this->today->copy()->subDays(2)->toDateString(); // 2026-05-09
        $fiveDaysAgo = $this->today->copy()->subDays(5)->toDateString();// 2026-05-06
        $tenDaysAgo = $this->today->copy()->subDays(10)->toDateString();

        return [
            // ──────────────────────────────────────────────────
            // 経営法務：定義（昨日タッチ済み → morning-bugfix Tier1 対象）
            // ──────────────────────────────────────────────────
            [
                'subject' => '経営法務',
                'sub_category' => '民法',
                'question_ref' => '2023年第15問',
                'note' => <<<'EOT'
                    民法上の契約を「成立要件」の観点から整理します。当事者の合意のみで成立する「諾成契約」と、合意に加えて物の引き渡しが必要な「要物契約」の区別、および2020年改正による変更点が論点です。

                    ---

                    ## 成立要件による契約の分類

                    ### 1. 諾成契約
                    当事者間の合意（申込み＋承諾）のみで成立する契約。売買・賃貸借・雇用など大多数の契約がこれに該当する。

                    ### 2. 要物契約
                    合意に加えて目的物の引き渡しが成立要件となる契約。旧民法では消費貸借・使用貸借がこれにあたった。

                    ### 3. 消費貸借の改正（重要）
                    旧民法：消費貸借は要物契約（金銭の引き渡しが必要）。改正民法（2020年施行）：**書面または電磁的記録**がある場合は引き渡し前でも諾成で成立するようになった。

                    ---

                    | 区分 | 成立要件 | 代表例 |
                    | --- | --- | --- |
                    | **諾成契約** | 合意のみ | 売買・賃貸借 |
                    | **要物契約（旧法）** | 合意＋引き渡し | 消費貸借 |
                    | **諾成的消費貸借（改正後）** | 合意＋書面 | ローン契約（書面あり） |

                    ---

                    #Definition 定義
                    諾成契約：合意のみで成立する契約。要物契約：合意に加えて物の引き渡しが必要な契約。書面による諾成的消費貸借：改正民法で新設、書面があれば引き渡し前でも成立する。

                    #Formula 公式
                    （なし）

                    #Keyword 重要語
                    諾成契約、要物契約、消費貸借、書面、改正民法（2020年施行）

                    #Pitfall 間違えやすい点
                    旧民法の「消費貸借＝要物契約」を丸暗記していると、改正後の「書面があれば諾成で成立」を見落とす。試験では改正後の取り扱いが問われる点に注意。

                    #Example 具体例
                    銀行ローン（金銭消費貸借）：旧法では実際の金銭交付が必要だったが、改正後は金銭消費貸借契約書（書面）があれば合意段階で成立する。

                    #Relation 他概念との関係
                    双務・片務（給付が双方向か）、有償・無償（対価の有無）は別軸の分類。消費貸借は原則として片務・無償。

                    #MemoryHook 覚え方
                    「諾＝OK（合意）だけでOK」「要物＝物が要る」。消費貸借は「書面があれば諾成、書面なければ要物（改正後）」。
                    EOT,
                'proficiency' => Proficiency::Incorrect->value,
                'failure_types' => [FailureType::MissingDefinition->value],
                'solved_at' => '2026-04-02',
                'last_touched_at' => $yesterday,
            ],
            [
                'subject' => '経営法務',
                'sub_category' => '知的財産権',
                'question_ref' => '2022年第8問',
                'note' => <<<'EOT'
                    知的財産権の存続期間を整理します。特許権は「出願日から20年」という起算点が頻出の誤解ポイントです。

                    ---

                    ## 主要な知的財産権の存続期間

                    ### 1. 特許権
                    出願日から**20年**。登録日からではなく出願日から起算することに注意。出願から登録まで数年かかる場合、実質的な保護期間はその分短くなる。

                    ### 2. 実用新案権
                    出願日から**10年**。特許より保護期間が短く、審査も簡易。

                    ### 3. 商標権
                    登録日から**10年**（更新可能・半永久的に維持可）。商標のみ更新制度がある。

                    ### 4. 著作権
                    創作時から**死後70年**（法人の著作物は公表後70年）。登録不要で創作と同時に発生。

                    ---

                    | 権利 | 起算点 | 存続期間 | 更新 |
                    | --- | --- | --- | --- |
                    | **特許権** | 出願日 | 20年 | 不可 |
                    | **実用新案権** | 出願日 | 10年 | 不可 |
                    | **商標権** | 登録日 | 10年 | 可（無制限） |
                    | **著作権** | 創作時 | 死後70年 | 不要 |

                    ---

                    #Definition 定義
                    特許権：新規性・進歩性を要件とする発明の保護権利。実用新案権：物品の形状・構造に関する考案の保護権利。商標権：商品・サービスに使用するマークの保護権利。

                    #Formula 公式
                    起算点の違い：特許・実用新案＝出願日、商標＝登録日、著作権＝創作時（死後70年）

                    #Keyword 重要語
                    特許権、実用新案権、商標権、著作権、出願日、登録日、存続期間

                    #Pitfall 間違えやすい点
                    特許権の存続期間を「登録日から20年」と覚えてしまいやすいが、正しくは「出願日から20年」。実用新案を「登録日から10年」と誤る場合も多い。

                    #Example 具体例
                    2010年に出願された特許：審査を経て2013年に登録された場合でも、存続期間は2030年（出願から20年）まで。登録日の2013年から20年の2033年ではない。

                    #Relation 他概念との関係
                    意匠権（登録日から25年）、不正競争防止法（存続期間なし・行為規制）とも合わせて整理するとよい。

                    #MemoryHook 覚え方
                    「特許20年は出願から」「実用新案は特許の半分の10年」「商標は更新できる唯一の権利」。
                    EOT,
                'proficiency' => Proficiency::Partial->value,
                'failure_types' => [FailureType::MissingDefinition->value],
                'solved_at' => '2026-04-05',
                'last_touched_at' => $yesterday,
            ],
            [
                'subject' => '経営法務',
                'sub_category' => '会社法',
                'question_ref' => '2023年第3問',
                'note' => <<<'EOT'
                    取締役会設置会社における監査役の要否と、監査役会の構成要件を整理します。公開会社・非公開会社の区別が判断の起点です。

                    ---

                    ## 会社の種類と機関設計

                    ### 1. 公開会社（株式譲渡が自由な会社）
                    取締役会の設置が**必須**。取締役会設置会社では監査役（または監査等委員会・指名委員会等）が必要。

                    ### 2. 非公開会社（株式譲渡制限会社）
                    取締役会・監査役ともに**任意**。小規模会社では設置しないケースが多い。

                    ### 3. 監査役会の構成要件
                    監査役会を設置する場合：①監査役3名以上、②うち**社外監査役が半数以上**、③うち1名以上が常勤監査役。

                    ---

                    | 会社の種類 | 取締役会 | 監査役 | 監査役会（大会社） |
                    | --- | --- | --- | --- |
                    | **公開会社** | 必須 | 原則必須 | 必須 |
                    | **非公開会社** | 任意 | 任意 | 任意 |

                    ---

                    #Definition 定義
                    公開会社：株式の全部または一部について譲渡制限のない会社。監査役会：3名以上の監査役で構成される合議体（うち半数以上が社外監査役）。

                    #Formula 公式
                    監査役会の構成：監査役3名以上、社外監査役≧1/2、常勤監査役1名以上

                    #Keyword 重要語
                    公開会社、非公開会社、取締役会、監査役、監査役会、社外監査役、常勤監査役

                    #Pitfall 間違えやすい点
                    「取締役会設置会社では必ず監査役が必要」と思うと、監査等委員会設置会社・指名委員会等設置会社では監査役を**置けない**という例外を見落とす。また非公開会社では任意である点も混同しやすい。

                    #Example 具体例
                    上場会社（公開会社＋大会社）：取締役会・監査役会（または監査等委員会）が義務付けられる。中小の非公開会社：取締役1名のみで監査役なしも適法。

                    #Relation 他概念との関係
                    会計監査人（大会社に義務付け）、監査等委員会設置会社・指名委員会等設置会社（監査役を置かない別形態）と合わせて整理する。

                    #MemoryHook 覚え方
                    「公開会社は取締役会必須・監査役必須（例外あり）」「監査役会は3人以上で半分は社外」。
                    EOT,
                'proficiency' => Proficiency::Incorrect->value,
                'failure_types' => [FailureType::MissingDefinition->value],
                'solved_at' => '2026-04-10',
                'last_touched_at' => $yesterday,
            ],

            // ──────────────────────────────────────────────────
            // 企業経営理論：定義（直近7日 → Tier2 対象）
            // ──────────────────────────────────────────────────
            [
                'subject' => '企業経営理論',
                'sub_category' => '動機付け理論',
                'question_ref' => '2022年第18問',
                'note' => <<<'EOT'
                    ハーズバーグの二要因理論における「衛生要因」と「動機付け要因」の区別を整理します。満足と不満足は別次元の問題だという点が従来の理論との大きな違いです。

                    ---

                    ## 二要因理論の構造

                    ### 1. 衛生要因（Hygiene Factors）
                    不満を**防ぐ**ことはできるが、満足や動機付けをもたらさない要因。給与・労働条件・会社の方針・対人関係など職場環境に関するもの。

                    ### 2. 動機付け要因（Motivators）
                    真の満足と動機付けをもたらす要因。達成感・承認・仕事そのものの面白さ・責任・成長など仕事内容に関するもの。

                    ### 3. 伝統的理論との違い
                    従来の理論は「不満がなければ満足」と考えたが、ハーズバーグは「満足と不満は別次元の問題」と主張した。

                    ---

                    | 要因 | 効果 | 代表例 |
                    | --- | --- | --- |
                    | **衛生要因** | 不満を防ぐ（満足はもたらさない） | 給与・労働条件・人間関係 |
                    | **動機付け要因** | 満足・動機付けをもたらす | 達成・承認・成長・責任 |

                    ---

                    #Definition 定義
                    衛生要因：不満の解消には寄与するが動機付けにはならない環境的要因。動機付け要因：真の満足と内発的動機付けをもたらす仕事内容に関する要因。

                    #Formula 公式
                    （なし）

                    #Keyword 重要語
                    衛生要因、動機付け要因、内発的動機付け、職務充実（ジョブ・エンリッチメント）

                    #Pitfall 間違えやすい点
                    「給与を上げれば動機付けられる」は誤り。給与は衛生要因であり、不満の解消にはなるが積極的な動機付けにはならない。衛生要因を「動機付け要因」と混同する選択肢が頻出。

                    #Example 具体例
                    衛生要因：労働時間の改善→不満は解消されるが、仕事への情熱は生まれない。動機付け要因：プロジェクトリーダーに任命→達成感・責任感から仕事への意欲が高まる。

                    #Relation 他概念との関係
                    マズローの欲求段階説との対応：衛生要因≈下位欲求（生理・安全・社会的欲求）、動機付け要因≈上位欲求（尊厳・自己実現欲求）。マクレガーのX理論・Y理論とも関連する。

                    #MemoryHook 覚え方
                    「衛生＝清潔にするだけ（不満防止止まり）」「動機付け＝やる気を生む」。給与はトイレ（衛生）、やりがいはご馳走（動機付け）。
                    EOT,
                'proficiency' => Proficiency::Partial->value,
                'failure_types' => [FailureType::MissingDefinition->value],
                'solved_at' => '2026-04-18',
                'last_touched_at' => $fiveDaysAgo,
            ],
            [
                'subject' => '企業経営理論',
                'sub_category' => '消費者行動論',
                'question_ref' => '2021年第22問',
                'note' => <<<'EOT'
                    消費者の購買意思決定における「関与度」と「精緻化見込みモデル（ELM）」の関係を整理します。高関与・低関与によって説得経路が変わる点が核心です。

                    ---

                    ## 関与度と情報処理ルート

                    ### 1. 関与度（インボルブメント）
                    消費者が購買に費やす情報処理の深さ・関心の度合い。高関与製品（車・住宅・保険）と低関与製品（日用品・食料品）で購買行動が大きく異なる。

                    ### 2. 精緻化見込みモデル（ELM）
                    態度変容の2つのルートを説明するモデル（ペティとカシオッポ提唱）。
                    - **中心ルート（Central Route）**：高関与時。製品の特性・品質・論理的情報を深く処理して態度を形成。
                    - **周辺ルート（Peripheral Route）**：低関与時。有名人起用・デザイン・雰囲気など周辺的手がかりで態度を形成。

                    ### 3. 広告戦略への応用
                    高関与製品には詳細なスペック訴求（中心ルート）。低関与製品にはイメージ広告・感情訴求（周辺ルート）が効果的。

                    ---

                    | 関与度 | 情報処理ルート | 態度形成の基準 | マーケティング例 |
                    | --- | --- | --- | --- |
                    | **高関与** | 中心ルート | 論理・品質・スペック | 詳細比較サイト・スペック訴求広告 |
                    | **低関与** | 周辺ルート | 感情・雰囲気・タレント | イメージ広告・パッケージデザイン |

                    ---

                    #Definition 定義
                    関与度：購買意思決定に費やす情報処理の深さ・関心の強さ。ELM（精緻化見込みモデル）：関与度によって説得経路（中心ルート・周辺ルート）が変わるモデル。

                    #Formula 公式
                    （なし）

                    #Keyword 重要語
                    関与度、ELM、中心ルート、周辺ルート、精緻化、態度変容

                    #Pitfall 間違えやすい点
                    「高関与＝中心ルート」「低関与＝周辺ルート」の対応を逆に覚えやすい。中心＝深く考える（関与が高い）、周辺＝なんとなく（関与が低い）と紐付ける。

                    #Example 具体例
                    高関与：マイホーム購入→複数社を比較し、金利・設備・立地を詳細に検討（中心ルート）。低関与：シャンプー購入→好きなタレントが使っているから選ぶ（周辺ルート）。

                    #Relation 他概念との関係
                    ハワード＝シェス・モデル（購買意思決定プロセス）、認知的不協和理論（購買後の態度変化）とも関連する。

                    #MemoryHook 覚え方
                    「関与高い＝中心で深く考える」「関与低い＝周辺のオマケで決める」。ELMの中心＝理性、周辺＝感性。
                    EOT,
                'proficiency' => Proficiency::Incorrect->value,
                'failure_types' => [FailureType::MissingDefinition->value],
                'solved_at' => '2026-04-20',
                'last_touched_at' => $twoDaysAgo,
            ],

            // ──────────────────────────────────────────────────
            // 経済学：定義（全期間 Tier3 対象）
            // ──────────────────────────────────────────────────
            [
                'subject' => '経済学・経済政策',
                'sub_category' => 'IS-LM分析',
                'question_ref' => '2023年第4問',
                'note' => <<<'EOT'
                    IS-LM分析は財市場と貨幣市場の同時均衡を表すフレームワークです。財政政策・金融政策がどの曲線をシフトさせるかが最重要論点です。

                    ---

                    ## IS曲線とLM曲線の基本

                    ### 1. IS曲線（財市場の均衡）
                    利子率↑→投資↓→国民所得↓という関係から**右下がり**。財政拡大（政府支出↑・減税）でIS曲線が右シフトし、国民所得が増加する。

                    ### 2. LM曲線（貨幣市場の均衡）
                    国民所得↑→貨幣需要↑→利子率↑という関係から**右上がり**。金融緩和（マネーサプライ↑）でLM曲線が右シフトし、利子率が低下する。

                    ### 3. 政策効果のまとめ
                    - 財政政策：IS曲線を右シフト（国民所得↑・利子率↑）
                    - 金融政策：LM曲線を右シフト（国民所得↑・利子率↓）

                    ---

                    | 政策 | シフトする曲線 | 国民所得 | 利子率 |
                    | --- | --- | --- | --- |
                    | **財政拡大** | IS右シフト | 増加 | 上昇 |
                    | **金融緩和** | LM右シフト | 増加 | 低下 |
                    | **財政緊縮** | IS左シフト | 減少 | 低下 |
                    | **金融引き締め** | LM左シフト | 減少 | 上昇 |

                    ---

                    #Definition 定義
                    IS曲線：財市場の均衡を表す右下がりの曲線（I＝S）。LM曲線：貨幣市場の均衡を表す右上がりの曲線（L＝M）。

                    #Formula 公式
                    IS曲線：Y = C(Y-T) + I(r) + G（財市場の均衡条件）
                    LM曲線：M/P = L(Y, r)（貨幣市場の均衡条件）

                    #Keyword 重要語
                    IS曲線、LM曲線、財政政策、金融政策、クラウディングアウト、流動性のわな

                    #Pitfall 間違えやすい点
                    「財政政策はLM曲線をシフトさせる」と誤解しやすい。財政政策（IS）と金融政策（LM）の対応を確実に覚える。財政政策では利子率が上昇してクラウディングアウトが起きる点も混同しやすい。

                    #Example 具体例
                    財政拡大（公共投資↑）→IS右シフト→国民所得↑・利子率↑→民間投資が抑制される（クラウディングアウト効果）。

                    #Relation 他概念との関係
                    AD-AS分析（総需要・総供給）はIS-LM分析を物価変動まで拡張したもの。マンデル＝フレミングモデルは開放経済版のIS-LM。

                    #MemoryHook 覚え方
                    「IS＝財市場（Investment-Saving）・右下がり」「LM＝貨幣市場（Liquidity-Money）・右上がり」。「財政はIS、金融はLM」でシフトの対応を覚える。
                    EOT,
                'proficiency' => Proficiency::Incorrect->value,
                'failure_types' => [FailureType::MissingDefinition->value, FailureType::WrongApproach->value],
                'solved_at' => '2026-03-20',
                'last_touched_at' => $tenDaysAgo,
            ],
            [
                'subject' => '経済学・経済政策',
                'sub_category' => '余剰分析',
                'question_ref' => '2022年第6問',
                'note' => <<<'EOT'
                    余剰分析は消費者余剰・生産者余剰・総余剰の概念を使って、市場の効率性や課税の影響を分析するフレームワークです。グラフ上の「面積」として捉えることが重要です。

                    ---

                    ## 余剰の定義と課税の影響

                    ### 1. 消費者余剰
                    消費者が支払ってもよいと考える最大金額（需要曲線）と実際の市場価格との差。需要曲線と価格水準の間の上方の面積で表される。

                    ### 2. 生産者余剰
                    生産者が受け取る価格と供給コスト（供給曲線）との差。価格水準と供給曲線の間の下方の面積で表される。

                    ### 3. 課税の効果
                    課税→供給曲線が上シフト→均衡価格↑・均衡数量↓→消費者余剰↓・生産者余剰↓・税収↑。しかし取引量の減少分が**死荷重（厚生損失）**として消滅し、総余剰が必ず減少する。

                    ---

                    | 項目 | 変化 | 理由 |
                    | --- | --- | --- |
                    | **消費者余剰** | 減少 | 価格上昇により享受できる余剰が縮小 |
                    | **生産者余剰** | 減少 | 税負担分が吸収される |
                    | **政府税収** | 増加 | 課税額×取引量 |
                    | **死荷重** | 発生 | 取引量減少による純損失 |
                    | **総余剰** | 減少 | 死荷重の分だけ純損失 |

                    ---

                    #Definition 定義
                    消費者余剰：需要曲線と価格の差（支払い意思額と実際支払い額の差）。生産者余剰：価格と供給曲線の差（受取額と最低受入可能額の差）。死荷重（厚生損失）：課税等により取引量が減少することで生じる取り戻せない社会的損失。

                    #Formula 公式
                    死荷重 = (課税後価格 - 課税前価格) × (課税前数量 - 課税後数量) ÷ 2

                    #Keyword 重要語
                    消費者余剰、生産者余剰、総余剰、死荷重（厚生損失）、課税、パレート効率性

                    #Pitfall 間違えやすい点
                    課税すると「政府が税収を得るので総余剰は変わらない」と思いがちだが、死荷重の分だけ総余剰は**必ず減少**する。また消費者余剰（上の三角）と生産者余剰（下の三角）の位置を取り違えやすい。

                    #Example 具体例
                    たばこ税：課税→たばこ価格↑→購入量↓→消費者余剰↓・生産者余剰↓・税収↑。取引量の減少分が死荷重として消滅する。

                    #Relation 他概念との関係
                    独占市場の分析（独占による死荷重）、外部性・市場の失敗との関係、ラムゼイ価格付け（死荷重最小化）とも関連する。

                    #MemoryHook 覚え方
                    「グラフを書いて面積を塗る」→上の三角が消費者余剰、下の三角が生産者余剰。「課税＝必ず死荷重が生まれる」を公理として覚える。
                    EOT,
                'proficiency' => Proficiency::Incorrect->value,
                'failure_types' => [FailureType::MissingDefinition->value],
                'solved_at' => '2026-03-22',
                'last_touched_at' => null,
            ],
            [
                'subject' => '経済学・経済政策',
                'sub_category' => 'AD-AS分析',
                'question_ref' => '2021年第5問',
                'note' => <<<'EOT'
                    AD-AS分析は、総需要（AD）曲線と総供給（AS）曲線を使って物価水準と実質GDPの均衡を説明するマクロ経済モデルです。IS-LM分析を物価変動まで拡張したものです。

                    ---

                    ## AD曲線とAS曲線の基本

                    ### 1. AD曲線（総需要曲線）
                    **右下がり**。物価↑→実質残高↓→利子率↑→投資↓→総需要↓という経路（ケインズ効果）。財政拡大・金融緩和でAD曲線が右シフト。

                    ### 2. AS曲線（総供給曲線）
                    **短期：右上がり**。物価↑→実質賃金↓→雇用↑→産出↑（名目賃金の硬直性を前提）。**長期：垂直**（潜在GDPの水準で固定）。

                    ### 3. ショックの分析
                    - 需要ショック（AD右シフト）：物価↑・実質GDP↑（短期）
                    - 供給ショック（AS左シフト）：物価↑・実質GDP↓（スタグフレーション）

                    ---

                    | ショック | シフト | 物価 | 実質GDP |
                    | --- | --- | --- | --- |
                    | **財政拡大** | AD右 | 上昇 | 増加 |
                    | **金融緩和** | AD右 | 上昇 | 増加 |
                    | **石油価格↑（供給ショック）** | AS左 | 上昇 | 減少 |

                    ---

                    #Definition 定義
                    AD曲線：物価と実質総需要の逆相関を示す右下がりの曲線。AS曲線：物価と実質総供給の関係を示す曲線（短期は右上がり、長期は垂直）。スタグフレーション：物価上昇と景気後退が同時に起きる現象。

                    #Formula 公式
                    AD曲線の傾き：物価↑→実質マネーサプライ(M/P)↓→LM曲線左シフト→利子率↑→投資↓→AD↓
                    AS曲線の傾き（短期）：物価↑→実質賃金(W/P)↓→雇用↑→産出↑

                    #Keyword 重要語
                    AD曲線、AS曲線、需要ショック、供給ショック、スタグフレーション、長期AS（垂直）

                    #Pitfall 間違えやすい点
                    AD曲線を「右上がり」と描いてしまう（個別の需要曲線と混同）。AD曲線は「物価と総需要の関係」で**右下がり**。また「長期ASが垂直」という点を忘れやすい。

                    #Example 具体例
                    1970年代のオイルショック：石油価格急騰→AS曲線が左シフト→物価↑かつGDP↓（スタグフレーション）。各国がインフレと不況の同時進行に苦しんだ。

                    #Relation 他概念との関係
                    IS-LM分析はAD曲線の背後にある財・貨幣市場の均衡を説明するもの。フィリップス曲線（インフレ率と失業率の関係）ともリンクする。

                    #MemoryHook 覚え方
                    「AD＝右下がり（物価高いと需要減）」「短期AS＝右上がり（物価高いと供給増）」「長期AS＝垂直（長期は潜在GDPに収束）」。
                    EOT,
                'proficiency' => Proficiency::Partial->value,
                'failure_types' => [FailureType::MissingDefinition->value],
                'solved_at' => '2026-03-25',
                'last_touched_at' => null,
            ],

            // ──────────────────────────────────────────────────
            // 財務・会計：ケアレス・解法が中心
            // ──────────────────────────────────────────────────
            [
                'subject' => '財務・会計',
                'sub_category' => '資本コスト・CAPM',
                'question_ref' => '2023年第12問',
                'note' => <<<'EOT'
                    CAPM（資本資産価格モデル）は、リスクに見合った期待収益率を算出するフレームワークです。β値の意味とマーケットリスクプレミアムの定義が頻出論点です。

                    ---

                    ## CAPMの構造

                    ### 1. 基本公式
                    期待収益率 E(r) = Rf + β × (Rm - Rf)
                    - Rf：無リスク利子率（国債利回りなど）
                    - Rm：市場全体の期待収益率
                    - Rm - Rf：マーケットリスクプレミアム（市場超過収益率）
                    - β：システマティックリスクの大きさ（市場全体に対する感応度）

                    ### 2. β値の解釈
                    - β = 1：市場と同じリスク・リターン
                    - β > 1：市場より変動が大きい（ハイリスク・ハイリターン）
                    - β < 1：市場より変動が小さい（ローリスク・ローリターン）

                    ### 3. 計算の注意点
                    Rm - Rf を「マーケットリスクプレミアム」と呼ぶ。RmそのものではなくRm - Rfにβを掛けることを忘れない。

                    ---

                    #Definition 定義
                    CAPM（Capital Asset Pricing Model）：システマティックリスク（β）に基づいて資産の期待収益率を算出するモデル。マーケットリスクプレミアム：市場全体の期待収益率から無リスク利子率を差し引いた超過収益率（Rm - Rf）。

                    #Formula 公式
                    E(r) = Rf + β(Rm - Rf)
                    計算例：β=1.2, Rf=2%, Rm=8% → E(r) = 2 + 1.2 × (8-2) = 2 + 7.2 = 9.2%

                    #Keyword 重要語
                    CAPM、期待収益率、無リスク利子率（Rf）、市場期待収益率（Rm）、マーケットリスクプレミアム、β（ベータ）

                    #Pitfall 間違えやすい点
                    「β × Rm」と計算してしまうミスが頻出。正しくは「β × (Rm - Rf)」。Rm - Rf がマーケットリスクプレミアムであることを意識する。また Rf を最後に足し忘れるケースも多い。

                    #Example 具体例
                    β=0.8, Rf=1%, Rm=6%の場合：E(r) = 1 + 0.8 × (6-1) = 1 + 4 = 5%。β<1なので市場平均より低い期待収益率になる。

                    #Relation 他概念との関係
                    WACC（加重平均資本コスト）の計算でCAPMにより求めた株主資本コストを使用する。証券市場線（SML）はCAPMを視覚化したもの。

                    #MemoryHook 覚え方
                    「Rfに β×(Rm-Rf) を足す」。「Rm-Rfはプレミアム（おまけ）、βはおまけの倍率」。計算前に必ずRm-Rfを先に計算してからβを掛ける手順を癖にする。
                    EOT,
                'proficiency' => Proficiency::Partial->value,
                'failure_types' => [FailureType::CalculationError->value],
                'solved_at' => '2026-03-28',
                'last_touched_at' => $twoDaysAgo,
                'is_good_question' => true,
            ],
            [
                'subject' => '財務・会計',
                'sub_category' => 'CVP分析',
                'question_ref' => '2022年第11問',
                'note' => <<<'EOT'
                    CVP分析（Cost-Volume-Profit Analysis）は、費用・販売量・利益の関係を分析するフレームワークです。損益分岐点売上高の計算が頻出です。

                    ---

                    ## CVP分析の基本構造

                    ### 1. 限界利益と限界利益率
                    限界利益 = 売上高 - 変動費
                    限界利益率 = 限界利益 ÷ 売上高 = 1 - 変動費率

                    ### 2. 損益分岐点売上高（BEP）
                    損益分岐点売上高 = 固定費 ÷ 限界利益率
                    利益がちょうど0になる売上高。これを超えると利益が出る。

                    ### 3. 目標利益達成売上高
                    目標利益達成売上高 = (固定費 + 目標利益) ÷ 限界利益率

                    ---

                    | 指標 | 計算式 |
                    | --- | --- |
                    | **限界利益** | 売上高 - 変動費 |
                    | **限界利益率** | 限界利益 ÷ 売上高 |
                    | **損益分岐点売上高** | 固定費 ÷ 限界利益率 |
                    | **目標利益達成売上高** | (固定費 + 目標利益) ÷ 限界利益率 |

                    ---

                    #Definition 定義
                    限界利益：売上高から変動費だけを差し引いた利益（固定費の回収に充てる部分）。損益分岐点（BEP）：利益がゼロになる売上高・販売量。変動費率：売上高に占める変動費の割合。

                    #Formula 公式
                    損益分岐点売上高 = 固定費 ÷ 限界利益率
                    限界利益率 = (売上高 - 変動費) ÷ 売上高 = 1 - 変動費率

                    #Keyword 重要語
                    限界利益、限界利益率、変動費率、固定費、損益分岐点（BEP）、安全余裕率

                    #Pitfall 間違えやすい点
                    「損益分岐点 = 固定費 ÷ 変動費率」という誤りが多い。正しくは**限界利益率**で割る。変動費率と限界利益率は足すと1になる（1 - 変動費率 = 限界利益率）を確認してから計算する。

                    #Example 具体例
                    固定費100万円、変動費率60%の場合：限界利益率=40%、損益分岐点売上高=100÷0.4=250万円。売上が250万円を超えた分だけ利益が出る。

                    #Relation 他概念との関係
                    意思決定会計（差額原価収益分析）でも限界利益の概念を使用。製品ミックス決定（制約条件がある場合の最適生産量）にも応用される。

                    #MemoryHook 覚え方
                    「BEP＝固定費÷限界利益率」を呪文にする。「変動費率+限界利益率=1」を先に確認してから計算する癖をつける。
                    EOT,
                'proficiency' => Proficiency::Partial->value,
                'failure_types' => [FailureType::CalculationError->value],
                'solved_at' => '2026-04-01',
                'last_touched_at' => $tenDaysAgo,
            ],
            [
                'subject' => '財務・会計',
                'sub_category' => '証券投資論',
                'question_ref' => '2023年第14問',
                'note' => <<<'EOT'
                    ポートフォリオのリスク（標準偏差）は、個別資産のリスクの加重平均にはならず、相関係数によって分散効果が生じます。この点が頻出の誤解ポイントです。

                    ---

                    ## ポートフォリオのリスクと分散投資

                    ### 1. リスクの加重平均にならない理由
                    2資産のポートフォリオの分散（σ²）には相関係数（ρ）が入るため、単純な加重平均より一般的に小さくなる。

                    ### 2. 相関係数の影響
                    - ρ = +1（完全正相関）：リスクは加重平均と一致。分散効果なし。
                    - ρ = 0（無相関）：リスクが低減される。
                    - ρ = -1（完全負相関）：リスクを最大限に低減（理論上ゼロにも可能）。

                    ### 3. 分散効果
                    相関が低い資産を組み合わせるほど、同じ期待収益率でリスクが小さいポートフォリオが実現できる（効率的フロンティア）。

                    ---

                    | 相関係数 | リスクの変化 | 分散効果 |
                    | --- | --- | --- |
                    | **ρ = +1** | 加重平均と同じ | なし |
                    | **0 < ρ < 1** | 加重平均より小さい | あり |
                    | **ρ = 0** | さらに低減 | 大きい |
                    | **ρ = -1** | 最大限低減 | 最大 |

                    ---

                    #Definition 定義
                    ポートフォリオのリスク（標準偏差）：複数資産を組み合わせた際のリターンのばらつき。相関係数（ρ）：2資産の収益率の連動性を-1〜+1で表す指標。分散効果：相関が低い資産を組み合わせることでリスクを低減できる効果。

                    #Formula 公式
                    2資産ポートフォリオの分散：σ²ₚ = w₁²σ₁² + w₂²σ₂² + 2w₁w₂ρσ₁σ₂
                    （w：投資比率、σ：各資産の標準偏差、ρ：相関係数）

                    #Keyword 重要語
                    相関係数、分散効果、効率的フロンティア、システマティックリスク、アンシステマティックリスク

                    #Pitfall 間違えやすい点
                    「ポートフォリオの標準偏差は個別資産の標準偏差の加重平均」と思いがち。完全正相関（ρ=+1）のときだけ成立し、それ以外では加重平均よりも小さくなる。

                    #Example 具体例
                    株式A（σ=20%）と株式B（σ=20%）を50%ずつ保有。ρ=+1なら合成σ=20%（変わらず）。ρ=0なら合成σ≈14.1%に低減（約30%リスク削減）。

                    #Relation 他概念との関係
                    CAPM（β＝システマティックリスクのみ評価）、効率的市場仮説、マーコウィッツの平均分散アプローチと密接に関連する。

                    #MemoryHook 覚え方
                    「相関+1のときだけ加重平均」「それ以外は必ず小さくなる（分散効果）」。「完全正相関以外はオマケが出る」と覚える。
                    EOT,
                'proficiency' => Proficiency::Incorrect->value,
                'failure_types' => [FailureType::MissingDefinition->value, FailureType::CalculationError->value],
                'solved_at' => '2026-04-08',
                'last_touched_at' => $fiveDaysAgo,
                'is_good_question' => true,
            ],
            [
                'subject' => '財務・会計',
                'sub_category' => '意思決定会計',
                'question_ref' => '2021年第10問',
                'note' => <<<'EOT'
                    意思決定会計における「差額原価収益分析」は、複数の選択肢を比較する際に「意思決定に関連する原価・収益だけを比較する」フレームワークです。

                    ---

                    ## 差額原価収益分析の考え方

                    ### 1. 関連原価と埋没原価（サンクコスト）
                    - **関連原価**：意思決定によって変化する原価。比較する意味がある。
                    - **埋没原価（サンクコスト）**：既に発生して回収できない原価。意思決定に関係しない。

                    ### 2. 増分（差額）分析の手順
                    ①選択肢AとBで異なる収益・費用だけを列挙する。②埋没原価を除外する。③差額利益（増分利益）が大きい選択肢を選ぶ。

                    ### 3. 機会原価の考慮
                    ある選択肢を選ぶことで放棄した最大の利益（機会原価）も関連原価として含める。

                    ---

                    | 原価の種類 | 意思決定との関係 | 分析への含め方 |
                    | --- | --- | --- |
                    | **関連原価** | 選択肢によって変化する | 含める |
                    | **埋没原価** | 既発生・変化しない | 除外する |
                    | **機会原価** | 放棄した代替案の利益 | 含める |

                    ---

                    #Definition 定義
                    差額原価収益分析：複数の代替案を比較する際に、選択肢によって異なる原価・収益（差額・増分）のみを比較して意思決定を行う手法。埋没原価：過去に発生し現在の意思決定では回収不能な原価。

                    #Formula 公式
                    差額利益 = (選択肢Aの関連収益 - 選択肢Bの関連収益) - (選択肢Aの関連原価 - 選択肢Bの関連原価)

                    #Keyword 重要語
                    差額原価収益分析、埋没原価（サンクコスト）、関連原価、機会原価、増分分析

                    #Pitfall 間違えやすい点
                    埋没原価（すでに支払った費用）を「もったいない」と感じて意思決定に含めてしまう。コンコルドの誤謬とも言われる典型的な誤り。意思決定では過去の支出ではなく将来の差額だけを比較する。

                    #Example 具体例
                    3年前に100万円で購入した機械を今売るかどうか迷う場合：購入価格100万円は埋没原価。意思決定では「今売った場合の収入」と「使い続けた場合の将来利益の差額」のみを比較する。

                    #Relation 他概念との関係
                    CVP分析の限界利益概念と関連する。戦略的意思決定（設備投資・撤退判断）での正味現在価値（NPV）法とも連動する。

                    #MemoryHook 覚え方
                    「埋没原価は意思決定に埋もれさせる（無視する）」「過去は変えられない→将来の差額だけ見る」。
                    EOT,
                'proficiency' => Proficiency::Partial->value,
                'failure_types' => [FailureType::WrongApproach->value],
                'solved_at' => '2026-04-12',
                'last_touched_at' => $yesterday,
            ],

            // ──────────────────────────────────────────────────
            // 運営管理：解法中心
            // ──────────────────────────────────────────────────
            [
                'subject' => '運営管理',
                'sub_category' => '在庫管理・JIT',
                'question_ref' => '2023年第20問',
                'note' => <<<'EOT'
                    EOQ（Economic Order Quantity：経済的発注量）は、在庫管理において発注費用と保管費用の合計を最小にする最適発注量を求める公式です。√（ルート）を忘れるミスが最も多い。

                    ---

                    ## EOQの考え方

                    ### 1. 2種類のコストのトレードオフ
                    - **発注費用**：1回の発注にかかるコスト×発注回数。発注量が多いほど回数が減り総額は下がる。
                    - **保管費用**：在庫を保有することにかかるコスト。発注量が多いほど平均在庫が増え総額は上がる。

                    ### 2. EOQ公式
                    EOQ = √(2DS/H)
                    - D：年間需要量
                    - S：1回あたり発注費用
                    - H：1単位あたり年間保管費用

                    ### 3. 最適化の原理
                    EOQ公式は「発注費用の総額＝保管費用の総額」となる点で最適化される（両者が等しくなるときに合計費用が最小）。

                    ---

                    #Definition 定義
                    EOQ（経済的発注量）：在庫の発注費用と保管費用の合計を最小にする1回あたりの発注量。

                    #Formula 公式
                    EOQ = √(2DS/H)
                    年間総在庫費用 = (D/Q)×S + (Q/2)×H（Q：発注量、第1項＝発注費用、第2項＝保管費用）

                    #Keyword 重要語
                    EOQ、経済的発注量、発注費用、保管費用、年間需要、安全在庫

                    #Pitfall 間違えやすい点
                    √（ルート）を忘れて「2DS/H」をそのまま答えにしてしまうミスが最多。またDの単位が「年間」であることを確認する（月次需要が与えられたら12倍する）。

                    #Example 具体例
                    D=1,200個/年、S=500円/回、H=100円/個/年の場合：EOQ=√(2×1200×500÷100)=√12000≈110個。年間約11回発注するのが最適。

                    #Relation 他概念との関係
                    JIT（ジャスト・イン・タイム）はEOQの発想と対照的で、発注ロットを極小化して在庫ゼロを目指す。安全在庫（需要変動に備えるバッファ）とも合わせて整理する。

                    #MemoryHook 覚え方
                    「√(2DS/H)のルートを忘れるな」。「D＝Demand（需要）、S＝Setup（発注費）、H＝Holding（保管費）」で変数の意味を覚える。
                    EOT,
                'proficiency' => Proficiency::Incorrect->value,
                'failure_types' => [FailureType::CalculationError->value, FailureType::WrongApproach->value],
                'solved_at' => '2026-04-15',
                'last_touched_at' => $tenDaysAgo,
            ],
            [
                'subject' => '運営管理',
                'sub_category' => '生産計画・工程管理',
                'question_ref' => '2022年第17問',
                'note' => <<<'EOT'
                    PERT（Program Evaluation and Review Technique）は、プロジェクトの各作業をネットワーク図で表し、クリティカルパスを特定して工期を管理する手法です。

                    ---

                    ## クリティカルパスの概念

                    ### 1. クリティカルパス
                    プロジェクト全体の開始から終了までの経路のうち、所要時間が**最長の経路**。プロジェクトの最短完成時間を決定する。クリティカルパス上の作業が遅れると全体が遅れる。

                    ### 2. フロート（余裕時間）
                    各作業が遅れても全体工期に影響しない余裕時間。クリティカルパス上の作業のフロートは**ゼロ**。
                    - トータルフロート：後続作業の最遅開始時刻 - 当該作業の最早完了時刻
                    - フリーフロート：後続作業の最早開始時刻 - 当該作業の最早完了時刻

                    ### 3. クリティカルパスの特定方法
                    ①フォワードパス（最早時刻の計算）で最早開始・完了時刻を求める。②バックワードパス（最遅時刻の計算）で最遅開始・完了時刻を求める。③フロート＝0の作業をつなぐ経路がクリティカルパス。

                    ---

                    #Definition 定義
                    PERT：プロジェクトの作業をネットワーク（矢線図）で表し、工程管理を行う手法。クリティカルパス：開始から終了までの最長経路（＝プロジェクトの最短工期を決める）。フロート：作業の余裕時間（クリティカルパス上ではゼロ）。

                    #Formula 公式
                    トータルフロート = 最遅開始時刻 - 最早開始時刻
                    プロジェクト最短工期 = クリティカルパスの合計作業時間

                    #Keyword 重要語
                    PERT、クリティカルパス、フロート、最早時刻、最遅時刻、アクティビティ

                    #Pitfall 間違えやすい点
                    「クリティカルパスは最短経路」と思いがちだが、正しくは「最長経路」（これがプロジェクトの最短完成時間を決める）。「最長の経路＝最短の工期」という逆説を意識して覚える。

                    #Example 具体例
                    A→B（5日）、A→C→D（3日+4日）の2経路がある場合：A→B=5日、A→C→D=7日。クリティカルパスはA→C→D（最長7日）。プロジェクトは最短7日で完成。

                    #Relation 他概念との関係
                    ガントチャート（作業の時系列表示）はPERTと補完関係。CPM（クリティカルパス法）はPERTとほぼ同義で、確定的な作業時間を前提とするバリエーション。

                    #MemoryHook 覚え方
                    「クリティカルパス＝最長経路・フロートゼロ」。「最長が全体を縛る首根っこ（クリティカル）」と覚える。
                    EOT,
                'proficiency' => Proficiency::Partial->value,
                'failure_types' => [FailureType::MissingDefinition->value],
                'solved_at' => '2026-04-22',
                'last_touched_at' => $twoDaysAgo,
            ],

            // ──────────────────────────────────────────────────
            // 経営法務：解法
            // ──────────────────────────────────────────────────
            [
                'subject' => '経営法務',
                'sub_category' => '会社法',
                'question_ref' => '2023年第7問',
                'note' => <<<'EOT'
                    株式会社の設立方法には「発起設立」と「募集設立」の2種類があります。発起人が設立時に全株式を引き受けるかどうかが最大の違いです。

                    ---

                    ## 発起設立と募集設立の比較

                    ### 1. 発起設立
                    発起人が設立時に発行される**すべての株式**を引き受ける方式。手続きがシンプルで、中小企業の設立で一般的。

                    ### 2. 募集設立
                    発起人が一部の株式を引き受け、残りを**発起人以外の者（設立時募集株主）**から広く募集する方式。手続きが複雑で、創立総会の開催が必要。

                    ### 3. 創立総会（募集設立のみ）
                    募集設立では発起人と設立時募集株主が参加する「創立総会」を開き、取締役・監査役の選任などを行う。発起設立では不要。

                    ---

                    | 項目 | 発起設立 | 募集設立 |
                    | --- | --- | --- |
                    | **株式の引受け** | 発起人のみが全株式を引き受け | 発起人＋募集株主が引き受け |
                    | **創立総会** | 不要 | 必要 |
                    | **手続きの複雑さ** | シンプル | 複雑 |
                    | **主な利用場面** | 中小企業の設立 | 大規模な設立（まれ） |

                    ---

                    #Definition 定義
                    発起人：株式会社の設立を企画し、定款に署名・記名押印する者。発起設立：発起人が全株式を引き受ける設立方法。募集設立：発起人以外からも株式を募集する設立方法。創立総会：募集設立時に発起人・募集株主が参加して機関を設置する総会。

                    #Formula 公式
                    （なし）

                    #Keyword 重要語
                    発起人、発起設立、募集設立、創立総会、設立時募集株主、定款

                    #Pitfall 間違えやすい点
                    「募集設立＝公開募集（IPO）」と混同しやすいが、募集設立は設立段階での手続きであり、株式公開（上場）とは別の概念。また創立総会は**募集設立にのみ**存在し、発起設立には存在しない。

                    #Example 具体例
                    3名で会社を設立し、3名全員が出資する場合：発起設立で全員が発起人として株式を引き受ける。設立資金の一部を社外の投資家から集める場合は募集設立となる。

                    #Relation 他概念との関係
                    設立後の資金調達（増資・社債）とは異なる「設立段階」の手続き。公開会社への移行（上場）は設立後の別プロセス。

                    #MemoryHook 覚え方
                    「発起設立＝発起人だけで完結」「募集設立＝外から募集→創立総会が必要」。
                    EOT,
                'proficiency' => Proficiency::Partial->value,
                'failure_types' => [FailureType::MissingDefinition->value],
                'solved_at' => '2026-04-28',
                'last_touched_at' => $fiveDaysAgo,
            ],

            // ──────────────────────────────────────────────────
            // 企業経営理論：解法
            // ──────────────────────────────────────────────────
            [
                'subject' => '企業経営理論',
                'sub_category' => 'ドメイン・成長戦略',
                'question_ref' => '2022年第5問',
                'note' => <<<'EOT'
                    アンゾフの成長マトリクスは、「市場」と「製品」の2軸（既存/新規）で4つの成長戦略を体系化したフレームワークです。各戦略の名称と組み合わせの混同が頻出の誤答パターンです。

                    ---

                    ## 4つの成長戦略

                    ### 1. 市場浸透戦略（既存市場×既存製品）
                    現在の市場でシェアを拡大する。価格引き下げ・販促強化など。リスク最小。

                    ### 2. 市場開発戦略（新市場×既存製品）
                    既存製品を新しい市場（新地域・新顧客層）に展開する。海外進出など。

                    ### 3. 製品開発戦略（既存市場×新製品）
                    既存顧客に新製品を提供する。既存ブランド力を活かして新商品を投入。

                    ### 4. 多角化戦略（新市場×新製品）
                    新市場に新製品で参入する。リスク最大だが成長機会も最大。

                    ---

                    | | **既存製品** | **新製品** |
                    | --- | --- | --- |
                    | **既存市場** | 市場浸透 | 製品開発 |
                    | **新市場** | 市場開発 | 多角化 |

                    ---

                    #Definition 定義
                    アンゾフの成長マトリクス：市場（既存/新規）と製品（既存/新規）の2×2マトリクスで4つの成長戦略を分類するフレームワーク。

                    #Formula 公式
                    （なし）

                    #Keyword 重要語
                    市場浸透、市場開発、製品開発、多角化、アンゾフ、成長マトリクス

                    #Pitfall 間違えやすい点
                    「新市場×新製品＝製品開発戦略」と「既存市場×新製品＝製品開発戦略」を混同しやすい。正しくは「既存市場×新製品＝製品開発」「新市場×既存製品＝市場開発」。「市場」と「製品」のどちらが新規かを明確に意識する。

                    #Example 具体例
                    製品開発戦略：既存のスマートフォン顧客向けにスマートウォッチを新発売（既存顧客×新製品）。市場開発戦略：国内向け製品をそのまま東南アジアに展開（既存製品×新市場）。

                    #Relation 他概念との関係
                    PPM（プロダクト・ポートフォリオ・マネジメント）と合わせて使われることが多い。多角化戦略はM&Aの文脈とも関連する。

                    #MemoryHook 覚え方
                    マトリクスを頭に描いて「縦が市場（上＝既存・下＝新）、横が製品（左＝既存・右＝新）」と固定する。「製品開発は縦（市場）は変えず、横（製品）だけ変える」。
                    EOT,
                'proficiency' => Proficiency::Partial->value,
                'failure_types' => [FailureType::MissingDefinition->value],
                'solved_at' => '2026-04-25',
                'last_touched_at' => $tenDaysAgo,
            ],
            [
                'subject' => '企業経営理論',
                'sub_category' => '経営資源戦略・VRIO',
                'question_ref' => '2023年第9問',
                'note' => <<<'EOT'
                    VRIOフレームワークは、企業の経営資源が「持続的な競争優位」をもたらすかどうかを4つの基準で評価する分析ツールです。V→R→I→Oの順序で評価することが重要です。

                    ---

                    ## VRIOの4基準と評価フロー

                    ### 1. V（Value：価値）
                    経営資源が外部環境の機会を活かし脅威に対応できるか。価値がなければ競争劣位となる。

                    ### 2. R（Rarity：希少性）
                    他社が同様の経営資源を保有していないか。希少性がなければ競争均衡に留まる。

                    ### 3. I（Imitability：模倣困難性）
                    競合他社が模倣・代替するのが困難か。模倣容易な資源では一時的優位しか得られない。

                    ### 4. O（Organization：組織）
                    企業がその資源を活用できる組織体制・プロセス・文化を持っているか。組織がなければ持続的優位は実現しない。

                    ---

                    | V | R | I | O | 競争上の位置 |
                    | --- | --- | --- | --- | --- |
                    | No | - | - | - | 競争劣位 |
                    | Yes | No | - | - | 競争均衡 |
                    | Yes | Yes | No | - | 一時的優位 |
                    | Yes | Yes | Yes | Yes | **持続的優位** |

                    ---

                    #Definition 定義
                    VRIOフレームワーク（バーニー提唱）：経営資源の価値（V）・希少性（R）・模倣困難性（I）・組織（O）を評価し、持続的競争優位の源泉を特定するフレームワーク。

                    #Formula 公式
                    （なし）

                    #Keyword 重要語
                    VRIO、経営資源、持続的競争優位、模倣困難性、組織的補完性、バーニー

                    #Pitfall 間違えやすい点
                    VRIOはV→R→I→Oの順序で評価する（前の基準を満たさなければ次の基準は意味がない）。特に「模倣困難性があっても組織（O）が活用できなければ持続的優位にならない」という点を見落としやすい。

                    #Example 具体例
                    特定企業の独自技術（V=Yes, R=Yes, I=Yes）があっても、その技術を製品化・市場展開できる組織能力（O）がなければ持続的優位は実現しない。

                    #Relation 他概念との関係
                    RBV（リソース・ベースト・ビュー）の代表的分析ツール。外部環境分析（SWOT・5フォース）が「業界の魅力度」を見るのに対し、VRIOは「自社の強みの持続性」を評価する内部分析。

                    #MemoryHook 覚え方
                    「V→R→I→Oの順で関門をくぐる」。「価値がある？希少？真似できない？使える組織がある？全部Yesで初めて持続的優位」。
                    EOT,
                'proficiency' => Proficiency::Partial->value,
                'failure_types' => [FailureType::WrongApproach->value],
                'solved_at' => '2026-05-01',
                'last_touched_at' => $fiveDaysAgo,
            ],

            // ──────────────────────────────────────────────────
            // 経営情報システム
            // ──────────────────────────────────────────────────
            [
                'subject' => '経営情報システム',
                'sub_category' => 'データベース',
                'question_ref' => '2022年第25問',
                'note' => <<<'EOT'
                    データベースの正規化は、データの冗長性と更新異常を排除するために、段階的にテーブル構造を整理するプロセスです。第1〜第3正規形の定義と順序が頻出です。

                    ---

                    ## 正規化の段階

                    ### 1. 第1正規形（1NF）
                    繰り返し項目（配列状の属性）を排除し、すべての属性が**原子値**（単一値）になっている状態。

                    ### 2. 第2正規形（2NF）
                    第1NFを満たした上で、**部分関数従属**を排除した状態。複合主キーの一部にのみ従属する非キー属性を別テーブルに分離する。

                    ### 3. 第3正規形（3NF）
                    第2NFを満たした上で、**推移的関数従属**を排除した状態。非キー属性→非キー属性という従属関係をなくす。

                    ---

                    | 正規形 | 排除する問題 | 条件 |
                    | --- | --- | --- |
                    | **第1正規形（1NF）** | 繰り返し項目 | 全属性が原子値 |
                    | **第2正規形（2NF）** | 部分関数従属 | 1NFを満たし、非キー属性が主キー全体に従属 |
                    | **第3正規形（3NF）** | 推移的関数従属 | 2NFを満たし、非キー→非キーの従属がない |

                    ---

                    #Definition 定義
                    関数従属：属性Aの値が決まれば属性Bの値が一意に決まる関係（A→B）。部分関数従属：複合主キーの一部にのみ非キー属性が従属すること。推移的関数従属：非キー属性を介して主キーと別の非キー属性が間接的に従属すること（A→B→C）。

                    #Formula 公式
                    （なし）

                    #Keyword 重要語
                    正規化、第1正規形、第2正規形、第3正規形、関数従属、部分関数従属、推移的関数従属

                    #Pitfall 間違えやすい点
                    正規形の番号と「排除する問題」の対応を取り違えやすい。「1NF＝繰り返し排除」「2NF＝部分従属排除」「3NF＝推移従属排除」を順番とセットで覚える。

                    #Example 具体例
                    受注テーブル（受注ID, 商品ID, 商品名, 数量）：商品名は商品IDにのみ従属（部分関数従属）。2NFにするには商品テーブル（商品ID, 商品名）を分離する。

                    #Relation 他概念との関係
                    ER図（エンティティ・リレーションシップ図）で設計した後に正規化を適用するのが一般的。BCNF（ボイスコッド正規形）は3NFをさらに厳格化したもの。

                    #MemoryHook 覚え方
                    「1→繰り返し排除、2→部分排除、3→推移排除」を「繰・部・推（クブスイ）」で覚える。
                    EOT,
                'proficiency' => Proficiency::Incorrect->value,
                'failure_types' => [FailureType::MissingDefinition->value],
                'solved_at' => '2026-05-02',
                'last_touched_at' => $yesterday,
            ],
            [
                'subject' => '経営情報システム',
                'sub_category' => 'アルゴリズム',
                'question_ref' => '2023年第28問',
                'note' => <<<'EOT'
                    ソートアルゴリズムの計算量（時間複雑度）は、データ量の増加に対して処理時間がどう変化するかを表します。バブルソートとクイックソートの特性の違いが頻出論点です。

                    ---

                    ## バブルソートとクイックソートの比較

                    ### 1. バブルソート
                    隣接する要素を順に比較・交換を繰り返すシンプルなアルゴリズム。n個の要素をソートする際の比較回数はn(n-1)/2回（平均・最悪ともO(n²)）。

                    ### 2. クイックソート
                    基準値（ピボット）を選び、それより小さい要素と大きい要素に分割して再帰的にソートする。平均計算量はO(n log n)。ただし最悪ケース（整列済みデータなど）ではO(n²)となる。

                    ### 3. 計算量の比較
                    - バブルソート：常にO(n²)
                    - クイックソート：平均O(n log n)、最悪O(n²)
                    - マージソート：最悪でもO(n log n)（安定ソート）

                    ---

                    | アルゴリズム | 平均計算量 | 最悪計算量 | 安定性 |
                    | --- | --- | --- | --- |
                    | **バブルソート** | O(n²) | O(n²) | 安定 |
                    | **クイックソート** | O(n log n) | O(n²) | 不安定 |
                    | **マージソート** | O(n log n) | O(n log n) | 安定 |

                    ---

                    #Definition 定義
                    O記法（ビッグオー記法）：アルゴリズムの計算量をデータ量nの関数で表す近似表記。バブルソート：隣接要素を繰り返し比較・交換するソート。クイックソート：ピボットを基準に分割統治で行うソート。

                    #Formula 公式
                    バブルソートの比較回数：n(n-1)/2 回 → O(n²)
                    クイックソートの平均計算量：O(n log n)、最悪：O(n²)

                    #Keyword 重要語
                    バブルソート、クイックソート、計算量、O記法、ピボット、安定ソート

                    #Pitfall 間違えやすい点
                    「クイックソートは常にO(n log n)」と思いがちだが、最悪ケースではO(n²)になる。また「バブルソートの比較回数はn²回」と丸暗記すると、正確にはn(n-1)/2回である点でずれが生じる（オーダーとしてはO(n²)で正しい）。

                    #Example 具体例
                    n=1,000のデータ：バブルソートは約499,500回の比較が必要。クイックソートの平均は約10,000回で、約50倍の差がある。

                    #Relation 他概念との関係
                    二分探索（O(log n)）、線形探索（O(n)）と合わせてアルゴリズムの計算量の体系を整理する。データ構造（スタック・キュー・木構造）とも関連する。

                    #MemoryHook 覚え方
                    「バブルは遅い（常にn²）」「クイックは平均は速い（n log n）でも最悪はバブルと同じ」「マージが最も安定（常にn log n）」。
                    EOT,
                'proficiency' => Proficiency::Incorrect->value,
                'failure_types' => [FailureType::MissingDefinition->value, FailureType::CalculationError->value],
                'solved_at' => '2026-05-03',
                'last_touched_at' => null,
            ],
        ];
    }

    // ----------------------------------------------------------------
    // Exam Sessions（模擬試験）
    // ----------------------------------------------------------------

    private function seedExamSessions(): void
    {
        $subjectMap = Subject::pluck('id', 'name');

        // 4月中旬：財務・会計の過去問セッション（完了済み）
        $this->createExamSession(
            subjectId: $subjectMap['財務・会計'],
            examYear: '2023',
            completedAt: Carbon::create(2026, 4, 13, 22, 0, 0),
            questions: $this->financeExamQuestions(),
        );

        // 4月下旬：企業経営理論の過去問セッション（完了済み）
        $this->createExamSession(
            subjectId: $subjectMap['企業経営理論'],
            examYear: '2022',
            completedAt: Carbon::create(2026, 4, 27, 21, 30, 0),
            questions: $this->managementExamQuestions(),
        );

        // 5月上旬：経済学（進行中）
        $this->createExamSession(
            subjectId: $subjectMap['経済学・経済政策'],
            examYear: '2023',
            completedAt: null,
            questions: $this->economicsExamQuestions(),
        );
    }

    private function createExamSession(int $subjectId, string $examYear, ?Carbon $completedAt, array $questions): void
    {
        $status = $completedAt ? 'completed' : 'in_progress';

        $session = ExamSession::create([
            'user_id' => $this->userId,
            'subject_id' => $subjectId,
            'exam_year' => $examYear,
            'status' => $status,
            'completed_at' => $completedAt,
        ]);

        foreach ($questions as $i => $q) {
            ExamQuestion::create([
                'exam_session_id' => $session->id,
                'sort_order' => $i + 1,
                'display_id' => $q['display_id'],
                'is_sub' => $q['is_sub'] ?? false,
                'has_children' => $q['has_children'] ?? false,
                'rank' => $q['rank'],
                'my_answer' => $q['my_answer'] ?? null,
                'is_correct' => $q['is_correct'] ?? null,
                'is_doubtful' => $q['is_doubtful'] ?? false,
                'point' => $q['point'] ?? 0,
                'note' => $q['note'] ?? null,
                'answered_time_ms' => $q['answered_time_ms'] ?? null,
            ]);
        }
    }

    private function financeExamQuestions(): array
    {
        $results = [
            // 正解 13/25 ≈ 66点（まずまず）
            [true, 4, 'A', false], [false, 4, 'B', false], [true, 4, 'A', false],
            [false, 4, 'C', true], [true, 4, 'B', false], [true, 4, 'A', false],
            [false, 4, 'C', false], [true, 4, 'B', false], [false, 4, 'C', true],
            [true, 4, 'A', false], [false, 4, 'B', false], [true, 4, 'A', false],
            [true, 4, 'B', false], [false, 4, 'C', false], [true, 4, 'A', false],
            [false, 4, 'B', true], [true, 4, 'A', false], [true, 4, 'B', false],
            [false, 4, 'C', false], [true, 4, 'A', false], [true, 4, 'B', false],
            [false, 4, 'C', false], [true, 4, 'A', false], [false, 4, 'B', false],
            [true, 4, 'A', false],
        ];

        return array_map(fn($r, $i) => [
            'display_id' => (string)($i + 1),
            'rank' => $r[2],
            'my_answer' => $r[0] ? 'ア' : 'イ',
            'is_correct' => $r[0],
            'is_doubtful' => $r[3],
            'point' => $r[0] ? $r[1] : 0,
            'answered_time_ms' => mt_rand(30000, 120000),
        ], $results, array_keys($results));
    }

    private function managementExamQuestions(): array
    {
        $results = [
            // 正解 16/25 ≈ 68点
            [true, 4, 'A', false], [true, 4, 'B', false], [false, 4, 'B', true],
            [true, 4, 'A', false], [false, 4, 'C', false], [true, 4, 'A', false],
            [true, 4, 'B', false], [false, 4, 'C', true], [true, 4, 'A', false],
            [true, 4, 'B', false], [false, 4, 'B', false], [true, 4, 'A', false],
            [false, 4, 'C', false], [true, 4, 'A', false], [true, 4, 'B', false],
            [true, 4, 'A', false], [false, 4, 'C', false], [true, 4, 'B', false],
            [false, 4, 'B', true], [true, 4, 'A', false], [true, 4, 'B', false],
            [false, 4, 'C', false], [true, 4, 'A', false], [true, 4, 'B', false],
            [false, 4, 'C', false],
        ];

        return array_map(fn($r, $i) => [
            'display_id' => (string)($i + 1),
            'rank' => $r[2],
            'my_answer' => $r[0] ? 'ア' : 'イ',
            'is_correct' => $r[0],
            'is_doubtful' => $r[3],
            'point' => $r[0] ? $r[1] : 0,
            'answered_time_ms' => mt_rand(30000, 120000),
        ], $results, array_keys($results));
    }

    private function economicsExamQuestions(): array
    {
        // 進行中セッション（5問だけ回答済み）
        $answered = [
            [true, 4, 'B', false],
            [false, 4, 'C', true],
            [false, 4, 'C', false],
            [true, 4, 'A', false],
            [false, 4, 'B', true],
        ];

        $questions = array_map(fn($r, $i) => [
            'display_id' => (string)($i + 1),
            'rank' => $r[2],
            'my_answer' => $r[0] ? 'ア' : 'イ',
            'is_correct' => $r[0],
            'is_doubtful' => $r[3],
            'point' => $r[0] ? $r[1] : 0,
            'answered_time_ms' => mt_rand(30000, 120000),
        ], $answered, array_keys($answered));

        // 残り20問は未回答
        for ($i = 5; $i < 25; $i++) {
            $questions[] = [
                'display_id' => (string)($i + 1),
                'rank' => 'B',
                'my_answer' => null,
                'is_correct' => null,
                'is_doubtful' => false,
                'point' => 0,
                'answered_time_ms' => null,
            ];
        }

        return $questions;
    }

    // ----------------------------------------------------------------
    // Sprints / StudyTickets / TicketNotes
    // ----------------------------------------------------------------

    private function seedSprints(): void
    {
        $subjectMap = Subject::pluck('id', 'name');
        $subCategoryMap = SubCategory::where('user_id', $this->userId)
            ->get(['id', 'name'])
            ->pluck('id', 'name');

        // ── バックログ ──────────────────────────────────────────────
        $backlog = Sprint::firstOrCreate(
            ['user_id' => $this->userId, 'type' => SprintType::Backlog->value],
            [
                'name' => 'バックログ',
                'status' => SprintStatus::Active->value,
            ]
        );

        // ── Sprint 1（完了：財務中心 4/14–4/27）────────────────────
        $sprint1 = Sprint::firstOrCreate(
            ['user_id' => $this->userId, 'name' => 'Sprint 1 - 財務集中週'],
            [
                'type' => SprintType::Active->value,
                'status' => SprintStatus::Completed->value,
                'goal' => 'CVP分析と資本コストを完璧にする。財務の過去問1.5年分を一周する。',
                'start_date' => '2026-04-14',
                'end_date' => '2026-04-27',
                'completed_at' => Carbon::create(2026, 4, 27, 23, 0, 0),
                'retrospective' => "CVP分析はほぼ完璧に解けるようになった。資本コスト・CAPMは符号ミスが続いており来スプリントでも継続する。全体的に財務の理解度が向上した。",
            ]
        );

        // ── Sprint 2（完了：企業経営理論＋法務 4/28–5/10）──────────
        $sprint2 = Sprint::firstOrCreate(
            ['user_id' => $this->userId, 'name' => 'Sprint 2 - 企業経営理論＆法務'],
            [
                'type' => SprintType::Active->value,
                'status' => SprintStatus::Completed->value,
                'goal' => '企業経営理論の組織論・マーケティングを総復習。法務は会社法と知財を優先する。',
                'start_date' => '2026-04-28',
                'end_date' => '2026-05-10',
                'completed_at' => Carbon::create(2026, 5, 10, 23, 0, 0),
                'retrospective' => "動機付け理論は完全に整理できた。会社法の機関設計は公開会社・非公開会社の区別が難しくフローチャートを作って対応した。手応えがある。",
            ]
        );

        // ── Sprint 3（進行中：経済学＋法務 5/11–5/24）──────────────
        $sprint3 = Sprint::firstOrCreate(
            ['user_id' => $this->userId, 'name' => 'Sprint 3 - 経済学・法務'],
            [
                'type' => SprintType::Active->value,
                'status' => SprintStatus::Active->value,
                'goal' => '経済学のIS-LM・AD-AS分析を計算問題で解けるようにする。法務の会社法機関設計を完璧にする。',
                'start_date' => '2026-05-11',
                'end_date' => '2026-05-24',
            ]
        );

        $this->seedTickets($backlog, $sprint1, $sprint2, $sprint3, $subjectMap, $subCategoryMap);
    }

    private function seedTickets(
        Sprint $backlog,
        Sprint $sprint1,
        Sprint $sprint2,
        Sprint $sprint3,
               $subjectMap,
               $subCategoryMap,
    ): void {
        // ── バックログ ──────────────────────────────────────────────
        $this->createTicket($backlog->id, $subjectMap['財務・会計'] ?? null, [
            'title' => '証券投資論のポートフォリオリスク計算を整理する',
            'acceptance_criteria' => '相関係数を使ったポートフォリオのリスク計算を3問以上正解できる',
            'status' => TicketStatus::Todo->value,
            'priority' => TicketPriority::High->value,
            'ticket_type' => TicketType::Practice->value,
            'source' => TicketSource::WrongAnswer->value,
            'estimate_minutes' => 90,
        ], [$subCategoryMap['証券投資論'] ?? null]);

        $this->createTicket($backlog->id, $subjectMap['経営法務'] ?? null, [
            'title' => '英文契約の基本用語リストを作る',
            'status' => TicketStatus::Todo->value,
            'priority' => TicketPriority::Low->value,
            'ticket_type' => TicketType::Memorization->value,
            'source' => TicketSource::Manual->value,
            'estimate_minutes' => 45,
        ], [$subCategoryMap['英文契約'] ?? null]);

        // ── Sprint 1 ────────────────────────────────────────────────
        $t1a = $this->createTicket($sprint1->id, $subjectMap['財務・会計'] ?? null, [
            'title' => 'CVP分析の過去問を10問解く（損益分岐点・目標利益）',
            'acceptance_criteria' => '損益分岐点売上高・目標利益を求める計算問題を10問解いて8問以上正解する',
            'status' => TicketStatus::Done->value,
            'priority' => TicketPriority::High->value,
            'ticket_type' => TicketType::Practice->value,
            'source' => TicketSource::WrongAnswer->value,
            'estimate_minutes' => 60,
            'completed_at' => Carbon::create(2026, 4, 20, 22, 0, 0),
        ], [$subCategoryMap['CVP分析'] ?? null]);

        $this->createTicket($sprint1->id, $subjectMap['財務・会計'] ?? null, [
            'title' => '資本コスト・CAPMの計算公式を整理してノートにまとめる',
            'acceptance_criteria' => 'CAPMの計算式を暗記し、数値代入問題を3問解ける',
            'status' => TicketStatus::Done->value,
            'priority' => TicketPriority::High->value,
            'ticket_type' => TicketType::Knowledge->value,
            'source' => TicketSource::WrongAnswer->value,
            'estimate_minutes' => 60,
            'completed_at' => Carbon::create(2026, 4, 25, 21, 30, 0),
        ], [$subCategoryMap['資本コスト・CAPM'] ?? null]);

        $this->createTicket($sprint1->id, $subjectMap['財務・会計'] ?? null, [
            'title' => '財務諸表分析のROE分解（デュポン分析）を完璧にする',
            'acceptance_criteria' => 'ROE＝当期純利益率×総資産回転率×財務レバレッジを説明できる',
            'status' => TicketStatus::Done->value,
            'priority' => TicketPriority::Medium->value,
            'ticket_type' => TicketType::Understanding->value,
            'source' => TicketSource::Review->value,
            'estimate_minutes' => 45,
            'completed_at' => Carbon::create(2026, 4, 27, 20, 0, 0),
        ], [$subCategoryMap['財務諸表分析'] ?? null]);

        // ── Sprint 2 ────────────────────────────────────────────────
        $t2a = $this->createTicket($sprint2->id, $subjectMap['企業経営理論'] ?? null, [
            'title' => '動機付け理論（ハーズバーグ・マズロー）の比較表を作る',
            'acceptance_criteria' => '衛生要因と動機付け要因の違い、マズローとの対応関係を説明できる',
            'status' => TicketStatus::Done->value,
            'priority' => TicketPriority::High->value,
            'ticket_type' => TicketType::Memorization->value,
            'source' => TicketSource::WrongAnswer->value,
            'estimate_minutes' => 60,
            'completed_at' => Carbon::create(2026, 5, 3, 22, 0, 0),
        ], [$subCategoryMap['動機付け理論'] ?? null]);

        $this->createTicket($sprint2->id, $subjectMap['経営法務'] ?? null, [
            'title' => '会社法の機関設計（取締役会・監査役会）をフローチャートで整理',
            'acceptance_criteria' => '公開会社・非公開会社ごとに必要機関を正しく答えられる',
            'status' => TicketStatus::Done->value,
            'priority' => TicketPriority::High->value,
            'ticket_type' => TicketType::Knowledge->value,
            'source' => TicketSource::WrongAnswer->value,
            'estimate_minutes' => 90,
            'completed_at' => Carbon::create(2026, 5, 7, 21, 0, 0),
        ], [$subCategoryMap['会社法'] ?? null]);

        $this->createTicket($sprint2->id, $subjectMap['経営法務'] ?? null, [
            'title' => '知的財産権の存続期間を暗記カードで覚える',
            'acceptance_criteria' => '特許・実用新案・商標・著作権の存続期間を即答できる',
            'status' => TicketStatus::Done->value,
            'priority' => TicketPriority::Medium->value,
            'ticket_type' => TicketType::Memorization->value,
            'source' => TicketSource::Manual->value,
            'estimate_minutes' => 30,
            'completed_at' => Carbon::create(2026, 5, 10, 20, 0, 0),
        ], [$subCategoryMap['知的財産権'] ?? null]);

        $this->createTicket($sprint2->id, $subjectMap['企業経営理論'] ?? null, [
            'title' => 'マーケティング・ミックスの4Pを実例で整理する',
            'acceptance_criteria' => '各P（Product/Price/Place/Promotion）を実例とともに説明できる',
            'status' => TicketStatus::Done->value,
            'priority' => TicketPriority::Medium->value,
            'ticket_type' => TicketType::Understanding->value,
            'source' => TicketSource::Review->value,
            'estimate_minutes' => 45,
            'completed_at' => Carbon::create(2026, 5, 9, 22, 0, 0),
        ], [$subCategoryMap['マーケティング・ミックス'] ?? null]);

        // ── Sprint 3 ────────────────────────────────────────────────
        $this->createTicket($sprint3->id, $subjectMap['経済学・経済政策'] ?? null, [
            'title' => 'IS-LM分析のグラフ操作の練習問題を5問解く',
            'acceptance_criteria' => '財政政策・金融政策でのIS・LM曲線シフトをグラフで説明できる',
            'status' => TicketStatus::Doing->value,
            'priority' => TicketPriority::High->value,
            'ticket_type' => TicketType::Practice->value,
            'source' => TicketSource::WrongAnswer->value,
            'estimate_minutes' => 90,
        ], [$subCategoryMap['IS-LM分析'] ?? null]);

        $t3b = $this->createTicket($sprint3->id, $subjectMap['経済学・経済政策'] ?? null, [
            'title' => '余剰分析（消費者余剰・課税の死荷重）の計算問題を解く',
            'acceptance_criteria' => '課税前後の消費者余剰・生産者余剰・死荷重を計算できる',
            'status' => TicketStatus::Done->value,
            'priority' => TicketPriority::High->value,
            'ticket_type' => TicketType::Practice->value,
            'source' => TicketSource::WrongAnswer->value,
            'estimate_minutes' => 60,
            'completed_at' => Carbon::create(2026, 5, 14, 21, 0, 0),
        ], [$subCategoryMap['余剰分析'] ?? null]);

        $this->createTicket($sprint3->id, $subjectMap['経済学・経済政策'] ?? null, [
            'title' => 'AD-AS分析における財政・金融政策の効果を整理する',
            'acceptance_criteria' => '需要ショック・供給ショック時のAD・AS曲線の動きをグラフで説明できる',
            'status' => TicketStatus::Todo->value,
            'priority' => TicketPriority::Medium->value,
            'ticket_type' => TicketType::Understanding->value,
            'source' => TicketSource::Manual->value,
            'estimate_minutes' => 60,
        ], [$subCategoryMap['AD-AS分析'] ?? null]);

        $this->createTicket($sprint3->id, $subjectMap['経営法務'] ?? null, [
            'title' => '会社法の機関設計フローチャートを復習する（Sprint 2継続）',
            'acceptance_criteria' => '取締役会設置会社の場合の監査役の要否を正確に判断できる',
            'status' => TicketStatus::Todo->value,
            'priority' => TicketPriority::Medium->value,
            'ticket_type' => TicketType::Understanding->value,
            'source' => TicketSource::Review->value,
            'estimate_minutes' => 30,
        ], [$subCategoryMap['会社法'] ?? null]);

        // ── Ticket Notes ─────────────────────────────────────────────
        if ($t1a->notes()->count() === 0) {
            TicketNote::create([
                'ticket_id' => $t1a->id,
                'user_id' => $this->userId,
                'body' => "損益分岐点売上高 = 固定費 ÷ 限界利益率（1 - 変動費率）の公式を先に整理してから計算するとミスが減る。\n\n変動費率を先に求めて限界利益率を出す手順で解くとスムーズ。",
            ]);
        }

        if ($t2a->notes()->count() === 0) {
            TicketNote::create([
                'ticket_id' => $t2a->id,
                'user_id' => $this->userId,
                'body' => "ハーズバーグの二要因理論のポイント：\n\n- **衛生要因**（給与・労働条件）→ 不満の防止にはなるが満足・動機付けにはならない\n- **動機付け要因**（達成・承認・成長）→ 真の満足と動機付けをもたらす\n\nマズローとの対応：衛生要因 ≈ 下位欲求（生理・安全・社会）、動機付け要因 ≈ 上位欲求（尊厳・自己実現）",
            ]);
        }

        if ($t3b->notes()->count() === 0) {
            TicketNote::create([
                'ticket_id' => $t3b->id,
                'user_id' => $this->userId,
                'body' => "死荷重の計算手順：\n\n1. 課税前の均衡点（P0, Q0）を求める\n2. 課税後の均衡点（P1, Q1）を求める\n3. 死荷重 = (P1 - P0) × (Q0 - Q1) ÷ 2\n\nグラフを必ず書いてから計算する。供給曲線が上にシフトするイメージ。",
            ]);
        }
    }

    // ----------------------------------------------------------------
    // Snippets
    // ----------------------------------------------------------------

    private function seedSnippets(): void
    {
        Snippet::firstOrCreate(
            ['user_id' => $this->userId, 'title' => '解説要求'],
            ['content' => "ノートにまとめたいので、特定の選択肢に依存しないように解説してください。\n通常の解説の後に、末尾に以下のメタ文字列をつけてください。\n#Definition 定義, #Formula 公式, #Keyword 重要語, #Pitfall 間違えやすい点, #Example 具体例, #Relation 他概念との関係, #MemoryHook 覚え方"],
        );
    }

    private function createTicket(int $sprintId, ?int $subjectId, array $attrs, array $subCategoryIds = []): StudyTicket
    {
        $ticket = StudyTicket::firstOrCreate(
            ['user_id' => $this->userId, 'title' => $attrs['title']],
            array_merge([
                'sprint_id' => $sprintId,
                'subject_id' => $subjectId,
                'status' => TicketStatus::Todo->value,
                'priority' => TicketPriority::Medium->value,
                'ticket_type' => TicketType::Knowledge->value,
                'source' => TicketSource::Manual->value,
                'estimate_minutes' => 60,
                'acceptance_criteria' => 'サンプルチケット',
                'due_date' => '2026-05-18',
                'completed_at' => null,
            ], $attrs)
        );

        $syncIds = array_values(array_filter($subCategoryIds, fn($id) => $id !== null));
        if (!empty($syncIds)) {
            $ticket->subCategories()->sync($syncIds);
        }

        return $ticket;
    }
}
