<?php

namespace App\Filament\Resources\Dashboard\ApplicationResource\Pages;

use App\Filament\Resources\Dashboard\ApplicationResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Notification;
use App\Models\User;
use App\Notifications\ApplicationStatusNotification;

class EditApplication extends EditRecord
{
    protected static string $resource = ApplicationResource::class;

    public function mount($record): void
    {
        parent::mount($record);
        
        // Check if the current user is the owner of the application
        if ($this->record->user_id !== Auth::id()) {
            abort(403, 'You can only edit your own applications.');
        }
        
        // Check if the application status allows editing
        if ($this->record->status !== 'fix_needed') {
            abort(403, 'Wait for our response to edit your application.');
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['school_wish_to_apply_in'] = json_encode($data['school_wish_to_apply_in'] ?? []);
        
        // If the application was in 'fix_needed' status, change it to 'resubmitted'
        if ($this->record->status === 'fix_needed') {
            $data['status'] = 'resubmitted';
        }

        return $data;
    }

    protected function afterSave(): void
    {
        $record = $this->record;

        // If the status is now 'resubmitted', notify admins
        if ($record->status === 'resubmitted') {
            $admins = User::where('roles', 'admin')->get();
            Notification::send($admins, new ApplicationStatusNotification(
                'Application Resubmitted',
                'An application has been resubmitted by ' . optional($record->user)->name,
                url('/admin/admin/applications/' . $record->id . '/edit')
            ));
        }
    }
}
