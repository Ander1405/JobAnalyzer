<?php

namespace App\Providers;

use App\Models\TrackedJob;
use App\Models\User;
use App\Observers\TrackedJobObserver;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\DevCommands;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
        $this->configureAnalysisWorkers();

        Gate::before(
            fn (User $user, string $ability): ?bool => $user->hasRole('admin') ? true : null,
        );

        TrackedJob::observe(TrackedJobObserver::class);
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }

    /**
     * `composer dev` only starts one `queue:listen` process, so AnalyzeJobListing
     * jobs (queue "analysis") ran one at a time no matter how many were pending —
     * the AI call, not the queue, was the bottleneck per job, so more workers
     * means more analyses in flight at once. `queue:listen` reloads code on every
     * job (slow) and can't target a specific queue count like this; `queue:work`
     * processes are what actually run in parallel here.
     */
    protected function configureAnalysisWorkers(): void
    {
        $workers = (int) config('jobhunter.analysis_workers');

        for ($i = 1; $i <= $workers; $i++) {
            DevCommands::artisan(
                'queue:work --queue=analysis --tries=1 --timeout=0',
                "analysis-{$i}",
            );
        }
    }
}
