{{-- Consent of a parent or guardian (W-17), right below the birth date it
     depends on. Without JavaScript the block is always shown and the server
     decides; with it, the block appears only once the birth date makes the
     person a minor on race day. It stays open where something was entered or
     the server reported an error, so nothing filled in disappears. --}}
@php
    $nameValue = old("{$prefix}.guardian_name", $draft->get("{$prefix}.guardian_name"));
    $consentValue = old("{$prefix}.guardian_consent", $draft->get("{$prefix}.guardian_consent"));
    $keepOpen = filled($nameValue) || (bool) $consentValue || $errors->has("{$prefix}.guardian_name");
@endphp

<p class="field__hint" aria-live="polite" data-guardian-notice="field-participants-{{ $index }}-birth_date"
    data-message="{{ __('registration_form.people.guardian_needed') }}"></p>

<fieldset class="form__subgroup" data-guardian="field-participants-{{ $index }}-birth_date" @if ($keepOpen) data-keep-open @endif>
    <legend>{{ __('registration_form.people.guardian') }}</legend>
    <p class="field__hint">{{ __('registration_form.people.guardian_hint') }}</p>

    @include('registration.partials.field', [
        'name' => "{$prefix}.guardian_name", 'label' => __('registration_form.people.guardian_name'),
        'value' => $nameValue,
    ])

    <div class="field field--check">
        <input type="hidden" name="participants[{{ $index }}][guardian_consent]" value="0">
        <input id="field-participants-{{ $index }}-guardian_consent" type="checkbox"
            name="participants[{{ $index }}][guardian_consent]" value="1" @checked($consentValue)>
        <label for="field-participants-{{ $index }}-guardian_consent">{{ __('registration.guardian_consent.text') }}</label>
    </div>
</fieldset>
