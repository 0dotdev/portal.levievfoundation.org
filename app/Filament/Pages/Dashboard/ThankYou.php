<?php

namespace App\Filament\Pages\Dashboard;

use Filament\Pages\Page;

class ThankYou extends Page
{
    protected static ?string $navigationIcon = null;
    protected static string $view = 'filament.pages.thank-you';
    protected static ?string $title = 'Thank You';
    protected static bool $shouldRegisterNavigation = false;

    public static function getSlug(): string
    {
        return 'thank-you';
    }
} 