<?php

namespace App\Filament\Resources\Licenses;

use App\Enums\LicenseStatus;
use App\Filament\Resources\Licenses\Pages\CreateLicense;
use App\Filament\Resources\Licenses\Pages\EditLicense;
use App\Filament\Resources\Licenses\Pages\ListLicenses;
use App\Filament\Resources\Licenses\Pages\ViewLicense;
use App\Filament\Resources\Licenses\RelationManagers\ActivationsRelationManager;
use App\Filament\Resources\Licenses\Schemas\LicenseForm;
use App\Filament\Resources\Licenses\Tables\LicensesTable;
use App\Models\License;
use BackedEnum;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class LicenseResource extends Resource
{
    protected static ?string $model = License::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedKey;

    protected static UnitEnum|string|null $navigationGroup = 'License Management';

    protected static ?int $navigationSort = 3;

    protected static ?string $recordTitleAttribute = 'license_key';

    public static function getNavigationBadge(): ?string
    {
        return (string) static::getModel()::where('status', LicenseStatus::Active)->count();
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        return 'success';
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return 'Active licenses';
    }

    public static function getGlobalSearchResultTitle(Model $record): string
    {
        return $record->license_key;
    }

    public static function getGlobalSearchResultDetails(Model $record): array
    {
        return [
            'Customer' => $record->user?->name ?? 'Unknown',
            'Product' => $record->product?->name ?? 'Unknown',
            'Status' => $record->status->getLabel(),
        ];
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['license_key', 'user.name', 'user.email', 'product.name'];
    }

    public static function getGlobalSearchEloquentQuery(): Builder
    {
        return parent::getGlobalSearchEloquentQuery()->with(['user', 'product', 'package']);
    }

    public static function form(Schema $schema): Schema
    {
        return LicenseForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return LicensesTable::configure($table);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('License Information')
                    ->icon(Heroicon::Key)
                    ->columns(3)
                    ->components([
                        TextEntry::make('license_key')
                            ->label('License Key')
                            ->weight(FontWeight::Bold)
                            ->copyable()
                            ->copyMessage('License key copied!')
                            ->icon(Heroicon::ClipboardDocument),
                        TextEntry::make('status')
                            ->badge(),
                        TextEntry::make('expires_at')
                            ->label('Expires')
                            ->dateTime()
                            ->placeholder('Never')
                            ->icon(Heroicon::Calendar),
                    ]),
                Section::make('Customer & Product')
                    ->icon(Heroicon::User)
                    ->columns(2)
                    ->components([
                        TextEntry::make('user.name')
                            ->label('Customer')
                            ->icon(Heroicon::User),
                        TextEntry::make('user.email')
                            ->label('Email')
                            ->copyable()
                            ->icon(Heroicon::Envelope),
                        TextEntry::make('product.name')
                            ->label('Product')
                            ->badge()
                            ->color(fn ($record) => $record->product?->type?->getColor() ?? 'gray'),
                        TextEntry::make('package.name')
                            ->label('Package'),
                    ]),
                Section::make('Usage & Limits')
                    ->icon(Heroicon::ChartBar)
                    ->columns(3)
                    ->components([
                        TextEntry::make('domain_limit')
                            ->label('Domain Limit')
                            ->formatStateUsing(fn ($state) => $state === null ? '∞ Unlimited' : $state)
                            ->badge()
                            ->color(fn ($state) => $state === null ? 'success' : 'info'),
                        TextEntry::make('activeActivations')
                            ->label('Active Domains')
                            ->state(fn ($record) => $record->activeActivations()->count())
                            ->badge()
                            ->color('primary'),
                        TextEntry::make('last_validated_at')
                            ->label('Last Validated')
                            ->since()
                            ->placeholder('Never'),
                    ]),
                Grid::make(2)
                    ->schema([
                        Section::make('Subscription')
                            ->icon(Heroicon::CreditCard)
                            ->components([
                                TextEntry::make('subscription.type')
                                    ->label('Type')
                                    ->placeholder('No subscription'),
                                TextEntry::make('subscription.stripe_status')
                                    ->label('Stripe Status')
                                    ->badge()
                                    ->placeholder('N/A'),
                            ]),
                        Section::make('Timestamps')
                            ->icon(Heroicon::Clock)
                            ->components([
                                TextEntry::make('created_at')
                                    ->label('Created')
                                    ->dateTime(),
                                TextEntry::make('updated_at')
                                    ->label('Last Updated')
                                    ->since(),
                            ]),
                    ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            ActivationsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListLicenses::route('/'),
            'create' => CreateLicense::route('/create'),
            'view' => ViewLicense::route('/{record}'),
            'edit' => EditLicense::route('/{record}/edit'),
        ];
    }
}
