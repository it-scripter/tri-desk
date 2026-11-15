@extends('layouts.app')

@section('title', __('registration_form.steps.people').' – '.$race->name)
@section('heading', __('registration_form.title'))
@section('subheading', $race->name.' – '.$race->edition->titleWithNumber())

@section('content')
    @include('registration.partials.steps', ['current' => 'people', 'hasOptions' => $hasOptions])
    @include('registration.partials.errors')

    {{-- The race date lets the script show the guardian block only for
         minors (W-17); without a date it judges by birth year, like the
         server does. --}}
    <form class="form" method="post" action="{{ route('competition.registration.people.store') }}" novalidate
        @if ($race->raceDate()) data-race-date="{{ $race->raceDate()->format('Y-m-d') }}" @else data-race-year="{{ $race->edition->year }}" @endif>
        @csrf

        {{-- Honeypot: hidden from people and assistive technology alike;
             only bots fill it in. --}}
        <div class="visually-hidden" aria-hidden="true">
            <label for="website">Website</label>
            <input id="website" name="website" type="text" tabindex="-1" autocomplete="off">
        </div>

        <fieldset class="form__group">
            <legend>{{ __('registration_form.people.contact') }}</legend>
            <p class="field__hint">{{ __('registration_form.people.contact_hint') }}</p>

            @include('registration.partials.field', [
                'name' => 'contact.first_name', 'label' => __('registration_form.people.first_name'),
                'value' => old('contact.first_name', $draft->get('contact.first_name')), 'required' => true, 'autocomplete' => 'given-name',
            ])
            @include('registration.partials.field', [
                'name' => 'contact.last_name', 'label' => __('registration_form.people.last_name'),
                'value' => old('contact.last_name', $draft->get('contact.last_name')), 'required' => true, 'autocomplete' => 'family-name',
            ])
            @include('registration.partials.field', [
                'name' => 'contact.email', 'label' => __('registration_form.people.email'), 'type' => 'email',
                'value' => old('contact.email', $draft->get('contact.email')), 'required' => true, 'autocomplete' => 'email',
            ])
        </fieldset>

        @if ($race->isTeamRace())
            <fieldset class="form__group">
                <legend>{{ __('registration_form.people.team_name') }}</legend>
                @include('registration.partials.field', [
                    'name' => 'team_name', 'label' => __('registration_form.people.team_name'),
                    'value' => old('team_name', $draft->get('team_name')), 'required' => true,
                ])
            </fieldset>
        @endif

        @error('participants')
            <p class="alert alert--error" id="field-participants">{{ $message }}</p>
        @enderror

        @for ($i = 0; $i < $slots; $i++)
            @php
                $prefix = "participants.{$i}";
                $mandatory = $i < $required;
                $legend = ! $race->isTeamRace()
                    ? __('registration_form.people.person')
                    : __($mandatory ? 'registration_form.people.member' : 'registration_form.people.member_optional', ['number' => $i + 1]);
            @endphp

            <fieldset class="form__group" id="field-participants-{{ $i }}">
                <legend>{{ $legend }}</legend>

                @include('registration.partials.field', [
                    'name' => "{$prefix}.first_name", 'label' => __('registration_form.people.first_name'),
                    'value' => old("{$prefix}.first_name", $draft->get("{$prefix}.first_name")), 'required' => $mandatory,
                ])
                @include('registration.partials.field', [
                    'name' => "{$prefix}.last_name", 'label' => __('registration_form.people.last_name'),
                    'value' => old("{$prefix}.last_name", $draft->get("{$prefix}.last_name")), 'required' => $mandatory,
                ])
                @include('registration.partials.field', [
                    'name' => "{$prefix}.birth_date", 'label' => __('registration_form.people.birth_date'), 'type' => 'date',
                    'value' => old("{$prefix}.birth_date", $draft->get("{$prefix}.birth_date")), 'required' => $mandatory, 'autocomplete' => $i === 0 ? 'bday' : null,
                ])

                @if ($race->admitsMinors())
                    @include('registration.partials.guardian', ['index' => $i, 'prefix' => $prefix, 'draft' => $draft])
                @endif
                @include('registration.partials.field', [
                    'name' => "{$prefix}.gender", 'label' => __('registration_form.people.gender'), 'type' => 'select',
                    'value' => old("{$prefix}.gender", $draft->get("{$prefix}.gender")), 'required' => $mandatory,
                    'choices' => collect($genders)->mapWithKeys(fn ($g) => [$g->value => $g->label()])->all(),
                ])

                @if (in_array(\App\Domain\Competition\Enums\Gender::Diverse, $genders, true))
                    @include('registration.partials.field', [
                        'name' => "{$prefix}.scored_as", 'label' => __('registration_form.people.scored_as'), 'type' => 'select',
                        'hint' => __('registration_form.people.scored_as_hint'),
                        'value' => old("{$prefix}.scored_as", $draft->get("{$prefix}.scored_as")),
                        'choices' => [
                            \App\Domain\Competition\Enums\Gender::Male->value => \App\Domain\Competition\Enums\Gender::Male->label(),
                            \App\Domain\Competition\Enums\Gender::Female->value => \App\Domain\Competition\Enums\Gender::Female->label(),
                        ],
                    ])
                @endif

                @foreach ($participantFields as $field)
                    @include('registration.partials.extra-field', ['field' => $field, 'name' => "{$prefix}.extra.{$field->key}", 'draft' => $draft, 'mandatory' => $mandatory])
                @endforeach

            </fieldset>
        @endfor

        @if ($registrationFields->isNotEmpty())
            <fieldset class="form__group">
                <legend>{{ __('registration_form.people.details') }}</legend>
                @foreach ($registrationFields as $field)
                    @include('registration.partials.extra-field', ['field' => $field, 'name' => "extra.{$field->key}", 'draft' => $draft, 'mandatory' => true])
                @endforeach
            </fieldset>
        @endif

        <div class="form__actions">
            <a class="button button--secondary" href="{{ route('competition.registration.race') }}">{{ __('registration_form.navigation.back') }}</a>
            <button class="button" type="submit">{{ __('registration_form.navigation.next') }}</button>
        </div>
    </form>
@endsection
