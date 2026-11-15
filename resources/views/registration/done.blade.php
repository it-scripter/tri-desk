@extends('layouts.app')

@section('title', __('registration_form.done.title'))
@section('heading', __('registration_form.done.title'))
@section('subheading', $registration->race->name)

@section('content')
    <div class="alert alert--success" role="status">
        @if ($registration->status === \App\Domain\Competition\Enums\RegistrationStatus::Reserved)
            <p>{{ __('registration_form.done.reserved', ['date' => $registration->reserved_until?->format('d.m.Y, H:i').' Uhr']) }}</p>
        @else
            <p>{{ __('registration_form.done.confirmed') }}</p>
        @endif
        <p>{{ __('registration_form.done.mail', ['email' => $registration->contact_email]) }}</p>
    </div>

    <p class="meta"><a href="{{ route('competition.home') }}">{{ $competition->name }}</a></p>
@endsection
