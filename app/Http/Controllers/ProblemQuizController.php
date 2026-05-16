<?php

namespace App\Http\Controllers;

use App\Models\ProblemQuiz;
use App\UseCases\ProblemQuiz\CreateProblemQuizUseCase;
use App\UseCases\ProblemQuiz\DeleteProblemQuizUseCase;
use App\UseCases\ProblemQuiz\ListProblemQuizzesUseCase;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;

class ProblemQuizController extends Controller
{
    public function __construct(
        private readonly ListProblemQuizzesUseCase  $listUseCase,
        private readonly CreateProblemQuizUseCase   $createUseCase,
        private readonly DeleteProblemQuizUseCase   $deleteUseCase,
    ) {}

    public function index(Request $request, int $id): JsonResponse
    {
        $user   = $request->user() ?? auth('sanctum')->user();
        $quizzes = ($this->listUseCase)($user->id, $id);

        return response()->json($quizzes->map(fn (ProblemQuiz $q) => $this->toResponse($q))->values());
    }

    public function store(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'quizType'     => ['required', 'in:multiple_choice,word_card'],
            'question'     => ['required', 'string'],
            'explanation'  => ['required', 'string'],
            'options'      => [Rule::requiredIf(fn () => $request->input('quizType') === 'multiple_choice'), 'array', 'min:2'],
            'options.*'    => ['string'],
            'correctIndex' => [Rule::requiredIf(fn () => $request->input('quizType') === 'multiple_choice'), 'nullable', 'integer', 'min:0'],
        ]);

        $isMultipleChoice = $validated['quizType'] === 'multiple_choice';

        $user  = $request->user() ?? auth('sanctum')->user();
        $quiz  = ($this->createUseCase)(
            $user->id,
            $id,
            $validated['quizType'],
            $validated['question'],
            $isMultipleChoice ? ($validated['options'] ?? []) : [],
            $isMultipleChoice ? ($validated['correctIndex'] ?? null) : null,
            $validated['explanation'],
        );

        return response()->json($this->toResponse($quiz), 201);
    }

    public function destroy(Request $request, int $id, int $quizId): Response
    {
        $user = $request->user() ?? auth('sanctum')->user();
        ($this->deleteUseCase)($user->id, $id, $quizId);

        return response()->noContent();
    }

    private function toResponse(ProblemQuiz $quiz): array
    {
        return [
            'id'           => $quiz->id,
            'problemId'    => $quiz->problem_id,
            'quizType'     => $quiz->quiz_type,
            'question'     => $quiz->question,
            'options'      => $quiz->options ?? [],
            'correctIndex' => $quiz->correct_index,
            'explanation'  => $quiz->explanation,
            'createdAt'    => $quiz->created_at?->toIso8601String(),
        ];
    }
}
