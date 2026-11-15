@if ($isTest)
*** {{ __('registration_form.test.mail') }} ***

@endif
{{ __('registration_form.mail.greeting', ['name' => $registration->contact_first_name]) }}

{{ __('registration_form.mail.received', ['race' => $race->name, 'edition' => $edition->titleWithNumber()]) }}

@if ($reserved)
{{ __('registration_form.mail.reserved', ['date' => $registration->reserved_until?->format('d.m.Y, H:i').' Uhr']) }}
@else
{{ __('registration_form.mail.confirmed') }}
@endif

{{ __('registration_form.mail.participants') }}:
@foreach ($registration->participants as $person)
- {{ $person->fullName() }}
@endforeach
@foreach ($registration->options as $booked)
- {{ $booked->option->name }}@if ($booked->variant) ({{ $booked->variant->name }})@endif

@endforeach

{{ __('registration_form.mail.total') }}: {{ \Illuminate\Support\Number::currency($registration->amount_cents / 100, in: 'EUR', locale: 'de') }}

{{ __('registration_form.mail.link') }}
{{ $link }}

{{ __('registration_form.mail.link_warning') }}

{{ __('registration_form.mail.closing') }}
{{ $edition->competition->name }}
