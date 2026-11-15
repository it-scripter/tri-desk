<?php

namespace App\Http\Middleware;

use App\Http\Registration\RegistrationTestMode;
use App\Support\CompetitionContext;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

/**
 * Applies the test mode to the registration form: the edition under test
 * replaces the current edition in the context, and every page learns that it
 * runs as a test (banner). Runs after the competition middleware.
 */
class ApplyRegistrationTestMode
{
    public function __construct(
        private readonly CompetitionContext $context,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $test = RegistrationTestMode::resolve($request, $this->context->requireCompetition());

        if ($test !== null) {
            $this->context->setEdition($test->edition);
            View::share('edition', $test->edition);
        }

        $request->attributes->set(RegistrationTestMode::class, $test);
        View::share('testMode', $test);

        return $next($request);
    }
}
