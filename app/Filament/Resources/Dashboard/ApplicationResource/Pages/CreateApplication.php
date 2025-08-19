<?php

namespace App\Filament\Resources\Dashboard\ApplicationResource\Pages;

use App\Filament\Resources\Dashboard\ApplicationResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Notification;
use App\Models\User;
use App\Notifications\ApplicationStatusNotification;
use App\Services\ApplicationService;
use Filament\Actions\Action;
use Illuminate\Support\Facades\Auth;

class CreateApplication extends CreateRecord
{
    protected static string $resource = ApplicationResource::class;

    protected static bool $canCreateAnother = false;

    // Handling creation directly in create() method

    protected function getCreatedRedirectUrl(): ?string
    {
        return null;
    }

    protected function afterCreate(): void
    {
        $result = session('created_applications');

        if (!$result) {
            return;
        }

        $parent = $result['parent'];
        $applications = $result['applications'];

        // Notify all admins
        $admins = User::where('roles', 'admin')->get();
        foreach ($applications as $application) {
            Notification::send($admins, new ApplicationStatusNotification(
                'New Application Submitted',
                'A new application has been submitted by ' . Auth::user()->name,
                url('/admin/admin/applications/' . $application->id . '/edit')
            ));
        }

        // Notify the user
        Auth::user()->notify(new ApplicationStatusNotification(
            'Application Submitted',
            'Your application has been submitted successfully. Please wait for our response.',
            url('/dashboard/applications')
        ));

        // Cleanup session and redirect
        session()->forget('created_applications');
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
        $data = $this->form->getState();

        // Process form data using ApplicationService
        $applicationService = app(ApplicationService::class);
        $result = $applicationService->createApplicationsForFamily(Auth::user(), $data);

        // Store the created records in session for notification
        session(['created_applications' => $result]);

        // Call afterCreate to handle notifications and redirect
        $this->afterCreate();
    }

    /**
     * @return array<Action | ActionGroup>
     */
    protected function getFormActions(): array
    {
        return [
            $this->getCreateFormAction(),
            ...(static::canCreateAnother() ? [$this->getCreateAnotherFormAction()] : []),
        ];
    }

    protected function getCreateFormAction(): Action
    {
        return Action::make('create')
            ->label(__('Submit'))
            ->submit('create')
            ->keyBindings(['mod+s'])
            ->extraAttributes([
                'class' => 'submit-button'
            ]);
    }
}
