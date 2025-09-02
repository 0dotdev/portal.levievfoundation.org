<?php

namespace App\Filament\Resources\Dashboard\ApplicationResource\Pages;

use App\Filament\Resources\Dashboard\ApplicationResource;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Notification;
use App\Models\User;
use Filament\Actions;
use App\Notifications\ApplicationStatusNotification;
use App\Traits\CommonTrait;
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
                            ->native(false)
                            ->visible(fn($record) => $record->is_applying_for_grant)
                            ->maxItems(3)
                            ->default(fn($record) => $record->school_wish_to_apply_in ?? [])
                            ->afterStateHydrated(function ($component, $state) {
                                if (is_string($state)) {
                                    $state = json_decode($state, true) ?? [];
                                    $component->state($state);
                                }
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
                                        FileUpload::make('new_document')
                                            ->label('Upload New Document')
                                            ->visible(fn($get) => $get('status') === 'rejected')
                                            ->directory('documents/reuploads'),
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
    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['school_wish_to_apply_in'] = json_encode($data['school_wish_to_apply_in'] ?? []);

        // If the application was in 'fix_needed' status, change it to 'resubmitted'
        if ($this->record->status === 'fix_needed') {
            $data['status'] = 'resubmitted';
        }

        // If a new document is uploaded, set its status to 'pending'
        if (!empty($data['documents']) && is_array($data['documents'])) {
            foreach ($data['documents'] as &$doc) {
                if (!empty($doc['new_document'])) {
                    $doc['status'] = 'pending';
                }
            }
            unset($doc);
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
