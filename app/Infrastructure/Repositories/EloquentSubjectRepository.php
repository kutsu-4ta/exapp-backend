<?php

namespace App\Infrastructure\Repositories;

use App\Domain\Subject\SubjectRepositoryInterface;
use App\Models\Subject;
use App\Models\SubCategory;
use Illuminate\Support\Collection;

class EloquentSubjectRepository implements SubjectRepositoryInterface
{
    /**
     * 全科目の取得（表示順）
     */
    public function findAll(): Collection
    {
        return Subject::orderBy('display_order')->get();
    }

    /**
     * IDによる取得
     */
    public function findById(int $id): ?Subject
    {
        return Subject::find($id);
    }

    /**
     * 名前による取得
     */
    public function findByName(string $name): ?Subject
    {
        return Subject::where('name', $name)->first();
    }

    /**
     * 新規ユーザー登録時の初期論点データ投入
     */
    public function seedDefaults(int $userId): void
    {
        $subjects = $this->findAll();

        // 診断士試験の各科目における主要な論点（SubCategory）
        $defaults = [
            '企業経営理論' => ['ドメイン・成長戦略', '経営資源戦略・VRIO', '組織構造・組織文化', '動機付け理論', 'マーケティング・ミックス'],
            '財務・会計' => ['財務諸表分析', 'CVP分析', '意思決定会計', '資本コスト・CAPM', '証券投資論'],
            '運営管理' => ['生産計画・工程管理', '在庫管理・JIT', '店舗施設・陳列', '物流・SCM'],
            '経済学・経済政策' => ['国民所得統計', 'IS-LM分析', 'AD-AS分析', '市場の失敗'],
            '経営法務' => ['会社法', '知的財産権', '民法'],
            '経営情報システム' => ['ITインフラ', 'システム開発', '情報セキュリティ'],
            '中小企業経営・政策' => ['中小企業基本法', '中小企業白書'],
        ];

        foreach ($subjects as $subject) {
            if (!isset($defaults[$subject->name])) continue;

            foreach ($defaults[$subject->name] as $subName) {
                SubCategory::create([
                    'user_id'    => $userId,
                    'subject_id' => $subject->id,
                    'name'       => $subName,
                ]);
            }
        }
    }
}
