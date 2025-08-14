<?php

namespace App\Filament\Resources\Dashboard\ApplicationResource\Pages;

use App\Filament\Resources\Dashboard\ApplicationResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListApplications extends ListRecords
{
    protected static string $resource = ApplicationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    protected function getTableRecordUrlUsing(): ?\Closure
    {
        return null;
    }

    protected function getTableQuery(): Builder
    {
        return parent::getTableQuery()->where('user_id', auth()->id());
    }
}
