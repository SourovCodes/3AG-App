<?php

namespace App\Filament\Resources\NaldaCsvUploads\Schemas;

use App\Enums\NaldaCsvType;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class NaldaCsvUploadForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Upload Details')
                    ->schema([
                        Select::make('license_id')
                            ->relationship('license', 'license_key')
                            ->required()
                            ->searchable()
                            ->preload(),
                        Grid::make(2)
                            ->schema([
                                TextInput::make('domain')
                                    ->required()
                                    ->maxLength(255)
                                    ->placeholder('example.com'),
                                Select::make('csv_type')
                                    ->options(NaldaCsvType::class)
                                    ->required()
                                    ->native(false),
                            ]),
                        SpatieMediaLibraryFileUpload::make('csv')
                            ->collection('csv')
                            ->acceptedFileTypes(['text/csv', 'text/plain', 'application/csv', 'application/vnd.ms-excel'])
                            ->maxSize(10240)
                            ->downloadable()
                            ->openable()
                            ->helperText('Upload a CSV file (max 10MB)'),
                    ]),
                Section::make('SFTP Configuration')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('sftp_host')
                                    ->required()
                                    ->maxLength(255)
                                    ->placeholder('sftp.example.com'),
                                TextInput::make('sftp_port')
                                    ->numeric()
                                    ->default(22)
                                    ->minValue(1)
                                    ->maxValue(65535),
                            ]),
                        Grid::make(2)
                            ->schema([
                                TextInput::make('sftp_username')
                                    ->required()
                                    ->maxLength(255),
                                TextInput::make('sftp_path')
                                    ->maxLength(255)
                                    ->placeholder('/uploads/csv'),
                            ]),
                    ]),
                Section::make('Status')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Select::make('status')
                                    ->options([
                                        'pending' => 'Pending',
                                        'processing' => 'Processing',
                                        'completed' => 'Completed',
                                        'failed' => 'Failed',
                                    ])
                                    ->default('pending')
                                    ->native(false),
                                TextInput::make('uploaded_at')
                                    ->disabled()
                                    ->placeholder('Set automatically on completion'),
                            ]),
                        Textarea::make('error_message')
                            ->rows(3)
                            ->placeholder('Error details will appear here if upload fails'),
                    ])
                    ->collapsed()
                    ->collapsible(),
            ]);
    }
}
