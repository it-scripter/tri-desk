<?php

namespace App\Filament\Admin\Resources\Editions\Actions;

use App\Domain\Competition\Models\Edition;
use App\Domain\Competition\Services\EditionCopier;
use App\Filament\Admin\Resources\Editions\EditionResource;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rules\Unique;

/**
 * Copies an edition as the template of a new year (specification W-01,
 * B-01). Used in the edition list and on the edit page. The fields are
 * prefilled with the following year.
 */
class CopyEditionAction
{
    public static function make(): Action
    {
        return Action::make('copy')
            ->label(__('edition.copy.action'))
            ->icon(Heroicon::OutlinedDocumentDuplicate)
            ->authorize(fn (Edition $record): bool => Filament::auth()->user()?->can('copy', $record) ?? false)
            ->modalHeading(fn (Edition $record): string => __('edition.copy.heading', ['source' => $record->titleWithNumber()]))
            ->modalDescription(__('edition.copy.description'))
            ->fillForm(fn (Edition $record): array => [
                'year' => $record->year + 1,
                'number' => $record->number !== null ? $record->number + 1 : null,
                'slug' => (string) ($record->year + 1),
                // 52 weeks rather than a calendar year: the weekday stays,
                // a Sunday race is proposed on a Sunday again.
                'starts_on' => $record->starts_on?->copy()->addWeeks(52)->format('Y-m-d'),
            ])
            ->schema(fn (Edition $record): array => [
                TextInput::make('year')
                    ->label(__('edition.fields.year'))
                    ->numeric()
                    ->minValue(1989)
                    ->maxValue(2100)
                    ->required(),

                TextInput::make('number')
                    ->label(__('edition.fields.number'))
                    ->numeric()
                    ->minValue(1),

                TextInput::make('slug')
                    ->label(__('edition.fields.slug'))
                    ->required()
                    ->maxLength(255)
                    ->rules(['alpha_dash'])
                    ->unique(
                        table: 'editions',
                        column: 'slug',
                        modifyRuleUsing: fn (Unique $rule): Unique => $rule->where('competition_id', $record->competition_id),
                    ),

                DatePicker::make('starts_on')
                    ->label(__('edition.copy.starts_on'))
                    ->helperText(__('edition.copy.starts_on_hint'))
                    ->displayFormat('d.m.Y'),
            ])
            ->action(function (Edition $record, array $data, Action $action): void {
                $user = Filament::auth()->user();

                $copy = app(EditionCopier::class)->copy(
                    source: $record,
                    year: (int) $data['year'],
                    number: filled($data['number'] ?? null) ? (int) $data['number'] : null,
                    slug: (string) $data['slug'],
                    startsOn: filled($data['starts_on'] ?? null) ? Carbon::parse($data['starts_on']) : null,
                    causer: $user instanceof User ? $user : null,
                );

                Notification::make()
                    ->success()
                    ->title(__('edition.copy.success', ['edition' => $copy->titleWithNumber()]))
                    ->send();

                $action->redirect(EditionResource::getUrl('edit', ['record' => $copy]));
            });
    }
}
