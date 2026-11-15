<?php

namespace App\Http\Controllers;

use App\Domain\Competition\Models\Edition;
use App\Http\Registration\RegistrationTestMode;
use App\Models\User;
use App\Support\CompetitionContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;

/**
 * Starts, moves and ends the test mode of the registration form.
 *
 * The orga panel opens the start link in a new tab, so the panel stays where
 * it was; ending the test only has to say that the tab may be closed. The
 * link is signed, and the person must be signed in and allowed to manage the
 * registrations of the edition - a link from elsewhere cannot switch anyone
 * into the test mode.
 */
class RegistrationTestController extends Controller
{
    public function __construct(
        private readonly CompetitionContext $context,
    ) {}

    public function start(Request $request): RedirectResponse
    {
        $user = $request->user();
        $edition = Edition::query()
            ->where('competition_id', $this->context->requireCompetition()->id)
            ->find($request->route('edition'));

        abort_unless($user instanceof User && $edition instanceof Edition, 404);
        abort_unless(Gate::forUser($user)->allows('testRegistration', $edition), 403);

        // Start at the registration start where it lies ahead, else now.
        $at = $edition->registration_opens_at?->isFuture() ? $edition->registration_opens_at : now();

        RegistrationTestMode::start($request->session(), $edition, $at, $user);

        return redirect()->route('competition.registration.race');
    }

    /**
     * Moves the moment under test, e.g. from early bird to the normal price.
     * The draft is kept; every step judges again at the new moment.
     */
    public function move(Request $request): RedirectResponse
    {
        $test = $request->attributes->get(RegistrationTestMode::class);

        if (! $test instanceof RegistrationTestMode) {
            return redirect()->route('competition.registration.race');
        }

        $validated = $request->validate(['at' => ['required', 'date']], [], [
            'at' => __('registration_form.test.at'),
        ]);

        $test->moveTo($request->session(), Carbon::parse($validated['at']));

        return back();
    }

    public function end(Request $request): RedirectResponse
    {
        RegistrationTestMode::end($request->session(), $this->context->requireCompetition());

        return redirect()->route('competition.home')
            ->with('status', __('registration_form.test.ended'));
    }
}
