@extends('layouts.app')

@section('title', __('registration_form.title').' – '.$competition->name)
@section('heading', __('registration_form.title'))
@section('subheading', $edition?->titleWithNumber() ?? $competition->name)

@section('content')
    @include('registration.partials.steps', ['current' => 'race'])
    @include('registration.partials.errors')

    @if (! $open || $races->isEmpty())
        <p class="notice">{{ __('registration_form.race.none') }}</p>
    @else
        <p>{{ __('registration_form.race.intro') }}</p>

        <ul class="card-list">
            @foreach ($races as $race)
                <li class="card">
                    <h2>{{ $race->name }}</h2>
                    <p class="meta">
                        {{ $race->disciplineSequenceLabel() }} &middot; {{ $race->type->label() }}
                    </p>
                    @if ($tiers[$race->id])
                        <p>
                            {{ __('registration_form.race.price', ['price' => \Illuminate\Support\Number::currency($tiers[$race->id]->amount_cents / 100, in: 'EUR', locale: 'de')]) }}
                            @if ($race->isTeamRace())
                                ({{ __('registration_form.race.per_team') }})
                            @endif
                        </p>
                    @endif

                    @if ($soldOut[$race->id])
                        <p class="badge">{{ __('registration_form.race.sold_out') }}</p>
                    @else
                        <form method="post" action="{{ route('competition.registration.race.store') }}">
                            @csrf
                            <input type="hidden" name="race" value="{{ $race->id }}">
                            <button class="button" type="submit">
                                {{ __('registration_form.race.register') }}<span class="visually-hidden">: {{ $race->name }}</span>
                            </button>
                        </form>
                    @endif
                </li>
            @endforeach
        </ul>
    @endif

    <p class="meta"><a href="{{ route('competition.home') }}">{{ __('registration_form.navigation.back') }}</a></p>
@endsection
