<?php

namespace App\Filament\Resources\Users\RelationManagers;

use App\Enums\LicenseStatus;
use App\Filament\Resources\Licenses\LicenseResource;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class LicensesRelationManager extends RelationManager
{
    protected static string $relationship = 'licenses';

    protected static ?string $title = 'Licenses';

    protected static \BackedEnum|string|null $icon = Heroicon::OutlinedKey;

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('license_key')
            ->columns([
                TextColumn::make('license_key')
                    ->label('License Key')
                    ->searchable()
                    ->copyable()
                    ->copyMessage('Copied!')
                    ->weight('bold')
                    ->limit(25),
                TextColumn::make('product.name')
                    ->label('Product')
                    ->badge()
                    ->color(fn ($record) => $record->product?->type?->getColor() ?? 'gray'),
                TextColumn::make('package.name')
                    ->label('Package'),
                TextColumn::make('status')
                    ->badge(),
                TextColumn::make('domain_limit')
                    ->label('Domains')
                    ->formatStateUsing(fn ($state, $record) => $record->domain_limit === null
                        ? $record->activeActivations()->count().' / ∞'
                        : $record->activeActivations()->count().' / '.$state
                    )
                    ->badge()
                    ->color(fn ($state, $record) => $record->domain_limit === null
                        ? 'success'
                        : ($record->activeActivations()->count() >= $record->domain_limit ? 'danger' : 'info')),
                TextColumn::make('expires_at')
                    ->label('Expires')
                    ->dateTime()
                    ->placeholder('Never')
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Created')
                    ->since()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(LicenseStatus::class),
                SelectFilter::make('product')
                    ->relationship('product', 'name')
                    ->searchable()
                    ->preload(),
            ])
            ->headerActions([
                CreateAction::make()
                    ->url(fn () => LicenseResource::getUrl('create', [
                        'user_id' => $this->getOwnerRecord()->getKey(),
                    ]))
                    ->icon(Heroicon::OutlinedPlus),
            ])
            ->recordActions([
                Action::make('view')
                    ->url(fn ($record): string => LicenseResource::getUrl('view', ['record' => $record]))
                    ->icon(Heroicon::OutlinedEye),
                Action::make('edit')
                    ->url(fn ($record): string => LicenseResource::getUrl('edit', ['record' => $record]))
                    ->icon(Heroicon::OutlinedPencil),
            ])
            ->defaultSort('created_at', 'desc')
            ->emptyStateHeading('No licenses yet')
            ->emptyStateDescription('This user has no licenses. Create one to get started.')
            ->emptyStateIcon(Heroicon::OutlinedKey);
    }
}
