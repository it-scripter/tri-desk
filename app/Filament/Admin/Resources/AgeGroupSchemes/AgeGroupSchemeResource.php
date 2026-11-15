<?php

namespace App\Filament\Admin\Resources\AgeGroupSchemes;

use App\Domain\Competition\Enums\DiverseScoring;
use App\Domain\Competition\Models\AgeGroupScheme;
use App\Filament\Admin\Resources\AgeGroupSchemes\Pages\CreateAgeGroupScheme;
use App\Filament\Admin\Resources\AgeGroupSchemes\Pages\EditAgeGroupScheme;
use App\Filament\Admin\Resources\AgeGroupSchemes\Pages\ListAgeGroupSchemes;
use App\Filament\Admin\Resources\AgeGroupSchemes\RelationManagers\AgeGroupsRelationManager;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/**
 * Age group schemes shared by every competition (specification W-05), kept
 * by the super admin.
 */
class AgeGroupSchemeResource extends Resource
{
    protected static ?string $model = AgeGroupScheme::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;

    protected static ?int $navigationSort = 30;

    protected static ?string $recordTitleAttribute = 'name';

    public static function getNavigationGroup(): ?string
    {
        return __('competition.navigation_group');
    }

    public static function getModelLabel(): string
    {
        return __('age_group.scheme.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('age_group.scheme.plural_label');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')
                ->label(__('age_group.scheme.fields.name'))
                ->required()
                ->maxLength(255),

            TextInput::make('slug')
                ->label(__('age_group.scheme.fields.slug'))
                ->required()
                ->maxLength(255)
                ->rules(['alpha_dash'])
                ->unique(ignoreRecord: true),

            Select::make('diverse_scoring')
                ->label(__('age_group.scheme.fields.diverse_scoring'))
                ->helperText(__('age_group.scheme.hints.diverse_scoring'))
                ->options(DiverseScoring::labels())
                ->default(DiverseScoring::Separate->value)
                ->required(),

            Textarea::make('description')
                ->label(__('age_group.scheme.fields.description'))
                ->columnSpanFull(),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('name')
            ->columns([
                TextColumn::make('name')
                    ->label(__('age_group.scheme.fields.name'))
                    ->searchable()
                    ->sortable(),

                TextColumn::make('diverse_scoring')
                    ->label(__('age_group.scheme.fields.diverse_scoring'))
                    ->formatStateUsing(fn (DiverseScoring $state): string => $state->label()),

                TextColumn::make('age_groups_count')
                    ->label(__('age_group.scheme.fields.age_groups_count'))
                    ->counts('ageGroups'),
            ])
            ->recordActions([
                EditAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            AgeGroupsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAgeGroupSchemes::route('/'),
            'create' => CreateAgeGroupScheme::route('/create'),
            'edit' => EditAgeGroupScheme::route('/{record}/edit'),
        ];
    }
}
