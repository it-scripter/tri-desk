{{-- Shown on every page of the registration form while the test mode runs.
     The moment under test can be moved here, e.g. from early bird to the
     normal price, without starting the test anew. --}}
<div class="test-banner" role="status">
    <p>{{ __('registration_form.test.banner', ['date' => $test->at->format('d.m.Y, H:i').' Uhr']) }}</p>

    <div class="test-banner__actions">
        <form class="test-banner__move" method="post" action="{{ route('competition.registration.test.move') }}">
            @csrf
            <label for="test-at">{{ __('registration_form.test.at') }}</label>
            <input class="field__input" id="test-at" name="at" type="datetime-local"
                value="{{ $test->at->format('Y-m-d\TH:i') }}" required>
            <button class="button button--secondary" type="submit">{{ __('registration_form.test.move') }}</button>
        </form>

        <form method="post" action="{{ route('competition.registration.test.end') }}">
            @csrf
            <button class="button button--secondary" type="submit">{{ __('registration_form.test.end') }}</button>
        </form>
    </div>
</div>
