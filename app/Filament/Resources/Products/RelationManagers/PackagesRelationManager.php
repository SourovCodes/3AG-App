<?php

namespace App\Filament\Resources\Products\RelationManagers;

use App\Filament\Resources\Packages\PackageResource;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class PackagesRelationManager extends RelationManager
{
    protected static string $relationship = 'packages';

    protected static ?string $title = 'Packages';

    protected static \BackedEnum|string|null $icon = Heroicon::OutlinedSquares2x2;

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                TextColumn::make('name')
                    ->label('Package Name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                TextColumn::make('slug')
                    ->label('Slug')
                    ->copyable()
                    ->toggleable(),
                TextColumn::make('domain_limit')
                    ->label('Domain Limit')
                    ->formatStateUsing(fn ($state) => $state === null ? '∞ Unlimited' : $state)
                    ->badge()
                    ->color(fn ($state) => $state === null ? 'success' : 'info')
                    ->sortable(),
                TextColumn::make('monthly_price')
                    ->label('Monthly')
                    ->money('CHF')
                    ->sortable()
                    ->placeholder('N/A'),
                TextColumn::make('yearly_price')
                    ->label('Yearly')
                    ->money('CHF')
                    ->sortable()
                    ->placeholder('N/A'),
                IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean()
                    ->sortable(),
                TextColumn::make('sort_order')
                    ->label('Order')
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                TernaryFilter::make('is_active')
                    ->label('Status')
                    ->trueLabel('Active')
                    ->falseLabel('Inactive'),
            ])
            ->headerActions([
                CreateAction::make()
                    ->url(fn () => PackageResource::getUrl('create', [
                        'product_id' => $this->getOwnerRecord()->getKey(),
                    ]))
                    ->icon(Heroicon::OutlinedPlus),
            ])
            ->recordActions([
                Action::make('view')
                    ->url(fn ($record): string => PackageResource::getUrl('view', ['record' => $record]))
                    ->icon(Heroicon::OutlinedEye),
                Action::make('edit')
                    ->url(fn ($record): string => PackageResource::getUrl('edit', ['record' => $record]))
                    ->icon(Heroicon::OutlinedPencil),
            ])
            ->reorderable('sort_order')
            ->defaultSort('sort_order')
            ->emptyStateHeading('No packages yet')
            ->emptyStateDescription('Create packages to define pricing tiers for this product.')
            ->emptyStateIcon(Heroicon::OutlinedSquares2x2);
    }
}
