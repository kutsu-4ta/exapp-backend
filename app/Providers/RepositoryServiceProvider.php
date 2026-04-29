<?php

namespace App\Providers;

use App\Domain\DailyLog\DailyLogRepositoryInterface;
use App\Domain\MonthlySetting\MonthlySettingRepositoryInterface;
use App\Domain\Problem\ProblemRepositoryInterface;
use App\Domain\StudySession\StudySessionRepositoryInterface;
use App\Domain\SubCategory\SubCategoryRepositoryInterface;
use App\Domain\Subject\SubjectRepositoryInterface;
use App\Infrastructure\Repositories\EloquentDailyLogRepository;
use App\Infrastructure\Repositories\EloquentMonthlySettingRepository;
use App\Infrastructure\Repositories\EloquentProblemRepository;
use App\Infrastructure\Repositories\EloquentStudySessionRepository;
use App\Infrastructure\Repositories\EloquentSubCategoryRepository;
use App\Infrastructure\Repositories\EloquentSubjectRepository;
use Illuminate\Support\ServiceProvider;

class RepositoryServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(SubjectRepositoryInterface::class, EloquentSubjectRepository::class);
        $this->app->bind(SubCategoryRepositoryInterface::class, EloquentSubCategoryRepository::class);
        $this->app->bind(MonthlySettingRepositoryInterface::class, EloquentMonthlySettingRepository::class);
        $this->app->bind(DailyLogRepositoryInterface::class, EloquentDailyLogRepository::class);
        $this->app->bind(StudySessionRepositoryInterface::class, EloquentStudySessionRepository::class);
        $this->app->bind(ProblemRepositoryInterface::class, EloquentProblemRepository::class);
    }
}
