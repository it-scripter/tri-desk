{{-- One labelled input with its hint and error, wired up for assistive
     technology: the id follows the field name, so links of the error summary
     land here (WCAG 1.3.1, 3.3.1, 3.3.2).

     Parameters: name (dot notation), label, type, value, required, hint,
     choices (for select), autocomplete. --}}
@php
    $id = 'field-'.str_replace('.', '-', $name);
    $inputName = collect(explode('.', $name))->map(fn ($part, $i) => $i === 0 ? $part : "[{$part}]")->implode('');
    $error = $errors->first($name);
    $describedBy = trim(($hint ?? null ? $id.'-hint ' : '').($error ? $id.'-error' : ''));
    $type ??= 'text';
    $required ??= false;
@endphp

<div class="field @if ($error) field--invalid @endif">
    <label class="field__label" for="{{ $id }}">
        {{ $label }}
        @if ($required)
            <span class="field__required" aria-hidden="true">*</span>
        @endif
    </label>

    @if (! empty($hint))
        <p class="field__hint" id="{{ $id }}-hint">{{ $hint }}</p>
    @endif

    @if ($type === 'select')
        <select class="field__input" id="{{ $id }}" name="{{ $inputName }}"
            @if ($required) required @endif
            @if ($error) aria-invalid="true" @endif
            @if ($describedBy) aria-describedby="{{ $describedBy }}" @endif>
            <option value="">{{ __('registration_form.options.variant_placeholder') }}</option>
            @foreach ($choices as $choiceValue => $choiceLabel)
                <option value="{{ $choiceValue }}" @selected((string) $value === (string) $choiceValue)>{{ $choiceLabel }}</option>
            @endforeach
        </select>
    @else
        <input class="field__input" id="{{ $id }}" name="{{ $inputName }}" type="{{ $type }}"
            value="{{ $value }}"
            @if ($required) required @endif
            @if (! empty($autocomplete)) autocomplete="{{ $autocomplete }}" @endif
            @if ($error) aria-invalid="true" @endif
            @if ($describedBy) aria-describedby="{{ $describedBy }}" @endif>
    @endif

    @if ($error)
        <p class="field__error" id="{{ $id }}-error">{{ $error }}</p>
    @endif
</div>
