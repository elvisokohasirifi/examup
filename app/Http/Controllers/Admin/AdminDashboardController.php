<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\SuspiciousActivity;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\View\View;

class AdminDashboardController extends Controller
{
    public function __invoke(): View
    {
        /** @var User $user */
        $user = backpack_user();

        return view('admin.dashboard', [
            'totalExams' => $this->scopedExams($user)->count(),
            'publishedExams' => $this->scopedExams($user)
                ->where('is_published', true)
                ->where(function (Builder $query): void {
                    $query->whereNull('expires_at')->orWhere('expires_at', '>', now());
                })
                ->count(),
            'activeAttempts' => $this->scopedAttempts($user)
                ->where('status', ExamAttempt::STATUS_IN_PROGRESS)
                ->count(),
            'completedAttempts' => $this->scopedAttempts($user)
                ->current()
                ->whereIn('status', [ExamAttempt::STATUS_SUBMITTED, ExamAttempt::STATUS_AUTO_SUBMITTED])
                ->count(),
            'securityEvents' => SuspiciousActivity::query()
                ->where('created_at', '>=', now()->subDays(30))
                ->whereHas('attempt', fn (Builder $query) => $query->whereHas(
                    'exam',
                    fn (Builder $examQuery) => $this->scopeExamQuery($examQuery, $user),
                ))
                ->count(),
            'recentAttempts' => $this->scopedAttempts($user)
                ->with('exam:id,title')
                ->current()
                ->whereIn('status', [ExamAttempt::STATUS_SUBMITTED, ExamAttempt::STATUS_AUTO_SUBMITTED])
                ->latest('submitted_at')
                ->limit(6)
                ->get(),
        ]);
    }

    /**
     * @return Builder<Exam>
     */
    private function scopedExams(User $user): Builder
    {
        $query = Exam::query();

        $this->scopeExamQuery($query, $user);

        return $query;
    }

    /**
     * @return Builder<ExamAttempt>
     */
    private function scopedAttempts(User $user): Builder
    {
        return ExamAttempt::query()->whereHas(
            'exam',
            fn (Builder $query) => $this->scopeExamQuery($query, $user),
        );
    }

    /**
     * @param  Builder<Exam>  $query
     */
    private function scopeExamQuery(Builder $query, User $user): void
    {
        if ($user->isExaminer()) {
            $query->where('created_by', $user->id);
        }
    }
}
