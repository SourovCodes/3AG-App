<?php

namespace App\Filament\Resources\Products\Tables;

use App\Enums\ProductType;
use App\Filament\Resources\Products\ProductResource;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;

class ProductsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->description(fn ($record) => $record->slug),
                TextColumn::make('type')
                    ->badge()
                    ->sortable(),
                TextColumn::make('packages_count')
                    ->counts('packages')
                    ->label('Packages')
                    ->sortable()
                    ->badge()
                    ->color('info'),
                TextColumn::make('active_packages_count')
                    ->counts('activePackages')
                    ->label('Active')
                    ->sortable()
                    ->badge()
                    ->color('success'),
                IconColumn::make('is_active')
                    ->label('Status')
                    ->boolean()
                    ->sortable()
                    ->trueIcon(Heroicon::CheckCircle)
                    ->falseIcon(Heroicon::XCircle),
                TextColumn::make('sort_order')
                    ->label('Order')
                    ->numeric()
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->options(ProductType::class)
                    ->multiple(),
                TernaryFilter::make('is_active')
                    ->label('Active Status')
                    ->placeholder('All products')
                    ->trueLabel('Active only')
                    ->falseLabel('Inactive only'),
            ])
            ->recordActions([
                ViewAction::make()
                    ->icon(Heroicon::Eye),
                ActionGroup::make([
                    Action::make('activate')
                        ->icon(Heroicon::CheckCircle)
                        ->color('success')
                        ->action(function ($record) {
                            $record->update(['is_active' => true]);
                            Notification::make()->title('Product activated')->success()->send();
                        })
                        ->visible(fn ($record) => ! $record->is_active),
                    Action::make('deactivate')
                        ->icon(Heroicon::XCircle)
                        ->color('warning')
                        ->action(function ($record) {
                            $record->update(['is_active' => false]);
                            Notification::make()->title('Product deactivated')->success()->send();
                        })
                        ->visible(fn ($record) => $record->is_active),
                    EditAction::make()
                        ->icon(Heroicon::Pencil),
                ])->icon(Heroicon::EllipsisVertical),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    BulkAction::make('activate')
                        ->icon(Heroicon::CheckCircle)
                        ->color('success')
                        ->action(function (Collection $records) {
                            $records->each(fn ($record) => $record->update(['is_active' => true]));
                            Notification::make()->title($records->count().' products activated')->success()->send();
                        }),
                    BulkAction::make('deactivate')
                        ->icon(Heroicon::XCircle)
                        ->color('warning')
                        ->action(function (Collection $records) {
                            $records->each(fn ($record) => $record->update(['is_active' => false]));
                            Notification::make()->title($records->count().' products deactivated')->success()->send();
                        }),
                    DeleteBulkAction::make(),
                ]),
            ])
            ->reorderable('sort_order')
            ->defaultSort('sort_order')
            ->striped()
            ->recordUrl(fn ($record): string => ProductResource::getUrl('view', ['record' => $record]));
    }
}
