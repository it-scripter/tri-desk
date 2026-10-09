<?php

namespace App\Filament\Admin\Resources\Users\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Hash;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('user.sections.person'))->schema([
                TextInput::make('first_name')
                    ->label(__('user.fields.first_name'))
                    ->required()
                    ->maxLength(255),

                TextInput::make('last_name')
                    ->label(__('user.fields.last_name'))
                    ->required()
                    ->maxLength(255),

                TextInput::make('phone')
                    ->label(__('user.fields.phone'))
                    ->tel()
                    ->maxLength(255),

                DatePicker::make('birth_date')
                    ->label(__('user.fields.birth_date'))
                    ->displayFormat('d.m.Y')
                    ->maxDate(now()),
            ])->columns(2),

            Section::make(__('user.sections.account'))->schema([
                TextInput::make('email')
                    ->label(__('user.fields.email'))
                    ->email()
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true),

                TextInput::make('password')
                    ->label(__('user.fields.password'))
                    ->helperText(__('user.hints.password'))
                    ->password()
                    ->revealable()
                    ->minLength(8)
                    ->required(fn (string $operation): bool => $operation === 'create')
                    ->dehydrateStateUsing(fn (?string $state): ?string => filled($state) ? Hash::make($state) : null)
                    // Leaving the field empty on edit must keep the existing
                    // password, not overwrite it with null.
                    ->dehydrated(fn (?string $state): bool => filled($state)),
            ])->columns(2),
        ]);
    }
}
