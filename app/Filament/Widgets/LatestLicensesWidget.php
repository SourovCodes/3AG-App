<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\Licenses\LicenseResource;
use App\Models\License;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class LatestLicensesWidget extends BaseWidget
{
    protected static ?int $sort = 4;

    protected ?string $pollingInterval = '30s';

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(License::query()->with(['user', 'product', 'package'])->latest()->limit(10))
            ->heading('Recent Licenses')
            ->description('The 10 most recently created licenses')
            ->columns([
                TextColumn::make('license_key')
                    ->label('License Key')
                    ->searchable()
                    ->copyable()
                    ->copyMessage('Copied!')
                    ->weight('bold')
                    ->limit(20),
                TextColumn::make('user.name')
                    ->label('Customer')
                    ->searchable()
                    ->icon(Heroicon::OutlinedUser),
                TextColumn::make('user.email')
                    ->label('Email')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('product.name')
                    ->label('Product')
                    ->badge()
                    ->color(fn ($record) => $record->product?->type?->getColor() ?? 'gray'),
                TextColumn::make('package.name')
                    ->label('Package')
                    ->toggleable(),
                TextColumn::make('status')
                    ->badge(),
                TextColumn::make('created_at')
                    ->label('Created')
                    ->since()
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->paginated(false)
            ->headerActions([
                CreateAction::make()
                    ->url(LicenseResource::getUrl('create'))
                    ->label('New License')
                    ->icon(Heroicon::OutlinedPlus),
            ])
            ->recordActions([
                Action::make('view')
                    ->url(fn (License $record): string => LicenseResource::getUrl('edit', ['record' => $record]))
                    ->icon(Heroicon::OutlinedEye),
            ]);
    }
}
