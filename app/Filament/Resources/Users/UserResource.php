<?php

namespace App\Filament\Resources\Users;

use App\Filament\Resources\Users\Pages\CreateUser;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Filament\Resources\Users\Pages\ViewUser;
use App\Filament\Resources\Users\RelationManagers\LicensesRelationManager;
use App\Filament\Resources\Users\Schemas\UserForm;
use App\Filament\Resources\Users\Tables\UsersTable;
use App\Models\User;
use BackedEnum;
use Filament\GlobalSearch\GlobalSearchResult;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use UnitEnum;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    protected static UnitEnum|string|null $navigationGroup = 'User Management';

    protected static ?int $navigationSort = 10;

    protected static ?string $recordTitleAttribute = 'name';

    public static function getNavigationBadge(): ?string
    {
        return (string) static::getModel()::count();
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        return 'info';
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return 'Total registered users';
    }

    public static function getGlobalSearchResultTitle(Model $record): string
    {
        return $record->name;
    }

    public static function getGlobalSearchResultDetails(Model $record): array
    {
        return [
            'Email' => $record->email,
            'Status' => $record->stripe_id ? 'Subscribed' : 'Free',
        ];
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['name', 'email'];
    }

    public static function getGlobalSearchEloquentQuery(): Builder
    {
        return parent::getGlobalSearchEloquentQuery()->with(['licenses']);
    }

    /**
     * @return Collection<int, GlobalSearchResult>
     */
    public static function getGlobalSearchResults(string $search): Collection
    {
        return parent::getGlobalSearchResults($search)->take(5);
    }

    public static function form(Schema $schema): Schema
    {
        return UserForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return UsersTable::configure($table);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->columns(3)
            ->components([
                Section::make('Account Information')
                    ->icon(Heroicon::User)
                    ->columnSpan(1)
                    ->components([
                        TextEntry::make('name')
                            ->weight(FontWeight::Bold),
                        TextEntry::make('email')
                            ->copyable()
                            ->icon(Heroicon::Envelope),
                        IconEntry::make('email_verified_at')
                            ->label('Verified')
                            ->boolean()
                            ->trueIcon(Heroicon::CheckBadge)
                            ->falseIcon(Heroicon::XCircle),
                    ]),
                Section::make('Subscription & Billing')
                    ->icon(Heroicon::CreditCard)
                    ->columnSpan(1)
                    ->components([
                        TextEntry::make('stripe_id')
                            ->label('Stripe Customer ID')
                            ->copyable()
                            ->placeholder('No Stripe account')
                            ->icon(Heroicon::CreditCard),
                        TextEntry::make('pm_type')
                            ->label('Payment Method')
                            ->formatStateUsing(fn ($state, $record) => $state && $record->pm_last_four
                                ? ucfirst($state).' •••• '.$record->pm_last_four
                                : null
                            )
                            ->placeholder('No payment method'),
                        TextEntry::make('trial_ends_at')
                            ->label('Trial Ends')
                            ->dateTime()
                            ->placeholder('No trial')
                            ->badge()
                            ->color(fn ($state) => $state && $state->isFuture() ? 'warning' : 'gray'),
                    ]),
                Section::make('Statistics')
                    ->icon(Heroicon::ChartBar)
                    ->columnSpan(1)
                    ->components([
                        TextEntry::make('licenses_count')
                            ->label('Total Licenses')
                            ->state(fn ($record) => $record->licenses()->count())
                            ->badge()
                            ->color('info'),
                        TextEntry::make('active_licenses_count')
                            ->label('Active Licenses')
                            ->state(fn ($record) => $record->licenses()->where('status', 'active')->count())
                            ->badge()
                            ->color('success'),
                        TextEntry::make('created_at')
                            ->label('Member Since')
                            ->dateTime(),
                    ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            LicensesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListUsers::route('/'),
            'create' => CreateUser::route('/create'),
            'view' => ViewUser::route('/{record}'),
            'edit' => EditUser::route('/{record}/edit'),
        ];
    }
}
