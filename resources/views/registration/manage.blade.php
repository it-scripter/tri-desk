@extends('layouts.app')

@php($money = fn (int $cents): string => \Illuminate\Support\Number::currency($cents / 100, in: 'EUR', locale: 'de'))

@push('head')
    <meta name="robots" content="noindex, nofollow">
@endpush

@section('title', __('registration_form.manage.title'))
@section('heading', __('registration_form.manage.title'))
@section('subheading', $registration->race->name.' – '.$registration->race->edition->titleWithNumber())

@section('content')
    @if (session('status'))
        <div class="alert alert--success" role="status"><p>{{ session('status') }}</p></div>
    @endif
    @include('registration.partials.errors')

    @if ($registration->is_test)
        <p class="alert alert--warning" role="status">{{ __('registration_form.test.badge') }}</p>
    @endif

    <section class="card">
        <dl class="facts">
            <dt>{{ __('registration_form.manage.status') }}</dt>
            <dd>
                {{ $registration->status->label() }}
                @if ($registration->status === \App\Domain\Competition\Enums\RegistrationStatus::Reserved && $registration->reserved_until)
                    – {{ __('registration_form.manage.reserved_until', ['date' => $registration->reserved_until->format('d.m.Y, H:i').' Uhr']) }}
                @endif
            </dd>
            @if ($registration->team_name)
                <dt>{{ __('registration_form.people.team_name') }}</dt>
                <dd>{{ $registration->team_name }}</dd>
            @endif
            <dt>{{ __('registration_form.summary.total') }}</dt>
            <dd>{{ $money($registration->amount_cents) }}</dd>
        </dl>

        <h2>{{ __('registration_form.steps.people') }}</h2>
        <ul>
            @foreach ($registration->participants as $person)
                <li>{{ $person->fullName() }}, {{ $person->birth_date->format('d.m.Y') }}</li>
            @endforeach
        </ul>

        @if ($registration->options->isNotEmpty())
            <h2>{{ __('registration_form.steps.options') }}</h2>
            <ul>
                @foreach ($registration->options as $booked)
                    <li>{{ $booked->option->name }}@if ($booked->variant) ({{ $booked->variant->name }})@endif – {{ $money($booked->price_cents) }}</li>
                @endforeach
            </ul>
        @endif
    </section>

    <p class="meta">{{ __('registration_form.manage.changes_later') }}</p>

    @if ($cancellable)
        <section class="card">
            <h2>{{ __('registration_form.manage.cancel') }}</h2>
            <p>
                @if ($refund > 0)
                    {{ __('registration_form.manage.cancel_refund', ['amount' => $money($refund)]) }}
                @else
                    {{ __('registration_form.manage.cancel_no_refund') }}
                @endif
            </p>

            <form class="form" method="post" action="{{ route('competition.registration.manage.cancel', ['token' => $token]) }}">
                @csrf
                <div class="field field--check @error('confirm') field--invalid @enderror">
                    <input id="field-confirm" type="checkbox" name="confirm" value="1" required
                        @error('confirm') aria-invalid="true" aria-describedby="field-confirm-error" @enderror>
                    <label for="field-confirm">{{ __('registration_form.manage.cancel_confirm') }}</label>
                    @error('confirm')
                        <p class="field__error" id="field-confirm-error">{{ $message }}</p>
                    @enderror
                </div>
                <div class="form__actions">
                    <button class="button button--danger" type="submit">{{ __('registration_form.manage.cancel') }}</button>
                </div>
            </form>
        </section>
    @endif
@endsection
