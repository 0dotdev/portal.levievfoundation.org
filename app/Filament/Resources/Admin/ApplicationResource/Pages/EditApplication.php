<?php

namespace App\Filament\Resources\Admin\ApplicationResource\Pages;

use App\Filament\Resources\Admin\ApplicationResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Notification;
use App\Notifications\ApplicationStatusNotification;
use App\Models\User;

class EditApplication extends EditRecord
{
    protected static string $resource = ApplicationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function afterSave(): void
    {
        $record = $this->record;

        if (in_array($record->status, ['submitted', 'resubmitted'])) {
            $admins = User::where('roles', 'admin')->get();
            Notification::send($admins, new ApplicationStatusNotification(
                'New Application Received',
                'An application has been ' . $record->status . ' by ' . optional($record->user)->name,
                url('/admin/admin/applications/' . $record->id . '/edit')
            ));
        }

        if (in_array($record->status, ['fix_needed', 'rejected', 'approved'])) {
            optional($record->user)->notify(new ApplicationStatusNotification(
                'Application ' . ucfirst(str_replace('_', ' ', $record->status)),
                'Your application has been ' . str_replace('_', ' ', $record->status) . '.',
                url('/dashboard/applications')
            ));
        }
    }
}
