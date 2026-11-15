<?php

namespace App\Http\Registration;

use App\Domain\Competition\Models\Competition;
use App\Domain\Competition\Models\Edition;
use App\Models\User;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Contracts\Session\Session;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/**
 * The test mode of the public registration form: the orga team plays the
 * form through before registration opens, at a moment of its choice, for an
 * edition that need not be the current one yet.
 *
 * It is started from the orga panel in a new tab and lives in the session
 * the panel and the public pages share. On every request it is checked again: the person
 * must still be signed in and still allowed to manage the registrations of
 * that edition - otherwise the test mode ends silently.
 */
final readonly class RegistrationTestMode
{
    private const KEY = 'registration_test';

    private function __construct(
        public Edition $edition,
        public CarbonImmutable $at,
    ) {}

    public static function start(Session $session, Edition $edition, CarbonInterface $at, User $user): void
    {
        $session->put(self::key($edition->competition_id), [
            'edition_id' => $edition->id,
            'at' => $at->getTimestamp(),
            'user_id' => $user->id,
        ]);

        // A draft of the real form must not be continued as a test, nor
        // the other way round.
        $session->forget('registration_draft.'.$edition->competition_id);
    }

    /**
     * Moves the moment under test; the rest of the test stays as it is.
     */
    public function moveTo(Session $session, CarbonInterface $at): void
    {
        $key = self::key($this->edition->competition_id);
        $stored = $session->get($key);

        if (is_array($stored)) {
            $session->put($key, array_replace($stored, ['at' => $at->getTimestamp()]));
        }
    }

    public static function end(Session $session, Competition $competition): void
    {
        $session->forget(self::key($competition->id));
        $session->forget('registration_draft.'.$competition->id);
    }

    public static function resolve(Request $request, Competition $competition): ?self
    {
        $session = $request->session();
        $stored = $session->get(self::key($competition->id));

        if (! is_array($stored)) {
            return null;
        }

        $user = $request->user();
        $edition = Edition::query()
            ->where('competition_id', $competition->id)
            ->find($stored['edition_id'] ?? null);

        $valid = $user instanceof User
            && $user->id === ($stored['user_id'] ?? null)
            && $edition instanceof Edition
            && Gate::forUser($user)->allows('testRegistration', $edition)
            && is_int($stored['at'] ?? null);

        if (! $valid) {
            self::end($session, $competition);

            return null;
        }

        return new self($edition, CarbonImmutable::createFromTimestamp($stored['at'], config('app.timezone')));
    }

    private static function key(int $competitionId): string
    {
        return self::KEY.'.'.$competitionId;
    }
}
