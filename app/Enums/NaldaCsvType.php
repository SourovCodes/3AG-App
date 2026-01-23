<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use Filament\Support\Icons\Heroicon;

enum NaldaCsvType: string implements HasColor, HasIcon, HasLabel
{
    case Orders = 'orders';
    case Products = 'products';

    /**
     * Get the SFTP folder path for this CSV type.
     */
    public function getSftpFolder(): string
    {
        return match ($this) {
            self::Orders => '/order-status',
            self::Products => '/',
        };
    }

    public function getLabel(): string
    {
        return match ($this) {
            self::Orders => 'Orders',
            self::Products => 'Products',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Orders => 'info',
            self::Products => 'success',
        };
    }

    public function getIcon(): string|Heroicon
    {
        return match ($this) {
            self::Orders => Heroicon::ClipboardDocumentList,
            self::Products => Heroicon::CubeTransparent,
        };
    }
}
