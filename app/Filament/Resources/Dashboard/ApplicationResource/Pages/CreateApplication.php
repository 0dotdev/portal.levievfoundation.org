<?php

namespace App\Filament\Resources\Dashboard\ApplicationResource\Pages;

use App\Filament\Resources\Dashboard\ApplicationResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Notification;
use App\Models\User;
use App\Notifications\ApplicationStatusNotification;

class CreateApplication extends CreateRecord
{
    protected static string $resource = ApplicationResource::class;

    protected static bool $canCreateAnother = false;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['school_wish_to_apply_in'] = json_encode($data['school_wish_to_apply_in'] ?? []);
        // Add user_id if not set
        if (!isset($data['user_id'])) {
            $data['user_id'] = auth()->id();
        }
        return $data;
    }

    protected function getCreatedRedirectUrl(): ?string
    {
        return null;
    }

    protected function afterCreate(): void
    {
        $record = $this->record;

        // Notify all admins
        $admins = User::where('roles', 'admin')->get();
        Notification::send($admins, new ApplicationStatusNotification(
            'New Application Submitted',
            'A new application has been submitted by ' . optional($record->user)->name,
            url('/admin/admin/applications/' . $record->id . '/edit')
        ));

        // Notify the user
        optional($record->user)->notify(new ApplicationStatusNotification(
            'Application Submitted',
            'Your application has been submitted successfully. Please wait for our response.',
            url('/dashboard/applications')
        ));

        // Force redirect using session
        session()->flash('redirect_to_thank_you', true);
        $this->redirect('/dashboard/thank-you');
    }

    public function mount(): void
    {
        parent::mount();

        // Check if we need to redirect to thank you page
        if (session()->has('redirect_to_thank_you')) {
            session()->forget('redirect_to_thank_you');
            $this->redirect('/dashboard/thank-you');
        }
    }

    public function create(bool $another = false): void
    {
        // Use parent create method to handle relationships properly
        parent::create($another);

        // Force redirect to thank you page after creation
        $this->redirect('/dashboard/thank-you');
    }
}
