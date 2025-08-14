<?php

namespace App\Filament\Resources\Dashboard\DashboardResource\Pages;

use App\Filament\Resources\Dashboard\DashboardResource;
use Filament\Resources\Pages\Page;

class ThankYou extends Page
{
    protected static string $resource = DashboardResource::class;

    protected static string $view = 'filament.resources.dashboard.dashboard-resource.pages.thank-you';
}
