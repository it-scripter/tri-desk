{{-- Shown on every page of the registration form while the test mode runs. --}}
<div class="test-banner" role="status">
    <p>{{ __('registration_form.test.banner', ['date' => $test->at->format('d.m.Y, H:i').' Uhr']) }}</p>
    <form method="post" action="{{ route('competition.registration.test.end') }}">
        @csrf
        <button class="button button--secondary" type="submit">{{ __('registration_form.test.end') }}</button>
    </form>
</div>
