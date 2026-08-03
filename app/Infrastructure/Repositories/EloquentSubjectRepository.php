<?php

namespace App\Infrastructure\Repositories;

use App\Domain\Subject\SubjectRepositoryInterface;
use App\Models\ExamSession;
use App\Models\Problem;
use App\Models\StudySession;
use App\Models\SubCategory;
use App\Models\Subject;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class EloquentSubjectRepository implements SubjectRepositoryInterface
{
    public function findAll(int $userId): Collection
    {
        return Subject::where('user_id', $userId)->where('is_hidden', false)->orderBy('display_order')->get();
    }

    public function findAllIncludingHidden(int $userId): Collection
    {
        return Subject::where('user_id', $userId)->orderBy('display_order')->get();
    }

    public function findByName(int $userId, string $name): ?Subject
    {
        return Subject::where('user_id', $userId)->where('name', $name)->first();
    }

    public function firstOrCreate(int $userId, string $name): Subject
    {
        return Subject::firstOrCreate(
            ['user_id' => $userId, 'name' => $name],
            ['display_order' => (Subject::where('user_id', $userId)->max('display_order') ?? -1) + 1],
        );
    }

    public function rename(Subject $subject, string $newName): Subject
    {
        $subject->update(['name' => $newName]);
        return $subject->fresh();
    }

    public function setHidden(Subject $subject, bool $hidden): Subject
    {
        $subject->update(['is_hidden' => $hidden]);
        return $subject->fresh();
    }

    public function delete(Subject $subject): void
    {
        DB::transaction(function () use ($subject) {
            StudySession::where('subject_id', $subject->id)->delete();
            Problem::where('subject_id', $subject->id)->delete();
            ExamSession::where('subject_id', $subject->id)->delete();
            $subject->delete(); // sub_categories cascade via FK
        });
    }

    public function seedDefaults(int $userId): void
    {
        $defaults = [
            '経済学・経済政策' => ['国民所得統計', 'IS-LM分析', 'AD-AS分析', '市場の失敗'],
            '財務・会計'       => ['財務諸表分析', 'CVP分析', '意思決定会計', '資本コスト・CAPM', '証券投資論'],
            '企業経営理論'     => ['ドメイン・成長戦略', '経営資源戦略・VRIO', '組織構造・組織文化', '動機付け理論', 'マーケティング・ミックス'],
            '運営管理'         => ['生産計画・工程管理', '在庫管理・JIT', '店舗施設・陳列', '物流・SCM'],
            '経営法務'         => ['会社法', '知的財産権', '民法'],
            '経営情報システム' => ['ITインフラ', 'システム開発', '情報セキュリティ'],
            '中小企業経営・政策' => ['中小企業基本法', '中小企業白書'],
        ];

        foreach (array_keys($defaults) as $order => $subjectName) {
            $subNames = $defaults[$subjectName];
            $subject = Subject::firstOrCreate(
                ['user_id' => $userId, 'name' => $subjectName],
                ['display_order' => $order],
            );
            foreach ($subNames as $subName) {
                SubCategory::firstOrCreate([
                    'user_id'    => $userId,
                    'subject_id' => $subject->id,
                    'name'       => $subName,
                ]);
            }
        }
    }
}
