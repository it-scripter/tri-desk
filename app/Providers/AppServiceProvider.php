<?php

namespace App\Providers;

use App\Models\User;
use App\Support\CompetitionContext;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
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
        Factory::guessFactoryNamesUsing($this->factoryNameFor(...));

        // Models live in domain folders and some belong to packages, so the
        // automatic App\Models -> App\Policies guess does not apply. Policies
        // stay flat in app/Policies, named after the model.
        Gate::guessPolicyNamesUsing($this->policyNameFor(...));

        // The super admin passes every ability. Returning null for everyone
        // else leaves the decision to the policies.
        Gate::before(fn (User $user): ?bool => $user->isSuperAdmin() ? true : null);

        // Rate limits of the public registration (specification 9): sending
        // the form, and the magic link page, which must not be open to
        // guessing tokens.
        RateLimiter::for('registration', fn (Request $request): Limit => Limit::perMinute(10)->by($request->ip()));
        RateLimiter::for('magic-link', fn (Request $request): Limit => Limit::perMinute(30)->by($request->ip()));
    }

    /**
     * @param  class-string<Model>  $modelName
     * @return class-string<Factory<Model>>
     */
    private function factoryNameFor(string $modelName): string
    {
        /** @var class-string<Factory<Model>> $factory */
        $factory = 'Database\\Factories\\'.class_basename($modelName).'Factory';

        return $factory;
    }

    /**
     * @param  class-string<Model>  $modelClass
     * @return class-string
     */
    private function policyNameFor(string $modelClass): string
    {
        /** @var class-string $policy */
        $policy = 'App\\Policies\\'.class_basename($modelClass).'Policy';

        return $policy;
    }
}
