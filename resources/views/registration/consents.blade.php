@extends('layouts.app')

@section('title', __('registration_form.steps.consents').' – '.$race->name)
@section('heading', __('registration_form.title'))
@section('subheading', $race->name.' – '.$race->edition->titleWithNumber())

@section('content')
    @include('registration.partials.steps', ['current' => 'consents', 'hasOptions' => $hasOptions])
    @include('registration.partials.errors')

    @if ($rules->isNotEmpty())
        <section class="card">
            <h2>{{ __('registration_form.consents.cancellation') }}</h2>
            <ul>
                @foreach ($rules as $rule)
                    <li>
                        {{ __('registration_form.consents.cancellation_step', ['date' => $rule->valid_until->format('d.m.Y'), 'percent' => $rule->refund_percent]) }}
                        @if ($rule->fee_cents > 0)
                            {{ __('registration_form.consents.cancellation_fee', ['fee' => \Illuminate\Support\Number::currency($rule->fee_cents / 100, in: 'EUR', locale: 'de')]) }}
                        @endif
                    </li>
                @endforeach
            </ul>
            <p class="meta">{{ __('registration_form.consents.cancellation_none') }}</p>
        </section>
    @endif

    <form class="form" method="post" action="{{ route('competition.registration.consents.store') }}" novalidate>
        @csrf

        <fieldset class="form__group" id="field-consents">
            <legend>{{ __('registration_form.steps.consents') }}</legend>

            @foreach (['terms', 'privacy'] as $consent)
                <div class="field field--check @error($consent) field--invalid @enderror">
                    <input id="field-{{ $consent }}" type="checkbox" name="{{ $consent }}" value="1" required
                        @checked(old($consent, $draft->get("consents.{$consent}")))
                        @error($consent) aria-invalid="true" aria-describedby="field-{{ $consent }}-error" @enderror>
                    <label for="field-{{ $consent }}">
                        {{ __("registration_form.consents.{$consent}.label") }}
                        <span class="field__required" aria-hidden="true">*</span>
                    </label>
                    @error($consent)
                        <p class="field__error" id="field-{{ $consent }}-error">{{ $message }}</p>
                    @enderror
                </div>
            @endforeach

            <div class="field field--check">
                <input type="hidden" name="hide_name" value="0">
                <input id="field-hide_name" type="checkbox" name="hide_name" value="1" aria-describedby="field-hide_name-hint"
                    @checked(old('hide_name', $draft->get('consents.hide_name')))>
                <label for="field-hide_name">{{ __('registration_form.consents.hide_name.label') }}</label>
                <p class="field__hint" id="field-hide_name-hint">{{ __('registration_form.consents.hide_name.hint') }}</p>
            </div>
        </fieldset>

        <div class="form__actions">
            <a class="button button--secondary" href="{{ route($hasOptions ? 'competition.registration.options' : 'competition.registration.people') }}">{{ __('registration_form.navigation.back') }}</a>
            <button class="button" type="submit">{{ __('registration_form.navigation.next') }}</button>
        </div>
    </form>
@endsection
