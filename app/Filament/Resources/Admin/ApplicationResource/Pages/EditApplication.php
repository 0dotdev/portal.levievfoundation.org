<?php

namespace App\Filament\Resources\Admin\ApplicationResource\Pages;

use App\Filament\Resources\Admin\ApplicationResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use App\Notifications\ApplicationStatusNotification;
use App\Traits\CommonTrait;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Section;
use Filament\Forms\Form;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Wizard;
use Filament\Forms\Components\Wizard\Step;
use Illuminate\Database\Eloquent\Builder;
use Saade\FilamentAutograph\Forms\Components\SignaturePad;

class EditApplication extends EditRecord
{
    protected static string $resource = ApplicationResource::class;

    use CommonTrait;

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
                                            )
                                            ->columnSpan(1),
                                        Select::make('status')
                                            ->options([
                                                'pending' => 'Pending Review',
                                                'approved' => 'Approved',
                                                'rejected' => 'Rejected'
                                            ])
                                            ->required(),
                                        Textarea::make('comments')
                                            ->label('Review Comments')
                                            ->placeholder('Add any comments about this document')
                                            ->rows(2),
                                    ]),
                            ])
                            ->columns(4)
                            ->addable(false)
                            ->deletable(false)
                            ->reorderable(false),
                    ]),
                Section::make('Application Status')
                    ->schema([
                        Select::make('status')
                            ->options(self::applicationStatus())
                            ->required(),
                        Textarea::make('admin_comments')
                            ->rows(3),
                    ])->columns(1),
            ]);
    }

    // Store original document statuses before save
    protected array $originalDocumentStatuses = [];

    protected function beforeSave(): void
    {
        // Update the reviewed_at and reviewed_by fields
        $this->record->reviewed_at = now();
        $this->record->reviewed_by = \Illuminate\Support\Facades\Auth::id();

        // Store original document statuses
        $this->originalDocumentStatuses = $this->record->documents()->pluck('status', 'id')->toArray();

        // If a new document is uploaded, set its status to 'pending'
        if ($this->record->documents && is_iterable($this->record->documents)) {
            foreach ($this->record->documents as $doc) {
                if (!empty($doc->new_document)) {
                    $doc->status = 'pending';
                }
            }
        }
    }

    protected function afterSave(): void
    {
        $record = $this->record;

        // Check if all required documents are approved
        $allDocumentsApproved = $record->documents()
            ->where('status', '!=', 'approved')
            ->count() === 0;

        // If all documents are approved, update the application status accordingly
        if ($allDocumentsApproved && $record->status === 'pending') {
            $record->status = 'approved';
            $record->save();
        }

        // Only notify about rejected documents if any document's status was changed to 'rejected'
        $currentStatuses = $record->documents()->pluck('status', 'id')->toArray();
        $newlyRejectedIds = [];
        foreach ($currentStatuses as $id => $status) {
            if (($this->originalDocumentStatuses[$id] ?? null) !== 'rejected' && $status === 'rejected') {
                $newlyRejectedIds[] = $id;
            }
        }
        if (count($newlyRejectedIds) > 0) {
            $rejectedDocuments = $record->documents()->whereIn('id', $newlyRejectedIds)->get();
            $rejectedNames = $rejectedDocuments->map(function ($doc) {
                $types = [
                    'government_id' => 'Government ID',
                    'marriage_certificate' => 'Marriage Certificate',
                    'recent_utility_bill' => 'Recent Utility Bill',
                    'school_report_card_2_years' => 'School Report Card (2 Years)'
                ];
                return $types[$doc->document_type] ?? ucfirst(str_replace('_', ' ', $doc->document_type));
            })->implode(', ');
            optional($record->user)->notify(new ApplicationStatusNotification(
                'Document Rejected',
                'The following document(s) were rejected: ' . $rejectedNames . '. Please review and re-upload.',
                url('/dashboard/applications')
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
