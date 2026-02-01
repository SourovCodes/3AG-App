<?php

namespace App\Filament\Resources\Users\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->weight(FontWeight::SemiBold),
                TextColumn::make('email')
                    ->label('Email Address')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->copyMessage('Email copied')
                    ->icon('heroicon-o-envelope'),
                IconColumn::make('email_verified_at')
                    ->label('Verified')
                    ->boolean()
                    ->sortable()
                    ->trueIcon('heroicon-o-check-badge')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('gray'),
                TextColumn::make('stripe_id')
                    ->label('Stripe Customer')
                    ->searchable()
                    ->toggleable()
                    ->placeholder('No subscription')
                    ->badge()
                    ->color('info')
                    ->icon('heroicon-o-credit-card'),
                TextColumn::make('pm_last_four')
                    ->label('Payment Method')
                    ->formatStateUsing(fn (?string $state, $record): ?string => $state ? "{$record->pm_type} •••• {$state}" : null
                    )
                    ->placeholder('No card')
                    ->toggleable(),
                TextColumn::make('trial_ends_at')
                    ->label('Trial Ends')
                    ->dateTime()
                    ->sortable()
                    ->toggleable()
                    ->placeholder('No trial')
                    ->since()
                    ->badge()
                    ->color(fn ($state): string => $state && $state->isFuture() ? 'warning' : 'gray'),
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
                TernaryFilter::make('email_verified_at')
                    ->label('Email Verified')
                    ->nullable(),
                TernaryFilter::make('stripe_id')
                    ->label('Has Subscription')
                    ->nullable()
                    ->queries(
                        true: fn ($query) => $query->whereNotNull('stripe_id'),
                        false: fn ($query) => $query->whereNull('stripe_id'),
                    ),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
