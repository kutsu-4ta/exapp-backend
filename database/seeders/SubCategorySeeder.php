<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class SubCategorySeeder extends Seeder
{
    public function run(): void
    {
        $userId = 1;
        $now = Carbon::now();

        $subjectMap = DB::table('subjects')->pluck('id', 'name');

        $data = [
            '企業経営理論' => ['ドメイン・成長戦略', '経営資源戦略・VRIO', '組織構造・組織文化', '動機付け理論', '消費者行動論', 'マーケティング・ミックス'],
            '財務・会計' => ['財務諸表分析', 'CVP分析', '意思決定会計', '資本コスト・CAPM', '証券投資論'],
            '運営管理' => ['生産計画・工程管理', '在庫管理・JIT', '店舗施設・陳列', '物流・SCM'],
            '経済学・経済政策' => ['国民所得統計', 'IS-LM分析', 'AD-AS分析', '余剰分析', '市場の失敗'],
            '経営法務' => ['会社法', '知的財産権', '民法', '英文契約'],
            '中小企業経営・政策' => ['中小企業基本法', '中小企業白書', '中小企業施策'],
        ];

        foreach ($data as $subjectName => $names) {
            if (!isset($subjectMap[$subjectName])) {
                continue;
            }

            foreach ($names as $name) {
                DB::table('sub_categories')->updateOrInsert(
                    [
                        'user_id' => $userId,
                        'subject_id' => $subjectMap[$subjectName],
                        'name' => $name,
                    ],
                    ['created_at' => $now, 'updated_at' => $now]
                );
            }
        }
    }
}
