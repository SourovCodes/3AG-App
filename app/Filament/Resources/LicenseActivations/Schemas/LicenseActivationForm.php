<?php

namespace App\Filament\Resources\LicenseActivations\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class LicenseActivationForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('license_id')
                    ->relationship('license', 'id')
                    ->required(),
                TextInput::make('domain')
                    ->required(),
                TextInput::make('ip_address'),
                TextInput::make('user_agent'),
                DateTimePicker::make('last_checked_at'),
                DateTimePicker::make('activated_at')
                    ->default(now()),
            ]);
    }
}
