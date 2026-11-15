<?php

namespace App\Filament\Shared\RelationManagers;

use App\Domain\Competition\Enums\RaceFieldScope;
use App\Domain\Competition\Enums\RaceFieldType;
use App\Domain\Competition\Models\Race;
use App\Domain\Competition\Models\RaceField;
use App\Domain\Competition\Support\RaceFieldCatalogue;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rules\Unique;

/**
 * Additional fields of a race (specification W-11): taken over from the
 * catalogue with a click, or defined freely.
 */
class RaceFieldsRelationManager extends RelationManager
{
    protected static string $relationship = 'fields';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('race_field.plural_label');
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('label')
                ->label(__('race_field.fields.label'))
                ->required()
                ->maxLength(255),

            TextInput::make('key')
                ->label(__('race_field.fields.key'))
                ->helperText(__('race_field.hints.key'))
                ->required()
                ->maxLength(64)
                ->rules(['alpha_dash'])
                ->unique(
                    ignoreRecord: true,
                    modifyRuleUsing: fn (Unique $rule): Unique => $rule->where('race_id', $this->getOwnerRecord()->getKey()),
                ),

            Select::make('type')
                ->label(__('race_field.fields.type'))
                ->options(RaceFieldType::labels())
                ->default(RaceFieldType::Text->value)
                ->required()
                ->live(),

            Select::make('scope')
                ->label(__('race_field.fields.scope'))
                ->options(RaceFieldScope::labels())
                ->default(RaceFieldScope::Participant->value)
                ->required(),

            TagsInput::make('choices')
                ->label(__('race_field.fields.choices'))
                ->helperText(__('race_field.hints.choices'))
                ->visible(fn (Get $get): bool => self::isChoice($get('type')))
                ->required(fn (Get $get): bool => self::isChoice($get('type')))
                ->columnSpanFull(),

            TextInput::make('help')
                ->label(__('race_field.fields.help'))
                ->maxLength(255)
                ->columnSpanFull(),

            Toggle::make('is_required')
                ->label(__('race_field.fields.is_required')),

            TextInput::make('sort')
                ->label(__('race_field.fields.sort'))
                ->numeric()
                ->default(fn (): int => $this->race()->fields()->count()),
        ])->columns(2);
    }

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('sort')
            ->reorderable('sort')
            ->modelLabel(__('race_field.label'))
            ->pluralModelLabel(__('race_field.plural_label'))
            ->columns([
                TextColumn::make('label')
                    ->label(__('race_field.fields.label')),

                TextColumn::make('type')
                    ->label(__('race_field.fields.type'))
                    ->formatStateUsing(fn (RaceFieldType $state): string => $state->label()),

                TextColumn::make('scope')
                    ->label(__('race_field.fields.scope'))
                    ->formatStateUsing(fn (RaceFieldScope $state): string => $state->label()),

                IconColumn::make('is_required')
                    ->label(__('race_field.fields.is_required'))
                    ->boolean(),
            ])
            ->headerActions([
                $this->catalogueAction(),
                CreateAction::make(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    /**
     * Takes over a ready-made field; only those the race does not have yet
     * are offered.
     */
    private function catalogueAction(): Action
    {
        return Action::make('fromCatalogue')
            ->label(__('race_field.catalogue.action'))
            ->icon(Heroicon::OutlinedSquaresPlus)
            ->authorize(fn (): bool => Gate::allows('create', RaceField::class))
            ->schema([
                Select::make('key')
                    ->label(__('race_field.catalogue.field'))
                    ->options(fn (): array => array_diff_key(
                        RaceFieldCatalogue::labels(),
                        array_flip($this->race()->fields()->pluck('key')->all()),
                    ))
                    ->required(),

                Toggle::make('is_required')
                    ->label(__('race_field.fields.is_required')),
            ])
            ->action(function (array $data): void {
                $entry = RaceFieldCatalogue::entries()[$data['key']] ?? null;

                if ($entry === null) {
                    return;
                }

                $this->race()->fields()->firstOrCreate(['key' => $data['key']], [
                    'label' => $entry['label'],
                    'type' => $entry['type'],
                    'scope' => $entry['scope'],
                    'help' => $entry['help'],
                    'is_required' => (bool) ($data['is_required'] ?? false),
                    'sort' => $this->race()->fields()->count(),
                ]);
            });
    }

    private static function isChoice(mixed $type): bool
    {
        return $type === RaceFieldType::Choice || $type === RaceFieldType::Choice->value;
    }

    private function race(): Race
    {
        $race = $this->getOwnerRecord();

        if (! $race instanceof Race) {
            abort(404);
        }

        return $race;
    }
}
