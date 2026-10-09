@extends('layouts.app')

@section('title', $competition->name)
@section('heading', $competition->name)
@section('subheading', $edition ? $edition->titleWithNumber() : 'Keine aktuelle Ausgabe')

@section('content')
    @if (! $edition)
        <p class="notice">Für diesen Wettkampf ist noch keine Ausgabe veröffentlicht.</p>
    @else
        <p class="meta">
            Status: {{ $edition->status->label() }}
            @if ($edition->starts_on)
                &middot; {{ $edition->starts_on->format('d.m.Y') }}
            @endif
        </p>

        @if ($edition->races->isEmpty())
            <p class="notice">Die Wettbewerbe dieser Ausgabe stehen noch nicht fest.</p>
        @else
            <h2>Wettbewerbe</h2>
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
                    </li>
                @endforeach
            </ul>
        @endif
    @endif

    <p class="meta"><a href="{{ route('portal.home') }}">Zurück zur Übersicht</a></p>
@endsection
