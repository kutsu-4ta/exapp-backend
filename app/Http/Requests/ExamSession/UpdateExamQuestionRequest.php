<?php

namespace App\Http\Requests\ExamSession;

use App\Enums\Rank;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateExamQuestionRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            // タイムスタンプイベント
            'event' => ['required', 'string', Rule::in(['start', 'finish'])],

            // 問題メタ（初回 = 問題レコードが未作成の場合のみ利用）
            'displayId'   => ['sometimes', 'string', 'max:100'],
            'isSub'       => ['sometimes', 'boolean'],
            'hasChildren' => ['sometimes', 'boolean'],
            'rank'        => ['sometimes', 'string', Rule::enum(Rank::class)],
            'isDoubtful'  => ['sometimes', 'boolean'],
            'point'       => ['sometimes', 'integer', 'min:0'],
        ];
    }
}
