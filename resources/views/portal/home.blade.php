@extends('layouts.app')

@section('title', config('app.name'))
@section('heading', config('app.name'))
@section('subheading', 'Wettkampf-Anmeldungen und Helferportal')

@section('content')
    @php
        // Teasers are fed from the competition data (C-03); the editable CMS
        // start page follows in phase 7.
        $competitions = \App\Domain\Competition\Models\Competition::query()
            ->where('is_active', true)
            ->orderBy('sort')
            ->orderBy('name')
            ->get();
    @endphp

    @if ($competitions->isEmpty())
        <p class="notice">Es ist noch kein Wettkampf angelegt.</p>
    @else
        <ul class="card-list">
            @foreach ($competitions as $item)
                @php($current = $item->currentEdition())
                <li class="card">
                    <h2>
                        <a href="{{ route('competition.home', $item) }}">{{ $item->name }}</a>
                    </h2>
                    <p class="meta">
                        @if ($current)
                            Aktuelle Ausgabe: {{ $current->titleWithNumber() }} ({{ $current->status->label() }})
                        @else
                            Keine aktuelle Ausgabe
                        @endif
                    </p>
                </li>
            @endforeach
        </ul>
    @endif
@endsection
