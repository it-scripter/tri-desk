@extends('layouts.app')

@inject('availability', 'App\Domain\Competition\Services\RegistrationAvailability')

@section('title', $competition->name)
@section('heading', $competition->name)
@section('subheading', $edition ? $edition->titleWithNumber() : __('competition.home.no_edition'))

@section('content')
    @if (! $edition)
        <p class="notice">{{ __('competition.home.no_edition_published') }}</p>
    @else
        <p class="meta">
            {{ __('competition.home.status', ['status' => $edition->status->label()]) }}
            @if ($edition->starts_on)
                &middot; {{ $edition->starts_on->format('d.m.Y') }}
            @endif
        </p>

        {{-- Same answer as the form: offered only where the form accepts it
             (W-10). --}}
        @php($openRaces = $availability->openRaces($edition))

        @if ($openRaces->isNotEmpty())
            <p><a class="button" href="{{ route('competition.registration.race') }}">{{ __('competition.home.register') }}</a></p>
        @endif

        @if ($edition->races->isEmpty())
            <p class="notice">{{ __('competition.home.no_races') }}</p>
        @else
            <h2>{{ __('competition.home.races') }}</h2>
            <ul class="card-list">
                @foreach ($edition->races as $race)
                    <li class="card">
                        <h3>{{ $race->name }}</h3>
                        <p class="meta">
                            {{ $race->disciplineSequenceLabel() }}
                            &middot; {{ $race->type->label() }}
                            @if ($race->editionDay)
                                &middot; {{ $race->editionDay->title() }}
                            @endif
                        </p>

                        @if ($openRaces->contains('id', $race->id))
                            @if ($availability->isSoldOut($race))
                                <p class="badge">{{ __('registration_form.race.sold_out') }}</p>
                            @else
                                <p>
                                    <a href="{{ route('competition.registration.race', ['wettbewerb' => $race->slug]) }}">
                                        {{ __('competition.home.register_race') }}<span class="visually-hidden">: {{ $race->name }}</span>
                                    </a>
                                </p>
                            @endif
                        @endif
                    </li>
                @endforeach
            </ul>
        @endif
    @endif

    <p class="meta"><a href="{{ route('portal.home') }}">{{ __('competition.home.back') }}</a></p>
@endsection
