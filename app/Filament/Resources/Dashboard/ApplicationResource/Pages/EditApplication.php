<?php

namespace App\Filament\Resources\Dashboard\ApplicationResource\Pages;

use App\Filament\Resources\Dashboard\ApplicationResource;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Notification;
use App\Models\Document;
use App\Models\User;
use Filament\Actions;
use App\Notifications\ApplicationStatusNotification;
use App\Traits\CommonTrait;
use Filament\Notifications\Notification as FilamentNotification;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Section;
use Filament\Forms\Form;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;

class EditApplication extends EditRecord
{
    use CommonTrait;

    protected static string $resource = ApplicationResource::class;

    // Captures document file paths before save so afterSave() can detect which were re-uploaded
    protected array $originalDocumentFilePaths = [];

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

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Student Information')
                    ->description('Student details being reviewed')
                    ->schema([
                        TextInput::make('first_name')->required(),
                        TextInput::make('last_name')->required(),
                        DatePicker::make('date_of_birth')
                            ->required()
                            ->maxDate(now())
                            ->minDate(now()->subYears(25)),
                        Select::make('gender')
                            ->options(self::genders())
                            ->required(),
                        TextInput::make('current_school_name')->required(),
                        Select::make('current_school_location')
                            ->options(self::states())
                            ->required(),
                        Select::make('current_grade')
                            ->options(self::schoolGrades())
                            ->required(),
                        Select::make('school_year_applying_for')
                            ->options(self::applyingYears())
                            ->required()
                            ->visible(fn($record) => $record->is_applying_for_grant),
                        Select::make('school_wish_to_apply_in')
                            ->multiple()
                            ->options(self::applyingSchools())
                            ->required()
                            ->searchable()
                            ->preload()
                            ->optionsLimit(100)
                            ->native(false)
                            ->visible(fn($record) => $record->is_applying_for_grant)
                            ->maxItems(3)
                            ->default(fn($record) => $record->school_wish_to_apply_in ?? [])
                            ->reactive()
                            ->afterStateHydrated(function ($component, $state) {
                                if (is_string($state)) {
                                    $state = json_decode($state, true) ?? [];
                                    $component->state($state);
                                }
                            }),
                        Checkbox::make('attended_school_past_year')
                            ->label('Have You Started the Application Process for This School'),
                        TextInput::make('custom_school_details')
                            ->label('School Name with Address')
                            ->placeholder('Enter school name and address')
                            ->visible(function (callable $get) {
                                $schools = $get('school_wish_to_apply_in') ?? [];
                                return in_array('School Not Listed / Other', $schools);
                            })
                            ->required(function (callable $get) {
                                $schools = $get('school_wish_to_apply_in') ?? [];
                                return in_array('School Not Listed / Other', $schools);
                            }),
                    ])->columns(3),

                Section::make('Parent Information')
                    ->schema([
                        Grid::make(2)->schema([
                            Section::make("Father's Information")->schema([
                                TextInput::make('father_first_name')->required(),
                                TextInput::make('father_last_name')->required(),
                                TextInput::make('father_phone')
                                    ->required()
                                    ->tel()
                                    ->mask('(999) 999-9999'),
                                TextInput::make('father_email')
                                    ->required()
                                    ->email(),
                                TextInput::make('father_address')->required(),
                                TextInput::make('father_city')->required(),
                                Select::make('father_state')
                                    ->options(self::states())
                                    ->required(),
                                TextInput::make('father_pincode')
                                    ->required()
                                    ->numeric(),
                            ])->columns(4),
                            Section::make("Mother's Information")->schema([
                                TextInput::make('mother_first_name')->required(),
                                TextInput::make('mother_last_name')->required(),
                                TextInput::make('mother_phone')
                                    ->required()
                                    ->tel()
                                    ->mask('(999) 999-9999'),
                                TextInput::make('mother_email')
                                    ->required()
                                    ->email(),
                                Checkbox::make('mother_has_different_address')
                                    ->label('Different address than father?')
                                    ->reactive(),
                                Group::make([
                                    TextInput::make('mother_address')->required(),
                                    TextInput::make('mother_city')->required(),
                                    Select::make('mother_state')
                                        ->options(self::states())
                                        ->required(),
                                    TextInput::make('mother_pincode')
                                        ->required()
                                        ->numeric(),
                                ])->columns(2)
                                    ->visible(fn($get) => $get('mother_has_different_address')),
                            ])->columns(2),
                        ]),
                    ]),

