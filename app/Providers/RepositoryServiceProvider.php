<?php

namespace App\Providers;

use App\Domain\AlertSetting\AlertSettingRepositoryInterface;
use App\Domain\SubjectAlertSetting\SubjectAlertSettingRepositoryInterface;
use App\Domain\ProblemQuiz\ProblemQuizRepositoryInterface;
use App\Domain\DailyLog\DailyLogRepositoryInterface;
use App\Domain\ExamSession\ExamSessionRepositoryInterface;
use App\Domain\Material\MaterialRepositoryInterface;
use App\Domain\MonthlySetting\MonthlySettingRepositoryInterface;
use App\Domain\Problem\FlashcardSelectionStrategyInterface;
use App\Domain\Problem\ProblemRepositoryInterface;
use App\Domain\Problem\RandomFlashcardSelectionStrategy;
use App\Domain\StudySession\StudySessionRepositoryInterface;
use App\Domain\SubCategory\SubCategoryRepositoryInterface;
use App\Domain\Practice\PracticeSessionDraftRepositoryInterface;
use App\Domain\Practice\PracticeSessionRepositoryInterface;
use App\Domain\Stopwatch\StopwatchRepositoryInterface;
use App\Domain\Subject\SubjectRepositoryInterface;
use App\Domain\Analysis\AnalysisProblemRepositoryInterface;
use App\Domain\Sprint\SprintRepositoryInterface;
use App\Domain\StudyTicket\StudyTicketRepositoryInterface;
use App\Domain\TicketNote\TicketNoteRepositoryInterface;
use App\Infrastructure\Repositories\EloquentAlertSettingRepository;
use App\Infrastructure\Repositories\EloquentSubjectAlertSettingRepository;
use App\Infrastructure\Repositories\EloquentProblemQuizRepository;
use App\Infrastructure\Repositories\EloquentAnalysisProblemRepository;
use App\Infrastructure\Repositories\EloquentPracticeSessionDraftRepository;
use App\Infrastructure\Repositories\EloquentPracticeSessionRepository;
use App\Infrastructure\Repositories\EloquentStopwatchRepository;
use App\Infrastructure\Repositories\EloquentDailyLogRepository;
use App\Infrastructure\Repositories\EloquentExamSessionRepository;
use App\Infrastructure\Repositories\EloquentMaterialRepository;
use App\Infrastructure\Repositories\EloquentMonthlySettingRepository;
use App\Infrastructure\Repositories\EloquentProblemRepository;
use App\Infrastructure\Repositories\EloquentStudySessionRepository;
use App\Infrastructure\Repositories\EloquentSubCategoryRepository;
use App\Infrastructure\Repositories\EloquentSubjectRepository;
use App\Infrastructure\Repositories\EloquentSprintRepository;
use App\Infrastructure\Repositories\EloquentStudyTicketRepository;
use App\Infrastructure\Repositories\EloquentTicketNoteRepository;
use Illuminate\Support\ServiceProvider;

class RepositoryServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(AlertSettingRepositoryInterface::class, EloquentAlertSettingRepository::class);
        $this->app->bind(SubjectAlertSettingRepositoryInterface::class, EloquentSubjectAlertSettingRepository::class);
        $this->app->bind(ProblemQuizRepositoryInterface::class, EloquentProblemQuizRepository::class);
        $this->app->bind(AnalysisProblemRepositoryInterface::class, EloquentAnalysisProblemRepository::class);
        $this->app->bind(SubjectRepositoryInterface::class, EloquentSubjectRepository::class);
        $this->app->bind(MaterialRepositoryInterface::class, EloquentMaterialRepository::class);
        $this->app->bind(SubCategoryRepositoryInterface::class, EloquentSubCategoryRepository::class);
        $this->app->bind(MonthlySettingRepositoryInterface::class, EloquentMonthlySettingRepository::class);
        $this->app->bind(DailyLogRepositoryInterface::class, EloquentDailyLogRepository::class);
        $this->app->bind(StudySessionRepositoryInterface::class, EloquentStudySessionRepository::class);
        $this->app->bind(ProblemRepositoryInterface::class, EloquentProblemRepository::class);
        $this->app->bind(FlashcardSelectionStrategyInterface::class, RandomFlashcardSelectionStrategy::class);
        $this->app->bind(ExamSessionRepositoryInterface::class, EloquentExamSessionRepository::class);
        $this->app->bind(StopwatchRepositoryInterface::class, EloquentStopwatchRepository::class);
        $this->app->bind(PracticeSessionRepositoryInterface::class, EloquentPracticeSessionRepository::class);
        $this->app->bind(PracticeSessionDraftRepositoryInterface::class, EloquentPracticeSessionDraftRepository::class);
        $this->app->bind(SprintRepositoryInterface::class, EloquentSprintRepository::class);
        $this->app->bind(StudyTicketRepositoryInterface::class, EloquentStudyTicketRepository::class);
        $this->app->bind(TicketNoteRepositoryInterface::class, EloquentTicketNoteRepository::class);
    }
}
