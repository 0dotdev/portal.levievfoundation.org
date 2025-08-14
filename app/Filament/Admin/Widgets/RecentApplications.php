<?php

namespace App\Filament\Admin\Widgets;

use App\Filament\Resources\Admin\ApplicationResource;
use Carbon\Carbon;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class RecentApplications extends BaseWidget
{
    
    protected static ?string $heading = 'Recent Applications';

    protected static bool $isLazy = false;

    protected int|string|array $columnSpan = 'full';

    // Polling interval in seconds
    protected static ?string $pollingInterval = '6s';

    protected static ?int $sort = 2;

    public function table(Table $table): Table
    {
        return $table
            ->query(
                ApplicationResource::getEloquentQuery()
                    ->whereBetween('created_at', [Carbon::today(), Carbon::tomorrow()])
            )
            ->defaultSort('created_at', 'desc')
            ->defaultPaginationPageOption(10)

            ->columns([
                Tables\Columns\TextColumn::make('id')->label('ID')->sortable(),
                Tables\Columns\TextColumn::make('first_name')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('date_of_birth')->searchable(),
                Tables\Columns\TextColumn::make('gender')->searchable(),
                Tables\Columns\TextColumn::make('current_school_name')->searchable(),
                Tables\Columns\TextColumn::make('current_grade')->searchable(),
                Tables\Columns\BadgeColumn::make('status')
                    ->formatStateUsing(fn($state) => [
                        'pending' => 'Pending',
                        'approved' => 'Approved',
                        'rejected' => 'Rejected',
                        'fix_needed' => 'Fix Needed',
                        'resubmitted' => 'Resubmitted',
                    ][$state] ?? $state)
                    ->colors([
                        'primary' => 'pending',
                        'success' => 'approved',
                        'danger' => 'rejected',
                        'warning' => 'fix_needed',
                        'info' => 'resubmitted',
                    ])
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')->dateTime()->sortable(),
            ]);
    }


}
