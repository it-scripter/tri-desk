<?php

namespace App\Domain\Competition\Enums;

use Illuminate\Validation\Rule;

/**
 * Input types of an additional field (W-11). Each brings its own validation;
 * "time" is a duration such as an expected finish time (hh:mm:ss).
 */
enum RaceFieldType: string
{
    case Text = 'text';
    case Number = 'number';
    case Time = 'time';
    case Choice = 'choice';
    case Email = 'email';
    case Phone = 'phone';

    public function label(): string
    {
        return match ($this) {
            self::Text => 'Text',
            self::Number => 'Zahl',
            self::Time => 'Zeit (hh:mm:ss)',
            self::Choice => 'Auswahl',
            self::Email => 'E-Mail',
            self::Phone => 'Telefon',
        };
    }

    /**
     * Validation rules for a value of this type, without "required".
     *
     * @param  array<int, string>  $choices
     * @return array<int, mixed>
     */
    public function rules(array $choices = []): array
    {
        return match ($this) {
            self::Text => ['string', 'max:255'],
            self::Number => ['integer', 'min:0', 'max:9999999'],
            self::Time => ['string', 'regex:/^\d{1,2}:[0-5]\d(:[0-5]\d)?$/'],
            self::Choice => ['string', Rule::in($choices)],
            self::Email => ['string', 'email', 'max:255'],
            self::Phone => ['string', 'regex:/^[0-9 +()\/-]{5,30}$/'],
        };
    }

    /**
     * @return array<string, string>
     */
    public static function labels(): array
    {
        return array_combine(
            array_column(self::cases(), 'value'),
            array_map(fn (self $type): string => $type->label(), self::cases()),
        );
    }
}
