<?php

namespace App\Providers;

use App\Domain\DailyLog\DailyLogRepositoryInterface;
use App\Domain\Problem\ProblemRepositoryInterface;
use App\Domain\StudySession\StudySessionRepositoryInterface;
use App\Domain\Subject\SubjectRepositoryInterface;
use App\Infrastructure\Repositories\EloquentDailyLogRepository;
use App\Infrastructure\Repositories\EloquentProblemRepository;
use App\Infrastructure\Repositories\EloquentStudySessionRepository;
use App\Infrastructure\Repositories\EloquentSubjectRepository;
use Illuminate\Support\ServiceProvider;

class RepositoryServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(SubjectRepositoryInterface::class, EloquentSubjectRepository::class);
        $this->app->bind(DailyLogRepositoryInterface::class, EloquentDailyLogRepository::class);
        $this->app->bind(StudySessionRepositoryInterface::class, EloquentStudySessionRepository::class);
        $this->app->bind(ProblemRepositoryInterface::class, EloquentProblemRepository::class);
    }
}
