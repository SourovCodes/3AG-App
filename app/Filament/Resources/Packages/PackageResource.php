<?php

namespace App\Filament\Resources\Packages;

use App\Filament\Resources\Packages\Pages\CreatePackage;
use App\Filament\Resources\Packages\Pages\EditPackage;
use App\Filament\Resources\Packages\Pages\ListPackages;
use App\Filament\Resources\Packages\Pages\ViewPackage;
use App\Filament\Resources\Packages\Schemas\PackageForm;
use App\Filament\Resources\Packages\Tables\PackagesTable;
use App\Models\Package;
use BackedEnum;
use Filament\Infolists\Components\IconEntry;
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
use Illuminate\Support\Number;
use UnitEnum;

class PackageResource extends Resource
{
    protected static ?string $model = Package::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSquares2x2;

    protected static UnitEnum|string|null $navigationGroup = 'Shop Management';

    protected static ?int $navigationSort = 2;

    protected static ?string $recordTitleAttribute = 'name';

    public static function getNavigationBadge(): ?string
    {
        return (string) static::getModel()::where('is_active', true)->count();
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        return 'warning';
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return 'Active packages';
    }

    public static function getGlobalSearchResultTitle(Model $record): string
    {
        return $record->name.' ('.$record->product?->name.')';
    }

    public static function getGlobalSearchResultDetails(Model $record): array
    {
        return [
            'Product' => $record->product?->name ?? 'Unknown',
            'Domain Limit' => $record->domain_limit ?? '∞ Unlimited',
            'Monthly' => $record->monthly_price ? Number::currency($record->monthly_price, 'CHF') : 'N/A',
        ];
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['name', 'slug', 'description', 'product.name'];
    }

    public static function getGlobalSearchEloquentQuery(): Builder
    {
        return parent::getGlobalSearchEloquentQuery()->with(['product']);
    }

    public static function form(Schema $schema): Schema
    {
        return PackageForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PackagesTable::configure($table);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Package Details')
                    ->icon(Heroicon::Squares2x2)
                    ->columns(3)
                    ->components([
                        TextEntry::make('name')
                            ->weight(FontWeight::Bold),
                        TextEntry::make('slug')
                            ->copyable()
                            ->icon(Heroicon::Link),
                        TextEntry::make('product.name')
                            ->label('Product')
                            ->badge()
                            ->color(fn ($record) => $record->product?->type?->getColor() ?? 'gray'),
                    ]),
                Section::make('Description')
                    ->icon(Heroicon::DocumentText)
                    ->components([
                        TextEntry::make('description')
                            ->html()
                            ->placeholder('No description provided'),
                    ])
                    ->collapsible(),
                Grid::make(2)
                    ->schema([
                        Section::make('Pricing')
                            ->icon(Heroicon::CurrencyDollar)
                            ->components([
                                TextEntry::make('monthly_price')
                                    ->label('Monthly Price')
                                    ->money('CHF')
                                    ->placeholder('Not set'),
                                TextEntry::make('yearly_price')
                                    ->label('Yearly Price')
                                    ->money('CHF')
                                    ->placeholder('Not set'),
                                TextEntry::make('stripe_monthly_price_id')
                                    ->label('Stripe Monthly ID')
                                    ->copyable()
                                    ->placeholder('Not configured'),
                                TextEntry::make('stripe_yearly_price_id')
                                    ->label('Stripe Yearly ID')
                                    ->copyable()
                                    ->placeholder('Not configured'),
                            ]),
                        Section::make('Limits & Status')
                            ->icon(Heroicon::AdjustmentsHorizontal)
                            ->components([
                                TextEntry::make('domain_limit')
                                    ->label('Domain Limit')
                                    ->formatStateUsing(fn ($state) => $state === null ? '∞ Unlimited' : $state)
                                    ->badge()
                                    ->color(fn ($state) => $state === null ? 'success' : 'info'),
                                IconEntry::make('is_active')
                                    ->label('Active')
                                    ->boolean(),
                                TextEntry::make('sort_order')
                                    ->label('Sort Order'),
                            ]),
                    ]),
                Section::make('Features')
                    ->icon(Heroicon::ListBullet)
                    ->components([
                        TextEntry::make('features')
                            ->label('')
                            ->listWithLineBreaks()
                            ->bulleted()
                            ->placeholder('No features defined'),
                    ])
                    ->collapsible(),
                Section::make('Timestamps')
                    ->icon(Heroicon::Clock)
                    ->columns(2)
                    ->collapsed()
                    ->components([
                        TextEntry::make('created_at')
                            ->dateTime(),
                        TextEntry::make('updated_at')
                            ->since(),
                    ]),
            ]);
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
            'index' => ListPackages::route('/'),
            'create' => CreatePackage::route('/create'),
            'view' => ViewPackage::route('/{record}'),
            'edit' => EditPackage::route('/{record}/edit'),
        ];
    }
}
