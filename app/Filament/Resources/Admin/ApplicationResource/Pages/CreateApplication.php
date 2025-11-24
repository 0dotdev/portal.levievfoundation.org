<?php

namespace App\Filament\Resources\Admin\ApplicationResource\Pages;

use App\Filament\Resources\Admin\ApplicationResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateApplication extends CreateRecord
{
    protected static string $resource = ApplicationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('note')
                ->label('') // no button label
                ->view('filament.create-application-note')
        ];
    }
}
