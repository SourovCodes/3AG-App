<?php

namespace App\Filament\Resources\Licenses\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class LicenseForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('License Details')
                    ->schema([
                        Select::make('user_id')
                            ->relationship('user', 'name')
                            ->required()
                            ->searchable()
                            ->preload(),
                        Grid::make(2)
                            ->schema([
                                Select::make('product_id')
                                    ->relationship('product', 'name')
                                    ->required()
                                    ->searchable()
                                    ->live()
                                    ->afterStateUpdated(fn ($set) => $set('package_id', null)),
                                Select::make('package_id')
                                    ->relationship('package', 'name', fn ($query, $get) => $query->where('product_id', $get('product_id')))
                                    ->required()
                                    ->searchable()
                                    ->preload()
                                    ->live(),
                            ]),
                        TextInput::make('license_key')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255)
                            ->helperText('Auto-generated if left empty')
                            ->placeholder('Will be auto-generated'),
                    ]),
                Section::make('Subscription & Limits')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Select::make('subscription_id')
                                    ->relationship('subscription', 'type')
                                    ->searchable()
                                    ->preload()
                                    ->helperText('Optional - link to Stripe subscription')
                                    ->getOptionLabelFromRecordUsing(fn ($record) => 
                                        $record->user->email . ' - ' . ucfirst($record->type) . ' (' . $record->stripe_status . ')'
                                    ),
                                TextInput::make('domain_limit')
                                    ->numeric()
                                    ->minValue(1)
                                    ->placeholder('Leave empty for unlimited')
                                    ->helperText('Copied from package if left empty'),
                            ]),
                    ]),
                Section::make('Status & Expiry')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Select::make('status')
                                    ->options([
                                        'active' => 'Active',
                                        'suspended' => 'Suspended',
                                        'expired' => 'Expired',
                                        'cancelled' => 'Cancelled',
                                    ])
                                    ->required()
                                    ->default('active')
                                    ->native(false),
                                DateTimePicker::make('expires_at')
                                    ->helperText('Leave empty for no expiration'),
                            ]),
                    ]),
            ]);
    }
}
