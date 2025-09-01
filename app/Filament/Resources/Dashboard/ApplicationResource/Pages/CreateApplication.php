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

        $applications = $result['applications'];
        $totalChildren = $result['total_children'] ?? 0;
        $childrenApplyingForGrants = $result['children_applying_for_grants'] ?? 0;
        $childrenNotApplying = $result['children_not_applying'] ?? 0;

        // Notify all admins
        $admins = User::where('roles', 'admin')->get();
        foreach ($applications as $application) {
            Notification::send($admins, new ApplicationStatusNotification(
                'New Application Submitted',
                'A new application has been submitted by ' . Auth::user()->name,
                url('/admin/admin/applications/' . $application->id . '/edit')
            ));
        }

        // Create user notification message
        $notificationMessage = 'Your applications have been submitted successfully. ';
        if ($childrenApplyingForGrants > 0) {
            $notificationMessage .= "We received {$childrenApplyingForGrants} grant application(s).";
        }
        if ($childrenNotApplying > 0) {
            $notificationMessage .= " {$childrenNotApplying} child(ren) were not applying for grants, so no applications were created for them.";
        }
        $notificationMessage .= ' Please wait for our response.';

        // Notify the user
        Auth::user()->notify(new ApplicationStatusNotification(
            'Application Submitted',
            $notificationMessage,
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

        // Validate that at least one child is applying for a grant
        $childrenApplyingForGrants = collect($data['children'])->filter(function ($child) {
            return isset($child['is_applying_for_grant']) && $child['is_applying_for_grant'] === true;
        });

        if ($childrenApplyingForGrants->isEmpty()) {
            $this->addError('children', 'At least one child must be applying for a grant to submit this form.');
            return;
        }

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
            ->label(__('Submit Application'))
            ->submit('create')
            ->keyBindings(['mod+s'])
            ->extraAttributes([
                'class' => 'submit-button'
            ]);
    }
}
