<?php

namespace App\Filament\Admin\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Models\Application;
use App\Models\User;

class StatsOverview extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {

        return [
            Stat::make('Total Applications', Application::count()),
            Stat::make('Total Approved', Application::where('status', 'approved')->count()),
            Stat::make('Total Rejected', Application::where('status', 'rejected')->count()),
            Stat::make('Total Pending', Application::where('status', 'pending')->count()),
            Stat::make('Total Fix Needed', Application::where('status', 'fix_needed')->count()),
            Stat::make('Total Resubmitted', Application::where('status', 'resubmitted')->count()),
            
            Stat::make('Total Users', User::count()),
            Stat::make('Privileged Users', User::where('roles', 'admin')->count()),
            Stat::make('Normal Users', User::where('roles', 'user')->count()),

        ];
    }
}
