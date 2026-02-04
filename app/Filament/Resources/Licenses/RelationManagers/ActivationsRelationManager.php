<?php

namespace App\Filament\Resources\Licenses\RelationManagers;

use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class ActivationsRelationManager extends RelationManager
{
    protected static string $relationship = 'activations';

    protected static ?string $title = 'Domain Activations';

    protected static \BackedEnum|string|null $icon = Heroicon::OutlinedGlobeAlt;

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('domain')
            ->columns([
                TextColumn::make('domain')
                    ->label('Domain')
                    ->searchable()
                    ->copyable()
                    ->copyMessage('Domain copied!')
                    ->weight('bold')
                    ->icon(Heroicon::OutlinedGlobeAlt),
                IconColumn::make('deactivated_at')
                    ->label('Status')
                    ->boolean()
                    ->getStateUsing(fn ($record): bool => $record->isActive())
                    ->trueIcon(Heroicon::OutlinedCheckCircle)
                    ->falseIcon(Heroicon::OutlinedXCircle)
                    ->trueColor('success')
                    ->falseColor('danger'),
                TextColumn::make('ip_address')
                    ->label('IP Address')
                    ->copyable()
                    ->toggleable(),
                TextColumn::make('user_agent')
                    ->label('Browser')
                    ->limit(30)
                    ->tooltip(fn ($record) => $record->user_agent)
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('activated_at')
                    ->label('Activated')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('last_checked_at')
                    ->label('Last Check')
                    ->since()
                    ->placeholder('Never')
                    ->sortable(),
                TextColumn::make('deactivated_at')
                    ->label('Deactivated')
                    ->dateTime()
                    ->placeholder('Active')
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                TernaryFilter::make('deactivated_at')
                    ->label('Status')
                    ->nullable()
                    ->trueLabel('Deactivated')
                    ->falseLabel('Active')
                    ->queries(
                        true: fn ($query) => $query->whereNotNull('deactivated_at'),
                        false: fn ($query) => $query->whereNull('deactivated_at'),
                    ),
            ])
            ->headerActions([
                CreateAction::make()
                    ->icon(Heroicon::OutlinedPlus),
            ])
            ->recordActions([
                Action::make('deactivate')
                    ->icon(Heroicon::OutlinedXCircle)
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Deactivate Domain')
                    ->modalDescription('Are you sure you want to deactivate this domain? The license will no longer work on this domain.')
                    ->action(fn ($record) => $record->deactivate())
                    ->visible(fn ($record) => $record->isActive()),
                Action::make('reactivate')
                    ->icon(Heroicon::OutlinedCheckCircle)
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Reactivate Domain')
                    ->modalDescription('This will reactivate the domain. Make sure the license has available slots.')
                    ->action(fn ($record) => $record->reactivate())
                    ->visible(fn ($record) => ! $record->isActive()),
            ])
            ->defaultSort('activated_at', 'desc')
            ->emptyStateHeading('No activations yet')
            ->emptyStateDescription('This license has not been activated on any domains.')
            ->emptyStateIcon(Heroicon::OutlinedGlobeAlt);
    }
}
