{{-- Confirmation mail (W-15), HTML part. Plain inline markup: mail clients
     ignore stylesheets, and no external resource is loaded (specification 9). --}}
@php($money = fn (int $cents): string => \Illuminate\Support\Number::currency($cents / 100, in: 'EUR', locale: 'de'))
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <title>{{ $race->name }}</title>
</head>
<body style="font-family: system-ui, sans-serif; line-height: 1.5; color: #1b1b18;">
    @if ($isTest)
        <p style="padding: 0.5rem; border: 2px solid #b42318; color: #b42318;"><strong>{{ __('registration_form.test.mail') }}</strong></p>
    @endif

    <p>{{ __('registration_form.mail.greeting', ['name' => $registration->contact_first_name]) }}</p>

    <p>{{ __('registration_form.mail.received', ['race' => $race->name, 'edition' => $edition->titleWithNumber()]) }}</p>

    @if ($reserved)
        <p>{{ __('registration_form.mail.reserved', ['date' => $registration->reserved_until?->format('d.m.Y, H:i').' Uhr']) }}</p>
    @else
        <p>{{ __('registration_form.mail.confirmed') }}</p>
    @endif

    <p><strong>{{ __('registration_form.mail.participants') }}:</strong></p>
    <ul>
        @foreach ($registration->participants as $person)
            <li>{{ $person->fullName() }}</li>
        @endforeach
    </ul>

    @if ($registration->options->isNotEmpty())
        <ul>
            @foreach ($registration->options as $booked)
                <li>{{ $booked->option->name }}@if ($booked->variant) ({{ $booked->variant->name }})@endif</li>
            @endforeach
        </ul>
    @endif

    <p><strong>{{ __('registration_form.mail.total') }}:</strong> {{ $money($registration->amount_cents) }}</p>

    <p>{{ __('registration_form.mail.link') }}<br>
        <a href="{{ $link }}">{{ $link }}</a></p>

    <p style="font-size: 0.875rem; color: #5f5f58;">{{ __('registration_form.mail.link_warning') }}</p>

    <p>{{ __('registration_form.mail.closing') }}<br>{{ $edition->competition->name }}</p>
</body>
</html>
