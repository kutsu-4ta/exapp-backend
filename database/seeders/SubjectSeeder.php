<?php

namespace Database\Seeders;

use App\Models\Subject;
use Illuminate\Database\Seeder;

class SubjectSeeder extends Seeder
{
    public static array $definitions = [
        ['name' => '経済学・経済政策', 'display_order' => 1],
        ['name' => '財務・会計',        'display_order' => 2],
        ['name' => '企業経営理論',       'display_order' => 3],
        ['name' => '運営管理',          'display_order' => 4],
        ['name' => '経営法務',          'display_order' => 5],
        ['name' => '経営情報システム',    'display_order' => 6],
        ['name' => '中小企業経営・政策',  'display_order' => 7],
    ];

    public function seedForUser(int $userId): void
    {
        foreach (self::$definitions as $data) {
            Subject::firstOrCreate(
                ['user_id' => $userId, 'name' => $data['name']],
                ['display_order' => $data['display_order']],
            );
        }
    }

    public function run(): void
    {
        // subjects は user_id 必須のため、このシーダー単体では実行できません。
        // LocalDevSeeder から seedForUser() を呼び出してください。
        $this->command->warn('SubjectSeeder: user_id が必要です。LocalDevSeeder 経由で実行してください。');
    }
}
