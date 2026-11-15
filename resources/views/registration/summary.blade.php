@extends('layouts.app')

@php($money = fn (int $cents): string => \Illuminate\Support\Number::currency($cents / 100, in: 'EUR', locale: 'de'))

@section('title', __('registration_form.steps.summary').' – '.$race->name)
@section('heading', __('registration_form.title'))
@section('subheading', $race->name.' – '.$race->edition->titleWithNumber())

@section('content')
    @include('registration.partials.steps', ['current' => 'summary', 'hasOptions' => $hasOptions])
    @include('registration.partials.errors')

    <p>{{ __('registration_form.summary.intro') }}</p>

    <section class="card">
        <h2>{{ __('registration_form.summary.race') }}</h2>
        <dl class="facts">
            <dt>{{ __('registration_form.summary.race') }}</dt>
            <dd>{{ $race->name }}</dd>
            @if ($data->teamName)
                <dt>{{ __('registration_form.people.team_name') }}</dt>
                <dd>{{ $data->teamName }}</dd>
            @endif
            @if ($tier)
                <dt>{{ __('registration_form.summary.price_tier') }}</dt>
                <dd>{{ $tier->name }} – {{ $money($tier->amount_cents) }}</dd>
            @endif
        </dl>
    </section>

    <section class="card">
        <h2>{{ __('registration_form.steps.people') }}</h2>
        <ul>
            @foreach ($data->participants as $person)
                <li>{{ $person->firstName }} {{ $person->lastName }}, {{ $person->birthDate->format('d.m.Y') }}, {{ $person->gender->label() }}</li>
            @endforeach
        </ul>
        <p class="meta">{{ __('registration_form.people.contact') }}: {{ $data->contactFirstName }} {{ $data->contactLastName }}, {{ $data->contactEmail }}</p>
        <p class="meta"><a href="{{ route('competition.registration.people') }}">{{ __('registration_form.summary.edit') }}</a></p>
    </section>

    @if ($data->options !== [])
        <section class="card">
            <h2>{{ __('registration_form.steps.options') }}</h2>
            <ul>
                @foreach ($data->options as $choice)
                    <li>{{ $choice->option->name }}@if ($choice->variant) ({{ $choice->variant->name }})@endif – {{ $money($choice->option->price_cents) }}</li>
                @endforeach
            </ul>
            <p class="meta"><a href="{{ route('competition.registration.options') }}">{{ __('registration_form.summary.edit') }}</a></p>
        </section>
    @endif

    @if ($total !== null)
        <p class="total">{{ __('registration_form.summary.total') }}: <strong>{{ $money($total) }}</strong></p>
    @endif

    @if ($total)
        <p class="meta">{{ __('registration_form.summary.payment_note', ['hours' => $race->edition->payment_window_hours]) }}</p>
    @endif

    <form class="form" method="post" action="{{ route('competition.registration.submit') }}">
        @csrf
        <div class="form__actions">
            <a class="button button--secondary" href="{{ route('competition.registration.consents') }}">{{ __('registration_form.navigation.back') }}</a>
            <button class="button" type="submit">{{ __('registration_form.summary.submit') }}</button>
        </div>
    </form>
@endsection
