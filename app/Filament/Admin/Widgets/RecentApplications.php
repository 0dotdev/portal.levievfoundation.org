<?php

namespace App\Filament\Admin\Widgets;

use App\Filament\Resources\Admin\ApplicationResource;
use App\Traits\CommonTrait;
use Carbon\Carbon;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class RecentApplications extends BaseWidget
{
    use CommonTrait;

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
                TextColumn::make('first_name')
                    ->label('Child Name')
                    ->formatStateUsing(fn($record) => $record->first_name . ' ' . $record->last_name)
                    ->searchable(['first_name', 'last_name']),
                TextColumn::make('date_of_birth')->searchable(),
                TextColumn::make('gender')->searchable()->formatStateUsing(fn($state) => self::genders()[$state] ?? $state),

                TextColumn::make('current_school_name')->label('Current School')->description(fn($record): string => $record->current_school_location)->searchable(),
                TextColumn::make('current_grade')->searchable(),
                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn($state) => self::applicationStatus()[$state] ?? $state)
                    ->colors(self::applicationColors())
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')->dateTime()->sortable(),
            ]);
    }
}
