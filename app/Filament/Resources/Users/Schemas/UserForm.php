<?php

namespace App\Filament\Resources\Users\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Account Information')
                    ->columns(2)
                    ->components([
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('email')
                            ->label('Email Address')
                            ->email()
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true),
                        DateTimePicker::make('email_verified_at')
                            ->label('Email Verified At')
                            ->native(false),
                        TextInput::make('password')
                            ->password()
                            ->dehydrated(fn (?string $state): bool => filled($state))
                            ->required(fn (string $operation): bool => $operation === 'create')
                            ->minLength(8)
                            ->maxLength(255)
                            ->revealable()
                            ->helperText('Leave blank to keep current password when editing.'),
                    ]),

                Section::make('Subscription & Billing')
                    ->description('Stripe subscription information')
                    ->columns(2)
                    ->components([
                        TextInput::make('stripe_id')
                            ->label('Stripe Customer ID')
                            ->maxLength(255)
                            ->disabled()
                            ->dehydrated(false),
                        DateTimePicker::make('trial_ends_at')
                            ->label('Trial Ends At')
                            ->native(false),
                        Fieldset::make('Payment Method')
                            ->columns(2)
                            ->columnSpanFull()
                            ->components([
                                TextInput::make('pm_type')
                                    ->label('Type')
                                    ->maxLength(255)
                                    ->disabled()
                                    ->dehydrated(false),
                                TextInput::make('pm_last_four')
                                    ->label('Last 4 Digits')
                                    ->maxLength(4)
                                    ->disabled()
                                    ->dehydrated(false),
                            ]),
                    ]),
            ]);
    }
}