                Section::make('Documents Review')
                    ->schema([
                        Repeater::make('documents')
                            ->relationship('documents')
                            ->schema([
                                Grid::make(3)
                                    ->schema([
                                        Select::make('document_type')
                                            ->options([
                                                'government_id' => 'Government ID',
                                                'marriage_certificate' => 'Marriage Certificate',
                                                'recent_utility_bill' => 'Recent Utility Bill',
                                                'school_report_card_2_years' => 'School Report Card (2 Years)'
                                            ])
                                            ->disabled()
                                            ->dehydrated(false)
                                            ->suffixAction(
                                                Action::make('preview')
                                                    ->icon('heroicon-m-eye')
                                                    ->label('Preview Document')
                                                    ->url(fn($record) => $record?->getPreviewUrl())
                                                    ->openUrlInNewTab()
                                                    ->visible(fn($record) => $record?->getPreviewUrl() !== null)
                                            ),
                                        Placeholder::make('status_info')
                                            ->label('Status')
                                            ->content(fn($record) => match ($record->status) {
                                                'pending' => 'Pending Review',
                                                'approved' => 'Approved',
                                                'rejected' => 'Rejected',
                                                default => ucfirst($record->status)
                                            }),
                                        Placeholder::make('comments_info')
                                            ->label('Review Comments')
                                            ->content(fn($record) => $record->comments ?? '-'),
                                        FileUpload::make('file_path')
                                            ->label('Upload New Document')
                                            ->visible(fn($record) => $record?->status === 'rejected')
                                            ->directory('documents/reuploads')
                                            ->afterStateHydrated(fn($component) => $component->state(null))
                                            ->dehydrated(fn($state) => filled($state)),
                                    ])
                            ])
                            ->addable(false)
                            ->deletable(false)
                            ->reorderable(false),
                    ]),

                Section::make('Application Status')
                    ->schema([
                        Placeholder::make('admin_comments')
                            ->label('Comments')
                            ->content(fn($record) => $record->admin_comments ?? '-'),
                    ])->columns(1),
            ]);
    }
    protected function getSavedNotification(): ?FilamentNotification
    {
        // If documents were re-uploaded, afterSave() sends its own notification.
        // Return null here to suppress the default "Saved" toast in that case.
        if (count($this->originalDocumentFilePaths) > 0) {
            $currentPaths = $this->record->documents()->pluck('file_path', 'id')->toArray();
            foreach ($currentPaths as $id => $path) {
                if (($this->originalDocumentFilePaths[$id] ?? null) !== $path) {
                    return null;
                }
            }
        }

        return FilamentNotification::make()->title('Saved')->success();
    }

    protected function beforeSave(): void
    {
        // Snapshot current file paths before Filament saves the relationship.
        // afterSave() compares against this to find which documents were re-uploaded.
        $this->originalDocumentFilePaths = $this->record->documents()
            ->pluck('file_path', 'id')
            ->toArray();
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

        // Detect which documents had their file_path changed (i.e. user re-uploaded a file).
        // Filament has already saved file_path via the relationship by this point.
        $currentPaths = $record->documents()->pluck('file_path', 'id')->toArray();
        $reuploadedIds = [];
        foreach ($currentPaths as $id => $path) {
            if (($this->originalDocumentFilePaths[$id] ?? null) !== $path) {
                $reuploadedIds[] = $id;
            }
        }
        $hadDocumentUploads = count($reuploadedIds) > 0;

        // Reset status to 'pending' for re-uploaded documents
        if ($hadDocumentUploads) {
            Document::whereIn('id', $reuploadedIds)->update(['status' => 'pending']);
        }

        // Notify admins about the resubmission, mentioning document re-uploads if applicable
        if ($record->status === 'resubmitted') {
            $adminUrl = url('/admin/admin/applications/' . $record->id . '/edit');
            $adminMessage = 'An application has been resubmitted by ' . optional($record->user)->name . '.';
            if ($hadDocumentUploads) {
                $adminMessage .= ' Corrected document(s) have been uploaded and require re-review.';
            }

            $adminEmails = self::adminEmails();

            Notification::route('mail', $adminEmails)->notify(new ApplicationStatusNotification(
                'Application Resubmitted',
                $adminMessage,
                $adminUrl
            ));
        }

        // Show a success notification to the user when documents were uploaded
        if ($hadDocumentUploads) {
            FilamentNotification::make()
                ->title('Document uploaded successfully.')
                ->body('Admin is reviewing your application.')
                ->success()
                ->send();
        }
    }
}
