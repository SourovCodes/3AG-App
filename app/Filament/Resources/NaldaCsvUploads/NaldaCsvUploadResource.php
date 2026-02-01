<?php

namespace App\Filament\Resources\NaldaCsvUploads;

use App\Filament\Resources\NaldaCsvUploads\Pages\CreateNaldaCsvUpload;
use App\Filament\Resources\NaldaCsvUploads\Pages\EditNaldaCsvUpload;
use App\Filament\Resources\NaldaCsvUploads\Pages\ListNaldaCsvUploads;
use App\Filament\Resources\NaldaCsvUploads\Schemas\NaldaCsvUploadForm;
use App\Filament\Resources\NaldaCsvUploads\Tables\NaldaCsvUploadsTable;
use App\Models\NaldaCsvUpload;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class NaldaCsvUploadResource extends Resource
{
    protected static ?string $model = NaldaCsvUpload::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowUpTray;

    protected static UnitEnum|string|null $navigationGroup = 'License Management';

    protected static ?int $navigationSort = 5;

    protected static ?string $navigationLabel = 'Nalda CSV Uploads';

    public static function form(Schema $schema): Schema
    {
        return NaldaCsvUploadForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return NaldaCsvUploadsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListNaldaCsvUploads::route('/'),
            'create' => CreateNaldaCsvUpload::route('/create'),
            'edit' => EditNaldaCsvUpload::route('/{record}/edit'),
        ];
    }
}
