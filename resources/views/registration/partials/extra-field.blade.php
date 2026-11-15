{{-- An additional field of the race (W-11), rendered by its type. --}}
@php
    $type = match ($field->type) {
        \App\Domain\Competition\Enums\RaceFieldType::Number => 'number',
        \App\Domain\Competition\Enums\RaceFieldType::Email => 'email',
        \App\Domain\Competition\Enums\RaceFieldType::Phone => 'tel',
        \App\Domain\Competition\Enums\RaceFieldType::Choice => 'select',
        default => 'text',
    };
@endphp

@include('registration.partials.field', [
    'name' => $name,
    'label' => $field->label,
    'type' => $type,
    'hint' => $field->help,
    'value' => old($name, $draft->get($name)),
    'required' => $field->is_required && $mandatory,
    'choices' => collect($field->choices ?? [])->mapWithKeys(fn ($c) => [$c => $c])->all(),
])
