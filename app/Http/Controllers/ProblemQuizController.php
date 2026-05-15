<?php

namespace App\Http\Controllers;

use App\Models\ProblemQuiz;
use App\UseCases\ProblemQuiz\CreateProblemQuizUseCase;
use App\UseCases\ProblemQuiz\DeleteProblemQuizUseCase;
use App\UseCases\ProblemQuiz\ListProblemQuizzesUseCase;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

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
            'question'      => ['required', 'string'],
            'options'       => ['required', 'array', 'min:2'],
            'options.*'     => ['required', 'string'],
            'correctIndex'  => ['required', 'integer', 'min:0'],
            'explanation'   => ['required', 'string'],
        ]);

        $user  = $request->user() ?? auth('sanctum')->user();
        $quiz  = ($this->createUseCase)(
            $user->id,
            $id,
            $validated['question'],
            $validated['options'],
            $validated['correctIndex'],
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
            'question'     => $quiz->question,
            'options'      => $quiz->options,
            'correctIndex' => $quiz->correct_index,
            'explanation'  => $quiz->explanation,
            'createdAt'    => $quiz->created_at?->toIso8601String(),
        ];
    }
}
