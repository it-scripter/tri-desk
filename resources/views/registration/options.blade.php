@extends('layouts.app')

@section('title', __('registration_form.steps.options').' – '.$race->name)
@section('heading', __('registration_form.title'))
@section('subheading', $race->name.' – '.$race->edition->titleWithNumber())

@section('content')
    @include('registration.partials.steps', ['current' => 'options'])
    @include('registration.partials.errors')

    <form class="form" method="post" action="{{ route('competition.registration.options.store') }}" novalidate>
        @csrf

        <fieldset class="form__group" id="field-options">
            <legend>{{ __('registration_form.options.intro') }}</legend>

            @foreach ($options as $option)
                @php($chosen = $draft->get('options', []))
                <div class="option">
                    <div class="field field--check">
                        <input type="hidden" name="options[{{ $option->id }}][selected]" value="0">
                        <input id="option-{{ $option->id }}" type="checkbox" name="options[{{ $option->id }}][selected]" value="1"
                            @checked(old("options.{$option->id}.selected", array_key_exists($option->id, $chosen)))>
                        <label for="option-{{ $option->id }}">
                            {{ $option->name }}
                            ({{ \Illuminate\Support\Number::currency($option->price_cents / 100, in: 'EUR', locale: 'de') }})
                        </label>
                    </div>

                    @if ($option->description)
                        <p class="field__hint">{{ $option->description }}</p>
                    @endif

                    @if ($option->variants->isNotEmpty())
                        <div class="field">
                            <label class="field__label" for="option-{{ $option->id }}-variant">{{ __('registration_form.options.variant') }}: {{ $option->name }}</label>
                            <select class="field__input" id="option-{{ $option->id }}-variant" name="options[{{ $option->id }}][variant]">
                                <option value="">{{ __('registration_form.options.variant_placeholder') }}</option>
                                @foreach ($option->variants as $variant)
                                    <option value="{{ $variant->id }}" @selected((int) old("options.{$option->id}.variant", $chosen[$option->id] ?? 0) === $variant->id)>{{ $variant->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endif
                </div>
            @endforeach
        </fieldset>

        <div class="form__actions">
            <a class="button button--secondary" href="{{ route('competition.registration.people') }}">{{ __('registration_form.navigation.back') }}</a>
            <button class="button" type="submit">{{ __('registration_form.navigation.next') }}</button>
        </div>
    </form>
@endsection
