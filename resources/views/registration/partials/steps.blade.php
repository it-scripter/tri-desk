{{-- Progress of the registration form (W-10). The options step is left out
     where the race offers none, so the count matches what people see. --}}
@php
    $steps = ['race', 'people'];
    if ($hasOptions ?? true) {
        $steps[] = 'options';
    }
    $steps[] = 'consents';
    $steps[] = 'summary';
    $position = array_search($current, $steps, true) + 1;
@endphp

<nav class="steps" aria-label="{{ __('registration_form.title') }}">
    <p class="steps__status">{{ __('registration_form.progress', ['current' => $position, 'total' => count($steps)]) }}</p>
    <ol class="steps__list">
        @foreach ($steps as $index => $step)
            <li class="steps__item @if ($step === $current) steps__item--current @elseif ($index + 1 < $position) steps__item--done @endif"
                @if ($step === $current) aria-current="step" @endif>
                {{ __('registration_form.steps.'.$step) }}
            </li>
        @endforeach
    </ol>
</nav>
