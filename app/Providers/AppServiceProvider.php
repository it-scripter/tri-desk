<?php

namespace App\Providers;

use App\Models\User;
use App\Support\CompetitionContext;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // One competition context per request, shared by middleware, views
        // and services.
        $this->app->scoped(CompetitionContext::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Models live in domain folders (app/Domain/..., app/Integrations/...),
        // factories stay flat in database/factories. Laravel would otherwise
        // look for Database\Factories\Domain\Competition\Models\EditionFactory.
        Factory::guessFactoryNamesUsing(
            fn (string $modelName): string => 'Database\\Factories\\'.class_basename($modelName).'Factory',
        );

        // Models live in domain folders and some belong to packages, so the
        // automatic App\Models -> App\Policies guess does not apply. Policies
        // stay flat in app/Policies, named after the model.
        Gate::guessPolicyNamesUsing(
            fn (string $modelClass): string => 'App\\Policies\\'.class_basename($modelClass).'Policy',
        );

        // The super admin passes every ability. Returning null for everyone
        // else leaves the decision to the policies.
        Gate::before(fn (User $user): ?bool => $user->isSuperAdmin() ? true : null);
    }
}
