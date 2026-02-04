<?php

namespace App\Filament\Resources\Packages\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class PackageForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Package Details')
                    ->schema([
                        Select::make('product_id')
                            ->relationship('product', 'name')
                            ->required()
                            ->searchable()
                            ->preload(),
                        Grid::make(2)
                            ->schema([
                                TextInput::make('name')
                                    ->required()
                                    ->maxLength(255)
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(fn (string $operation, $state, $set) => $operation === 'create' ? $set('slug', Str::slug($state)) : null)
                                    ->placeholder('Basic, Pro, Agency'),
                                TextInput::make('slug')
                                    ->required()
                                    ->maxLength(255)
                                    ->unique(
                                        table: 'packages',
                                        column: 'slug',
                                        ignoreRecord: true,
                                        modifyRuleUsing: fn ($rule, $get) => $rule->where('product_id', $get('product_id'))
                                    ),
                            ]),
                        Textarea::make('description')
                            ->rows(2)
                            ->columnSpanFull(),
                    ]),
                Section::make('Domain Limit')
                    ->schema([
                        TextInput::make('domain_limit')
                            ->numeric()
                            ->minValue(1)
                            ->placeholder('Leave empty for unlimited')
                            ->helperText('Number of domains/websites where this license can be activated. Leave empty for unlimited.'),
                    ]),
                Section::make('Pricing')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('monthly_price')
                                    ->numeric()
                                    ->prefix('$')
                                    ->placeholder('9.99')
                                    ->minValue(0),
                                TextInput::make('yearly_price')
                                    ->numeric()
                                    ->prefix('$')
                                    ->placeholder('99.99')
                                    ->minValue(0),
                            ]),
                        Grid::make(2)
                            ->schema([
                                TextInput::make('stripe_monthly_price_id')
                                    ->maxLength(255)
                                    ->placeholder('price_xxxxx')
                                    ->helperText('Stripe price ID for monthly billing'),
                                TextInput::make('stripe_yearly_price_id')
                                    ->maxLength(255)
                                    ->placeholder('price_xxxxx')
                                    ->helperText('Stripe price ID for yearly billing'),
                            ]),
                    ]),
                Section::make('Features')
                    ->schema([
                        TagsInput::make('features')
                            ->placeholder('Add feature')
                            ->helperText('Press Enter after each feature')
                            ->columnSpanFull(),
                    ]),
                Section::make('Settings')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Toggle::make('is_active')
                                    ->default(true)
                                    ->inline(false),
                                TextInput::make('sort_order')
                                    ->numeric()
                                    ->default(0)
                                    ->minValue(0),
                            ]),
                    ]),
            ]);
    }
}
