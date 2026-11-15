{{-- Summary of the errors at the top of a step, linked to the fields. It
     takes the focus after a failed submit, so screen reader users hear what
     went wrong (WCAG 3.3.1). --}}
@if ($errors->any())
    <div class="alert alert--error" role="alert" tabindex="-1" data-error-summary>
        <p class="alert__title">{{ __('registration_form.errors.title') }}</p>
        <ul>
            @foreach ($errors->getMessages() as $field => $messages)
                <li>
                    @php($id = 'field-'.str_replace('.', '-', $field))
                    <a href="#{{ $id }}">{{ $messages[0] }}</a>
                </li>
            @endforeach
        </ul>
    </div>
@endif
