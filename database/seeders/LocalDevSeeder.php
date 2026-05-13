<?php

namespace Database\Seeders;

use App\Enums\FailureType;
use App\Enums\Proficiency;
use App\Enums\Rank;
use App\Enums\TimeSlot;
use App\Models\DailyLog;
use App\Models\ExamQuestion;
use App\Models\ExamSession;
use App\Models\Material;
use App\Models\MonthlySetting;
use App\Models\Problem;
use App\Models\StudySession;
use App\Models\SubCategory;
use App\Models\Subject;
use App\Models\SubjectMonthlyGoal;
use App\Models\SubjectSetting;
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
                'name'     => '山田 太郎',
                'password' => Hash::make('password'),
            ],
        );

        $this->userId = $user->id;

        UserProfile::updateOrCreate(
            ['user_id' => $this->userId],
            [
                'nickname'    => 'たろう',
                'occupation'  => '中小企業診断士受験生（会社員）',
                'goal'        => '2027年度の中小企業診断士一次試験合格。財務・会計と企業経営理論で高得点を狙いつつ、苦手の経済学を60点以上に引き上げる。',
                'weak_areas'  => '経済学・経済政策（IS-LM分析、余剰分析）、経営情報システム（アルゴリズム系）',
                'strong_areas'=> '財務・会計（財務諸表分析、CVP分析）',
                'interests'   => 'マーケティング戦略、組織論',
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
            ['name' => 'TAC過去問題集',     'display_order' => 1],
            ['name' => 'TACスピードテキスト', 'display_order' => 2],
            ['name' => '中小企業白書2025',   'display_order' => 3],
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
            '企業経営理論'        => ['ドメイン・成長戦略', '経営資源戦略・VRIO', '組織構造・組織文化', '動機付け理論', '消費者行動論', 'マーケティング・ミックス'],
            '財務・会計'          => ['財務諸表分析', 'CVP分析', '意思決定会計', '資本コスト・CAPM', '証券投資論'],
            '運営管理'            => ['生産計画・工程管理', '在庫管理・JIT', '店舗施設・陳列', '物流・SCM'],
            '経済学・経済政策'     => ['国民所得統計', 'IS-LM分析', 'AD-AS分析', '余剰分析', '市場の失敗'],
            '経営法務'            => ['会社法', '知的財産権', '民法', '英文契約'],
            '経営情報システム'     => ['データベース', 'ネットワーク', 'セキュリティ', 'アルゴリズム'],
            '中小企業経営・政策'   => ['中小企業基本法', '中小企業白書', '中小企業施策'],
        ];

        foreach ($data as $subjectName => $names) {
            $subjectId = $subjectMap[$subjectName] ?? null;
            if (!$subjectId) continue;

            foreach ($names as $name) {
                SubCategory::firstOrCreate([
                    'user_id'    => $this->userId,
                    'subject_id' => $subjectId,
                    'name'       => $name,
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
            ['year' => 2026, 'month' => 3, 'target_min' => 40.0,  'target_max' => 60.0],
            ['year' => 2026, 'month' => 4, 'target_min' => 60.0,  'target_max' => 80.0],
            ['year' => 2026, 'month' => 5, 'target_min' => 70.0,  'target_max' => 90.0],
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
            '財務・会計'          => '80点以上を安定させ、得点源にする。連結会計・デリバティブを重点的に固める。',
            '企業経営理論'        => '70点以上。マーケティングと組織論を落とさない。',
            '経済学・経済政策'     => '60点確保。IS-LM・AD-AS分析を確実に得点できるようにする。',
            '運営管理'            => '65点以上。生産管理の計算問題と店舗管理の暗記を両立する。',
            '経営法務'            => '60点確保。会社法・知財を優先し、英文契約は深追いしない。',
            '経営情報システム'     => '60点以上。過去問の出題パターンに慣れる。',
            '中小企業経営・政策'   => '60点以上。白書データは直前期に集中して暗記。',
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
                '財務・会計'      => 'CVP分析と資本コストを完璧に。過去問10年分の財務を一周する。',
                '企業経営理論'    => '組織論の動機付け理論を総復習。消費者行動論の頻出論点を整理する。',
                '経済学・経済政策' => 'IS-LM分析のグラフ操作を繰り返し練習する。',
            ],
            5 => [
                '財務・会計'      => '連結会計・デリバティブの苦手箇所を集中的に潰す。模試を1回受ける。',
                '企業経営理論'    => 'マーケティング・ミックスの4Pを実例とともに整理。直近3年の過去問を解く。',
                '経営法務'        => '会社法の機関設計と知的財産権の存続期間を暗記カードで定着させる。',
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
        $subjectMap    = Subject::pluck('id', 'name');
        $materialMap   = Material::where('user_id', $this->userId)->pluck('id', 'name');
        $subCategoryMap = SubCategory::where('user_id', $this->userId)
            ->get(['id', 'name'])
            ->pluck('id', 'name');

        // 学習開始日: 3月10日
        $startDate = Carbon::create(2026, 3, 10);
        $endDate   = $this->today->copy()->subDay(); // 前日まで

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
                    'reflection'   => $this->randomReflection($current),
                    'is_completed' => $current->lt($this->today->copy()->subDays(3)),
                ],
            );

            $sessions = $this->buildSessionsForDay($current, $startDate, $subjectMap, $subCategoryMap, $materialMap);

            foreach ($sessions as $s) {
                StudySession::firstOrCreate(
                    [
                        'daily_log_id' => $log->id,
                        'subject_id'   => $s['subject_id'],
                        'time_slot'    => $s['time_slot'],
                    ],
                    [
                        'sub_category_id' => $s['sub_category_id'],
                        'material_id'     => $s['material_id'],
                        'minutes'         => $s['minutes'],
                        'memo'            => $s['memo'],
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
        $base = match(true) {
            $daysIn < 14 => 0.65,
            $daysIn < 28 => 0.60,
            default      => 0.75,
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
        $daysIn   = $start->diffInDays($date);
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
                'subject_id'      => $subjectId,
                'sub_category_id' => $subCategoryMap[$subCatName] ?? null,
                'time_slot'       => $timeSlots[$i] ?? TimeSlot::Night->value,
                'material_id'     => $materialMap['TAC過去問題集'] ?? null,
                'minutes'         => max(15, $minutes),
                'memo'            => null,
            ];
        }

        return $sessions;
    }

    private function subjectWeights(int $daysIn): array
    {
        // 初期は財務・企業経営に集中
        if ($daysIn < 21) {
            return [
                '財務・会計'      => 35,
                '企業経営理論'    => 30,
                '経済学・経済政策' => 20,
                '運営管理'        => 10,
                '経営法務'        => 5,
            ];
        }
        // 中盤から全科目
        if ($daysIn < 42) {
            return [
                '財務・会計'          => 25,
                '企業経営理論'        => 22,
                '経済学・経済政策'     => 18,
                '運営管理'            => 15,
                '経営法務'            => 10,
                '経営情報システム'     => 7,
                '中小企業経営・政策'   => 3,
            ];
        }
        // 後半は全科目バランス
        return [
            '財務・会計'          => 22,
            '企業経営理論'        => 20,
            '経済学・経済政策'     => 16,
            '運営管理'            => 14,
            '経営法務'            => 12,
            '経営情報システム'     => 10,
            '中小企業経営・政策'   => 6,
        ];
    }

    private function pickSubjects(array $weights, int $count): array
    {
        $subjects = [];
        $pool = $weights;

        for ($i = 0; $i < $count; $i++) {
            if (empty($pool)) break;
            $total  = array_sum($pool);
            $rand   = mt_rand(1, $total);
            $cumul  = 0;
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
            '企業経営理論'        => ['ドメイン・成長戦略', '経営資源戦略・VRIO', '組織構造・組織文化', '動機付け理論', '消費者行動論', 'マーケティング・ミックス'],
            '財務・会計'          => ['財務諸表分析', 'CVP分析', '意思決定会計', '資本コスト・CAPM', '証券投資論'],
            '運営管理'            => ['生産計画・工程管理', '在庫管理・JIT', '店舗施設・陳列', '物流・SCM'],
            '経済学・経済政策'     => ['国民所得統計', 'IS-LM分析', 'AD-AS分析', '余剰分析', '市場の失敗'],
            '経営法務'            => ['会社法', '知的財産権', '民法', '英文契約'],
            '経営情報システム'     => ['データベース', 'ネットワーク', 'セキュリティ', 'アルゴリズム'],
            '中小企業経営・政策'   => ['中小企業基本法', '中小企業白書', '中小企業施策'],
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
        return (int) (round($raw / 15) * 15);
    }

    // ----------------------------------------------------------------
    // Problems（苦手問題）
    // ----------------------------------------------------------------

    private function seedProblems(): void
    {
        $subjectMap    = Subject::pluck('id', 'name');
        $materialMap   = Material::where('user_id', $this->userId)->pluck('id', 'name');
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
                    'user_id'      => $this->userId,
                    'subject_id'   => $subjectId,
                    'question_ref' => $p['question_ref'],
                    'solved_at'    => $p['solved_at'],
                ],
                [
                    'sub_category_id' => $subCategoryId,
                    'material_id'     => $materialId,
                    'note'            => $p['note'],
                    'proficiency'     => $p['proficiency'],
                    'failure_types'   => $p['failure_types'],
                    'is_good_question'=> $p['is_good_question'] ?? false,
                    'last_touched_at' => $p['last_touched_at'] ?? null,
                ],
            );
        }
    }

    private function problemDefinitions(): array
    {
        $yesterday  = $this->today->copy()->subDay()->toDateString();   // 2026-05-10
        $twoDaysAgo = $this->today->copy()->subDays(2)->toDateString(); // 2026-05-09
        $fiveDaysAgo = $this->today->copy()->subDays(5)->toDateString();// 2026-05-06
        $tenDaysAgo  = $this->today->copy()->subDays(10)->toDateString();

        return [
            // ──────────────────────────────────────────────────
            // 経営法務：定義（昨日タッチ済み → morning-bugfix Tier1 対象）
            // ──────────────────────────────────────────────────
            [
                'subject'       => '経営法務',
                'sub_category'  => '民法',
                'question_ref'  => '2023年第15問',
                'note'          => '諾成契約と要物契約の区別が曖昧。消費貸借は要物契約（旧民法）だが改正で書面による諾成消費貸借も可能になった。',
                'proficiency'   => Proficiency::Incorrect->value,
                'failure_types' => [FailureType::MissingDefinition->value],
                'solved_at'     => '2026-04-02',
                'last_touched_at' => $yesterday,
            ],
            [
                'subject'       => '経営法務',
                'sub_category'  => '知的財産権',
                'question_ref'  => '2022年第8問',
                'note'          => '特許権の存続期間は出願日から20年。登録日からではなく出願日から。実用新案は10年。',
                'proficiency'   => Proficiency::Partial->value,
                'failure_types' => [FailureType::MissingDefinition->value],
                'solved_at'     => '2026-04-05',
                'last_touched_at' => $yesterday,
            ],
            [
                'subject'       => '経営法務',
                'sub_category'  => '会社法',
                'question_ref'  => '2023年第3問',
                'note'          => '取締役会設置会社では監査役は必須（公開会社）。非公開会社では任意。監査役会は監査役3名以上かつ社外監査役半数以上。',
                'proficiency'   => Proficiency::Incorrect->value,
                'failure_types' => [FailureType::MissingDefinition->value],
                'solved_at'     => '2026-04-10',
                'last_touched_at' => $yesterday,
            ],

            // ──────────────────────────────────────────────────
            // 企業経営理論：定義（直近7日 → Tier2 対象）
            // ──────────────────────────────────────────────────
            [
                'subject'       => '企業経営理論',
                'sub_category'  => '動機付け理論',
                'question_ref'  => '2022年第18問',
                'note'          => 'ハーズバーグの二要因理論：衛生要因（給与・労働条件）は不満を防ぐが満足をもたらさない。動機付け要因（達成・承認）が真の満足をもたらす。',
                'proficiency'   => Proficiency::Partial->value,
                'failure_types' => [FailureType::MissingDefinition->value],
                'solved_at'     => '2026-04-18',
                'last_touched_at' => $fiveDaysAgo,
            ],
            [
                'subject'       => '企業経営理論',
                'sub_category'  => '消費者行動論',
                'question_ref'  => '2021年第22問',
                'note'          => '関与度（インボルブメント）：購買意思決定に費やす情報処理の深さ。高関与=精緻化見込みモデル（ELM）の中心ルート。',
                'proficiency'   => Proficiency::Incorrect->value,
                'failure_types' => [FailureType::MissingDefinition->value],
                'solved_at'     => '2026-04-20',
                'last_touched_at' => $twoDaysAgo,
            ],

            // ──────────────────────────────────────────────────
            // 経済学：定義（全期間 Tier3 対象）
            // ──────────────────────────────────────────────────
            [
                'subject'       => '経済学・経済政策',
                'sub_category'  => 'IS-LM分析',
                'question_ref'  => '2023年第4問',
                'note'          => 'IS曲線は財市場の均衡。右下がり。LM曲線は貨幣市場の均衡。右上がり。財政政策はIS曲線シフト、金融政策はLM曲線シフト。',
                'proficiency'   => Proficiency::Incorrect->value,
                'failure_types' => [FailureType::MissingDefinition->value, FailureType::WrongApproach->value],
                'solved_at'     => '2026-03-20',
                'last_touched_at' => $tenDaysAgo,
            ],
            [
                'subject'       => '経済学・経済政策',
                'sub_category'  => '余剰分析',
                'question_ref'  => '2022年第6問',
                'note'          => '消費者余剰=需要曲線と価格の間の面積。生産者余剰=価格と供給曲線の間の面積。課税→総余剰が減少（死荷重が発生）。',
                'proficiency'   => Proficiency::Incorrect->value,
                'failure_types' => [FailureType::MissingDefinition->value],
                'solved_at'     => '2026-03-22',
                'last_touched_at' => null,
            ],
            [
                'subject'       => '経済学・経済政策',
                'sub_category'  => 'AD-AS分析',
                'question_ref'  => '2021年第5問',
                'note'          => 'AD曲線は右下がり（物価↑→実質残高↓→利子率↑→投資↓）。AS曲線は短期では右上がり（物価↑→実質賃金↓→雇用↑→産出↑）。',
                'proficiency'   => Proficiency::Partial->value,
                'failure_types' => [FailureType::MissingDefinition->value],
                'solved_at'     => '2026-03-25',
                'last_touched_at' => null,
            ],

            // ──────────────────────────────────────────────────
            // 財務・会計：ケアレス・解法が中心
            // ──────────────────────────────────────────────────
            [
                'subject'       => '財務・会計',
                'sub_category'  => '資本コスト・CAPM',
                'question_ref'  => '2023年第12問',
                'note'          => 'CAPMで期待収益率=Rf + β(Rm - Rf)。β=1.2, Rf=2%, Rm=8% → 2+1.2×6=9.2%。マーケットリスクプレミアムはRm-Rfであることを忘れない。',
                'proficiency'   => Proficiency::Partial->value,
                'failure_types' => [FailureType::CalculationError->value],
                'solved_at'     => '2026-03-28',
                'last_touched_at' => $twoDaysAgo,
                'is_good_question' => true,
            ],
            [
                'subject'       => '財務・会計',
                'sub_category'  => 'CVP分析',
                'question_ref'  => '2022年第11問',
                'note'          => '損益分岐点売上高=固定費÷限界利益率。限界利益率=（売上高-変動費）÷売上高。符号ミス注意。',
                'proficiency'   => Proficiency::Partial->value,
                'failure_types' => [FailureType::CalculationError->value],
                'solved_at'     => '2026-04-01',
                'last_touched_at' => $tenDaysAgo,
            ],
            [
                'subject'       => '財務・会計',
                'sub_category'  => '証券投資論',
                'question_ref'  => '2023年第14問',
                'note'          => 'ポートフォリオのリスク（標準偏差）は単純平均にならない。相関係数が重要。完全正相関のときのみリスクが加重平均になる。',
                'proficiency'   => Proficiency::Incorrect->value,
                'failure_types' => [FailureType::MissingDefinition->value, FailureType::CalculationError->value],
                'solved_at'     => '2026-04-08',
                'last_touched_at' => $fiveDaysAgo,
                'is_good_question' => true,
            ],
            [
                'subject'       => '財務・会計',
                'sub_category'  => '意思決定会計',
                'question_ref'  => '2021年第10問',
                'note'          => '差額原価収益分析。埋没原価は意思決定に関係しない。増分収益と増分費用だけを比較する。',
                'proficiency'   => Proficiency::Partial->value,
                'failure_types' => [FailureType::WrongApproach->value],
                'solved_at'     => '2026-04-12',
                'last_touched_at' => $yesterday,
            ],

            // ──────────────────────────────────────────────────
            // 運営管理：解法中心
            // ──────────────────────────────────────────────────
            [
                'subject'       => '運営管理',
                'sub_category'  => '在庫管理・JIT',
                'question_ref'  => '2023年第20問',
                'note'          => 'EOQ（経済的発注量）=√(2DS/H)。D=年間需要、S=1回発注費用、H=1単位年間保管費用。√を忘れがち。',
                'proficiency'   => Proficiency::Incorrect->value,
                'failure_types' => [FailureType::CalculationError->value, FailureType::WrongApproach->value],
                'solved_at'     => '2026-04-15',
                'last_touched_at' => $tenDaysAgo,
            ],
            [
                'subject'       => '運営管理',
                'sub_category'  => '生産計画・工程管理',
                'question_ref'  => '2022年第17問',
                'note'          => 'PERT：クリティカルパスは最長経路（最短完成時間を決定する経路）。余裕時間（フロート）=0のパス。',
                'proficiency'   => Proficiency::Partial->value,
                'failure_types' => [FailureType::MissingDefinition->value],
                'solved_at'     => '2026-04-22',
                'last_touched_at' => $twoDaysAgo,
            ],

            // ──────────────────────────────────────────────────
            // 経営法務：解法
            // ──────────────────────────────────────────────────
            [
                'subject'       => '経営法務',
                'sub_category'  => '会社法',
                'question_ref'  => '2023年第7問',
                'note'          => '株式会社設立時の発起設立と募集設立の違い。発起設立は発起人のみが株式引受。募集設立は発起人以外からも募集可能。',
                'proficiency'   => Proficiency::Partial->value,
                'failure_types' => [FailureType::MissingDefinition->value],
                'solved_at'     => '2026-04-28',
                'last_touched_at' => $fiveDaysAgo,
            ],

            // ──────────────────────────────────────────────────
            // 企業経営理論：解法
            // ──────────────────────────────────────────────────
            [
                'subject'       => '企業経営理論',
                'sub_category'  => 'ドメイン・成長戦略',
                'question_ref'  => '2022年第5問',
                'note'          => 'アンゾフの成長マトリクス：既存市場×新製品=製品開発戦略。新市場×既存製品=市場開発戦略。混同しやすい。',
                'proficiency'   => Proficiency::Partial->value,
                'failure_types' => [FailureType::MissingDefinition->value],
                'solved_at'     => '2026-04-25',
                'last_touched_at' => $tenDaysAgo,
            ],
            [
                'subject'       => '企業経営理論',
                'sub_category'  => '経営資源戦略・VRIO',
                'question_ref'  => '2023年第9問',
                'note'          => 'VRIOフレームワーク：価値(V)→希少性(R)→模倣困難性(I)→組織(O)の順に問う。模倣困難性があっても組織が活用できなければ持続的優位にならない。',
                'proficiency'   => Proficiency::Partial->value,
                'failure_types' => [FailureType::WrongApproach->value],
                'solved_at'     => '2026-05-01',
                'last_touched_at' => $fiveDaysAgo,
            ],

            // ──────────────────────────────────────────────────
            // 経営情報システム
            // ──────────────────────────────────────────────────
            [
                'subject'       => '経営情報システム',
                'sub_category'  => 'データベース',
                'question_ref'  => '2022年第25問',
                'note'          => '正規化：第1正規形→繰り返し項目排除。第2正規形→部分関数従属排除。第3正規形→推移的関数従属排除。',
                'proficiency'   => Proficiency::Incorrect->value,
                'failure_types' => [FailureType::MissingDefinition->value],
                'solved_at'     => '2026-05-02',
                'last_touched_at' => $yesterday,
            ],
            [
                'subject'       => '経営情報システム',
                'sub_category'  => 'アルゴリズム',
                'question_ref'  => '2023年第28問',
                'note'          => 'バブルソートの比較回数はn(n-1)/2回。クイックソートの平均計算量はO(n log n)、最悪はO(n²)。',
                'proficiency'   => Proficiency::Incorrect->value,
                'failure_types' => [FailureType::MissingDefinition->value, FailureType::CalculationError->value],
                'solved_at'     => '2026-05-03',
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
            'user_id'      => $this->userId,
            'subject_id'   => $subjectId,
            'exam_year'    => $examYear,
            'status'       => $status,
            'completed_at' => $completedAt,
        ]);

        foreach ($questions as $i => $q) {
            ExamQuestion::create([
                'exam_session_id' => $session->id,
                'sort_order'      => $i + 1,
                'display_id'      => $q['display_id'],
                'is_sub'          => $q['is_sub'] ?? false,
                'has_children'    => $q['has_children'] ?? false,
                'rank'            => $q['rank'],
                'my_answer'       => $q['my_answer'] ?? null,
                'is_correct'      => $q['is_correct'] ?? null,
                'is_doubtful'     => $q['is_doubtful'] ?? false,
                'point'           => $q['point'] ?? 0,
                'note'            => $q['note'] ?? null,
                'answered_time_ms'=> $q['answered_time_ms'] ?? null,
            ]);
        }
    }

    private function financeExamQuestions(): array
    {
        $results = [
            // 正解 13/25 ≈ 66点（まずまず）
            [true, 4, 'A', false], [false, 4, 'B', false], [true, 4, 'A', false],
            [false, 4, 'C', true], [true, 4, 'B', false],  [true, 4, 'A', false],
            [false, 4, 'C', false],[true, 4, 'B', false],  [false, 4, 'C', true],
            [true, 4, 'A', false], [false, 4, 'B', false], [true, 4, 'A', false],
            [true, 4, 'B', false], [false, 4, 'C', false], [true, 4, 'A', false],
            [false, 4, 'B', true], [true, 4, 'A', false],  [true, 4, 'B', false],
            [false, 4, 'C', false],[true, 4, 'A', false],  [true, 4, 'B', false],
            [false, 4, 'C', false],[true, 4, 'A', false],  [false, 4, 'B', false],
            [true, 4, 'A', false],
        ];

        return array_map(fn ($r, $i) => [
            'display_id'       => (string) ($i + 1),
            'rank'             => $r[2],
            'my_answer'        => $r[0] ? 'ア' : 'イ',
            'is_correct'       => $r[0],
            'is_doubtful'      => $r[3],
            'point'            => $r[0] ? $r[1] : 0,
            'answered_time_ms' => mt_rand(30000, 120000),
        ], $results, array_keys($results));
    }

    private function managementExamQuestions(): array
    {
        $results = [
            // 正解 16/25 ≈ 68点
            [true, 4, 'A', false], [true, 4, 'B', false],  [false, 4, 'B', true],
            [true, 4, 'A', false], [false, 4, 'C', false],  [true, 4, 'A', false],
            [true, 4, 'B', false], [false, 4, 'C', true],   [true, 4, 'A', false],
            [true, 4, 'B', false], [false, 4, 'B', false],  [true, 4, 'A', false],
            [false, 4, 'C', false],[true, 4, 'A', false],   [true, 4, 'B', false],
            [true, 4, 'A', false], [false, 4, 'C', false],  [true, 4, 'B', false],
            [false, 4, 'B', true], [true, 4, 'A', false],   [true, 4, 'B', false],
            [false, 4, 'C', false],[true, 4, 'A', false],   [true, 4, 'B', false],
            [false, 4, 'C', false],
        ];

        return array_map(fn ($r, $i) => [
            'display_id'       => (string) ($i + 1),
            'rank'             => $r[2],
            'my_answer'        => $r[0] ? 'ア' : 'イ',
            'is_correct'       => $r[0],
            'is_doubtful'      => $r[3],
            'point'            => $r[0] ? $r[1] : 0,
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

        $questions = array_map(fn ($r, $i) => [
            'display_id'       => (string) ($i + 1),
            'rank'             => $r[2],
            'my_answer'        => $r[0] ? 'ア' : 'イ',
            'is_correct'       => $r[0],
            'is_doubtful'      => $r[3],
            'point'            => $r[0] ? $r[1] : 0,
            'answered_time_ms' => mt_rand(30000, 120000),
        ], $answered, array_keys($answered));

        // 残り20問は未回答
        for ($i = 5; $i < 25; $i++) {
            $questions[] = [
                'display_id'       => (string) ($i + 1),
                'rank'             => 'B',
                'my_answer'        => null,
                'is_correct'       => null,
                'is_doubtful'      => false,
                'point'            => 0,
                'answered_time_ms' => null,
            ];
        }

        return $questions;
    }
}
