<?php

namespace App\Http\Controllers;

use App\Domain\Competition\Enums\RegistrationStatus;
use App\Domain\Competition\Models\Registration;
use App\Domain\Competition\Services\RefundCalculator;
use App\Domain\Competition\Services\RegistrationService;
use App\Support\CompetitionContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * The page behind the magic link of the confirmation mail (specification
 * W-15): participants have no account, the link is their key. For now it
 * shows the registration and lets it be cancelled; changing data and
 * transfers follow with package 2b-4.
 *
 * The token is looked up by its hash and only within the competition of the
 * request. The competition comes from the context, not from a route binding:
 * the middleware resolves it by host or slug (C-01), a binding would look it
 * up by id. The page is kept out of search engines and rate limited.
 */
class ManageRegistrationController extends Controller
{
    public function __construct(
        private readonly RegistrationService $registrations,
        private readonly RefundCalculator $refunds,
        private readonly CompetitionContext $context,
    ) {}

    public function show(Request $request): Response
    {
        $token = $this->token($request);
        $registration = $this->find($token);

        return response()
            ->view('registration.manage', [
                'registration' => $registration,
                'token' => $token,
                'cancellable' => $this->isCancellable($registration),
                'refund' => $this->refunds->refundFor($registration->race->edition, $registration->amount_cents, now()),
            ])
            ->header('X-Robots-Tag', 'noindex, nofollow')
            ->header('Referrer-Policy', 'no-referrer');
    }

    public function cancel(Request $request): RedirectResponse
    {
        $token = $this->token($request);
        $registration = $this->find($token);

        $request->validate(['confirm' => ['accepted']], [], [
            'confirm' => __('registration_form.manage.cancel_confirm'),
        ]);

        if (! $this->isCancellable($registration)) {
            return back()->withErrors(['registration' => __('registration_form.manage.not_cancellable')]);
        }

        $this->registrations->cancel($registration);

        return redirect()
            ->route('competition.registration.manage', ['token' => $token])
            ->with('status', __('registration_form.manage.cancelled'));
    }

    /**
     * Read by name: Laravel hands untyped route parameters to the action by
     * position, so a plain string argument would receive the competition.
     */
    private function token(Request $request): string
    {
        $token = $request->route('token');

        return is_string($token) ? $token : '';
    }

    private function find(string $token): Registration
    {
        $competition = $this->context->requireCompetition();

        return Registration::query()
            ->where('token_hash', hash('sha256', $token))
            ->whereHas('race.edition', fn ($query) => $query->where('competition_id', $competition->id))
            ->with(['race.edition.cancellationRules', 'participants', 'options.option', 'options.variant', 'priceTier'])
            ->firstOrFail();
    }

    private function isCancellable(Registration $registration): bool
    {
        return in_array($registration->status, [RegistrationStatus::Reserved, RegistrationStatus::Confirmed], true)
            && ! $registration->race->edition->isClosed();
    }
}
