<?php

namespace App\Http\Middleware;

use App\Support\CompetitionContext;
use App\Support\CompetitionResolver;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

/**
 * Puts the competition of the request into the context (specification C-01).
 *
 * The slug stays out of every link: URL::defaults makes named routes fill the
 * competition parameter on their own, so route('competition.home') is enough.
 * That is what keeps the move to own domains a one-place change later.
 */
class ResolveCompetitionContext
{
    public function __construct(
        private readonly CompetitionResolver $resolver,
        private readonly CompetitionContext $context,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $slug = $request->route('competition');
        $competition = $this->resolver->resolve($request, is_string($slug) ? $slug : null);

        abort_if($competition === null, 404);

        $this->context->set($competition);

        // Named routes inside the context no longer need the parameter.
        URL::defaults(['competition' => $competition->slug]);

        // Replace the raw slug with the model, so route parameters and
        // controller arguments receive the competition itself.
        $request->route()?->setParameter('competition', $competition);

        View::share('competition', $competition);
        View::share('edition', $this->context->edition());

        return $next($request);
    }
}
