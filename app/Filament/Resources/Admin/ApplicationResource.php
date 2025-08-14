<?php

namespace App\Filament\Resources\Admin;

use App\Filament\Resources\Admin\ApplicationResource\Pages;
use App\Filament\Resources\Admin\ApplicationResource\RelationManagers\DocumentsRelationManager;
use App\Models\Application;
use Filament\Forms;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Components\Wizard;
use Filament\Forms\Components\Wizard\Step;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use App\Notifications\ApplicationStatusNotification;
use App\Models\User;
use Illuminate\Support\Facades\Notification;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Contracts\HasForms;
use Filament\Tables\Actions\ExportAction;
use App\Filament\Exports\ApplicationExporter;
use Filament\Tables\Actions\ExportBulkAction;
use Saade\FilamentAutograph\Forms\Components\SignaturePad;
use App\Services\GoogleDriveService;

class ApplicationResource extends Resource
{

    protected static ?string $model = Application::class;
    protected static string $relationship = 'documents';

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Wizard::make([
                    Step::make('Family Information')->schema([
                        TextInput::make('father_first_name')->required(),
                        TextInput::make('father_last_name')->required(),
                        TextInput::make('father_phone')->required()->tel(),
                        TextInput::make('father_email')->email()->required(),
                        TextInput::make('mother_first_name')->required(),
                        TextInput::make('mother_last_name')->required(),
                        TextInput::make('mother_phone')->required()->tel(),
                        TextInput::make('mother_email')->email()->required(),
                        TextInput::make('home_address')->required(),
                        Select::make('is_additional_address')
                            ->label('Is there an additional address?')
                            ->reactive()
                            ->options([
                                true => 'Yes',
                                false => 'No',
                            ])
                            ->default(false)
                            ->required(),
                        TextInput::make('additional_address')
                            ->label('Additional Address')
                            ->nullable()
                            ->hidden(fn(callable $get) => !$get('is_additional_address')),
                        Select::make('family_status')->options([
                            'single_parent' => 'Single Parent',
                            'married' => 'Married',
                            'other' => 'Other',
                        ])->required(),

                        // Select::make('no_of_children_in_household')
                        //     ->required()
                        //     ->reactive()
                        //     ->afterStateUpdated(function ($state, callable $set, callable $get) {
                        //         $existing = $get('childInfos') ?? [];
                        //         $count = intval($state);
                        //         $new = array_slice($existing, 0, $count);

                        //         while (count($new) < $count) {
                        //             $new[] = [
                        //                 'first_name' => '',
                        //                 'last_name' => '',
                        //                 'date_of_birth' => null,
                        //                 'gender' => null,
                        //                 'current_school' => '',
                        //                 'current_school_location' => null,
                        //                 'is_child_applying_for_grant' => false,
                        //             ];
                        //         }

                        //         $set('childInfos', $new);
                        //     })
                        //     ->options([
                        //         '0' => '0',
                        //         '1' => '1',
                        //         '2' => '2',
                        //         '3' => '3',
                        //         '4' => '4',
                        //         '5' => '5',
                        //         '6' => '6',
                        //         '7' => '7',
                        //         '8' => '8',
                        //         '9' => '9',
                        //         '10' => '10',
                        //         '11' => '11',
                        //         '12' => '12',
                        //         '13' => '13',
                        //         '14' => '14',
                        //         '15' => '15',
                        //     ])->required(),
                        // Repeater::make('childInfos')
                        //     //Auto generate repeater according to the number of children
                        //     ->reactive()
                        //     ->label('Children Information')
                        //     ->addActionLabel('Add Additional Child')
                        //     ->schema([
                        //         TextInput::make('first_name')->required(),
                        //         TextInput::make('last_name')->required(),
                        //         DatePicker::make('date_of_birth')->required(),
                        //         Select::make('gender')
                        //             ->options([
                        //                 'male' => 'Male',
                        //                 'female' => 'Female',
                        //             ]),
                        //         TextInput::make('current_school_name')
                        //             ->label('Current School Name')
                        //             ->required(),
                        //         Select::make('current_school_location')
                        //             ->label('Current School Location')
                        //             ->options([
                        //                 'Alabama' => 'Alabama',
                        //                 'Alaska' => 'Alaska',
                        //                 'Arizona' => 'Arizona',
                        //                 'Arkansas' => 'Arkansas',
                        //                 'California' => 'California',
                        //                 'Colorado' => 'Colorado',
                        //                 'Connecticut' => 'Connecticut',
                        //                 'Delaware' => 'Delaware',
                        //                 'Florida' => 'Florida',
                        //                 'Georgia' => 'Georgia',
                        //                 'Hawaii' => 'Hawaii',
                        //                 'Idaho' => 'Idaho',
                        //                 'Illinois' => 'Illinois',
                        //                 'Indiana' => 'Indiana',
                        //                 'Iowa' => 'Iowa',
                        //                 'Kansas' => 'Kansas',
                        //                 'Kentucky' => 'Kentucky',
                        //                 'Louisiana' => 'Louisiana',
                        //                 'Maine' => 'Maine',
                        //                 'Maryland' => 'Maryland',
                        //                 'Massachusetts' => 'Massachusetts',
                        //                 'Michigan' => 'Michigan',
                        //                 'Minnesota' => 'Minnesota',
                        //                 'Mississippi' => 'Mississippi',
                        //                 'Missouri' => 'Missouri',
                        //                 'Montana' => 'Montana',
                        //                 'Nebraska' => 'Nebraska',
                        //                 'Nevada' => 'Nevada',
                        //                 'New Hampshire' => 'New Hampshire',
                        //                 'New Jersey' => 'New Jersey',
                        //                 'New Mexico' => 'New Mexico',
                        //                 'New York' => 'New York',
                        //                 'North Carolina' => 'North Carolina',
                        //                 'North Dakota' => 'North Dakota',
                        //                 'Ohio' => 'Ohio',
                        //                 'Oklahoma' => 'Oklahoma',
                        //                 'Oregon' => 'Oregon',
                        //                 'Pennsylvania' => 'Pennsylvania',
                        //                 'Rhode Island' => 'Rhode Island',
                        //                 'South Carolina' => 'South Carolina',
                        //                 'South Dakota' => 'South Dakota',
                        //                 'Tennessee' => 'Tennessee',
                        //                 'Texas' => 'Texas',
                        //                 'Utah' => 'Utah',
                        //                 'Vermont' => 'Vermont',
                        //                 'Virginia' => 'Virginia',
                        //                 'Washington' => 'Washington',
                        //                 'West Virginia' => 'West Virginia',
                        //                 'Wisconsin' => 'Wisconsin',
                        //                 'Wyoming' => 'Wyoming',
                        //             ])
                        //             ->required(),
                        //         Checkbox::make('is_child_applying_for_grant')
                        //             ->label('Is this child applying for a grant?')
                        //             ->default(false),
                        //     ])
                        //     ->columns(2)
                        //     ->relationship()
                        //     ->defaultItems(0)
                        //     ->reorderable(false)
                        //     ->cloneable(false)
                        //     ->columnSpanFull(),
                        TextInput::make('synagogue_affiliation')
                            ->label('Are you affiliated with any synagogues? Please enter name and address.')
                            ->required()->columnSpanFull(),
                    ])->columns(3),
                    Step::make('Applicant Information')->schema([
                        TextInput::make('first_name')->required(),
                        TextInput::make('last_name')->required(),
                        DatePicker::make('date_of_birth')->required(),
                        Select::make('gender')->options([
                            'male' => 'Male',
                            'female' => 'Female',
                        ])->required(),
                        TextInput::make('current_school_name')->required(),
                        Select::make('current_school_location')
                            ->label('Current School Location')
                            ->options([
                                'Alabama' => 'Alabama',
                                'Alaska' => 'Alaska',
                                'Arizona' => 'Arizona',
                                'Arkansas' => 'Arkansas',
                                'California' => 'California',
                                'Colorado' => 'Colorado',
                                'Connecticut' => 'Connecticut',
                                'Delaware' => 'Delaware',
                                'Florida' => 'Florida',
                                'Georgia' => 'Georgia',
                                'Hawaii' => 'Hawaii',
                                'Idaho' => 'Idaho',
                                'Illinois' => 'Illinois',
                                'Indiana' => 'Indiana',
                                'Iowa' => 'Iowa',
                                'Kansas' => 'Kansas',
                                'Kentucky' => 'Kentucky',
                                'Louisiana' => 'Louisiana',
                                'Maine' => 'Maine',
                                'Maryland' => 'Maryland',
                                'Massachusetts' => 'Massachusetts',
                                'Michigan' => 'Michigan',
                                'Minnesota' => 'Minnesota',
                                'Mississippi' => 'Mississippi',
                                'Missouri' => 'Missouri',
                                'Montana' => 'Montana',
                                'Nebraska' => 'Nebraska',
                                'Nevada' => 'Nevada',
                                'New Hampshire' => 'New Hampshire',
                                'New Jersey' => 'New Jersey',
                                'New Mexico' => 'New Mexico',
                                'New York' => 'New York',
                                'North Carolina' => 'North Carolina',
                                'North Dakota' => 'North Dakota',
                                'Ohio' => 'Ohio',
                                'Oklahoma' => 'Oklahoma',
                                'Oregon' => 'Oregon',
                                'Pennsylvania' => 'Pennsylvania',
                                'Rhode Island' => 'Rhode Island',
                                'South Carolina' => 'South Carolina',
                                'South Dakota' => 'South Dakota',
                                'Tennessee' => 'Tennessee',
                                'Texas' => 'Texas',
                                'Utah' => 'Utah',
                                'Vermont' => 'Vermont',
                                'Virginia' => 'Virginia',
                                'Washington' => 'Washington',
                                'West Virginia' => 'West Virginia',
                                'Wisconsin' => 'Wisconsin',
                                'Wyoming' => 'Wyoming',
                            ])
                            ->required(),
                        Select::make('current_grade')->options([
                            '1' => '1',
                            '2' => '2',
                            '3' => '3',
                            '4' => '4',
                            '5' => '5',
                            '6' => '6',
                            '7' => '7',
                            '8' => '8',
                            '9' => '9',
                            '10' => '10',
                            '11' => '11',
                            '12' => '12',
                        ])->required(),
                        Select::make('school_year_applying_for')->required()
                            ->options([
                                '2025-2026' => '2025-2026',
                                '2026-2027' => '2026-2027',
                            ]),
                        Select::make('school_wish_to_apply_in')
                            ->maxItems(3)
                            ->multiple()
                            ->options([
                                'School A' => 'School A',
                                'School B' => 'School B',
                                'School C' => 'School C',
                                'School D' => 'School D',
                                'School E' => 'School E',
                                'School F' => 'School F',
                            ])->required()
                            ->columnSpanFull(),
                    ])->columns(2),
                    Step::make('Documents')->schema([
                        Grid::make(2)->schema([
                            FileUpload::make('government_id')
                                ->label('Government ID')
                                ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png'])
                                ->maxSize(10240)
                                ->directory('imgs/docs')
                                ->visibility('private')
                                ->required()
                                ->enableDownload()
                                ->enableOpen()
                                ->disk('google')
                                ->saveUploadedFileUsing(function ($file, $record) {
                                    $path = 'imgs/docs/' . $file->getClientOriginalName();
                                    $stream = fopen($file->getRealPath(), 'r');
                                    app(GoogleDriveService::class)->writeStreamWithRetry($path, $stream);
                                    if (is_resource($stream)) {
                                        fclose($stream);
                                    }
                                    return $path;
                                })
                                ->url(fn($record) => $record?->government_id_url),
                            Group::make([
                                Select::make('status_government_id')
                                    ->label('Government ID Status')
                                    ->options([
                                        'pending' => 'Pending',
                                        'approved' => 'Approved',
                                        'rejected' => 'Rejected',
                                    ])
                                    ->default('pending')
                                    ->required()
                                    ->visible(fn($livewire) => !$livewire instanceof \Filament\Resources\Pages\CreateRecord)
                                    ->disabled(fn($record) => $record && $record->status === 'fix_needed'),
                                Textarea::make('comments_government_id')
                                    ->label('Comments on Government ID')
                                    ->rows(5)
                                    ->maxLength(500)
                                    ->visible(fn($livewire) => !$livewire instanceof \Filament\Resources\Pages\CreateRecord)
                                    ->disabled(fn($record) => $record && $record->status === 'fix_needed'),
                            ]),
                            FileUpload::make('recent_report_card')
                                ->label('2 Years School Report Card')
                                ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png'])
                                ->maxSize(10240)
                                ->multiple()
                                ->maxFiles(2)
                                ->directory('imgs/docs')
                                ->visibility('private')
                                ->required()
                                ->enableDownload()
                                ->enableOpen()
                                ->disk('google')
                                ->saveUploadedFileUsing(function ($file, $record) {
                                    $path = 'imgs/docs/' . $file->getClientOriginalName();
                                    $stream = fopen($file->getRealPath(), 'r');
                                    app(GoogleDriveService::class)->writeStreamWithRetry($path, $stream);
                                    if (is_resource($stream)) {
                                        fclose($stream);
                                    }
                                    return $path;
                                })
                                ->url(fn($record) => $record?->recent_report_card_url),
                            Group::make([
                                Select::make('status_recent_report_card')
                                    ->label('Recent Report Card Status')
                                    ->options([
                                        'pending' => 'Pending',
                                        'approved' => 'Approved',
                                        'rejected' => 'Rejected',
                                    ])
                                    ->default('pending')
                                    ->required()
                                    ->visible(fn($livewire) => !$livewire instanceof \Filament\Resources\Pages\CreateRecord)
                                    ->disabled(fn($record) => $record && $record->status === 'fix_needed'),
                                Textarea::make('comments_recent_report_card')
                                    ->label('Comments on Recent Report Card')
                                    ->rows(5)
                                    ->maxLength(500)
                                    ->visible(fn($livewire) => !$livewire instanceof \Filament\Resources\Pages\CreateRecord)
                                    ->disabled(fn($record) => $record && $record->status === 'fix_needed'),
                            ]),
                            FileUpload::make('marriage_certificate')
                                ->label('Ketuba/Jewish Marriage Certificate')
                                ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png'])
                                ->maxSize(10240)
                                ->directory('imgs/docs')
                                ->visibility('private')
                                ->required()
                                ->enableDownload()
                                ->enableOpen()
                                ->disk('google')
                                ->saveUploadedFileUsing(function ($file, $record) {
                                    $path = 'imgs/docs/' . $file->getClientOriginalName();
                                    $stream = fopen($file->getRealPath(), 'r');
                                    app(GoogleDriveService::class)->writeStreamWithRetry($path, $stream);
                                    if (is_resource($stream)) {
                                        fclose($stream);
                                    }
                                    return $path;
                                })
                                ->url(fn($record) => $record?->marriage_certificate_url),
                            Group::make([
                                Select::make('status_marriage_certificate')
                                    ->label('Marriage Certificate Status')
                                    ->options([
                                        'pending' => 'Pending',
                                        'approved' => 'Approved',
                                        'rejected' => 'Rejected',
                                    ])
                                    ->default('pending')
                                    ->required()
                                    ->visible(fn($livewire) => !$livewire instanceof \Filament\Resources\Pages\CreateRecord)
                                    ->disabled(fn($record) => $record && $record->status === 'fix_needed'),
                                Textarea::make('comments_marriage_certificate')
                                    ->label('Comments on Marriage Certificate')
                                    ->rows(5)
                                    ->maxLength(500)
                                    ->visible(fn($livewire) => !$livewire instanceof \Filament\Resources\Pages\CreateRecord)
                                    ->disabled(fn($record) => $record && $record->status === 'fix_needed'),
                            ]),
                            FileUpload::make('recent_utility_bill')
                                ->label('Recent Utility Bill (Electric, Gas or Cable Bill)')
                                ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png'])
                                ->maxSize(10240)
                                ->directory('imgs/docs')
                                ->visibility('private')
                                ->required()
                                ->enableDownload()
                                ->enableOpen()
                                ->disk('google')
                                ->saveUploadedFileUsing(function ($file, $record) {
                                    $path = 'imgs/docs/' . $file->getClientOriginalName();
                                    $stream = fopen($file->getRealPath(), 'r');
                                    app(GoogleDriveService::class)->writeStreamWithRetry($path, $stream);
                                    if (is_resource($stream)) {
                                        fclose($stream);
                                    }
                                    return $path;
                                })
                                ->url(fn($record) => $record?->recent_utility_bill_url),
                            Group::make([
                                Select::make('status_recent_utility_bill')
                                    ->label('Recent Utility Bill Status')
                                    ->options([
                                        'pending' => 'Pending',
                                        'approved' => 'Approved',
                                        'rejected' => 'Rejected',
                                    ])
                                    ->default('pending')
                                    ->required()
                                    ->visible(fn($livewire) => !$livewire instanceof \Filament\Resources\Pages\CreateRecord)
                                    ->disabled(fn($record) => $record && $record->status === 'fix_needed'),
                                Textarea::make('comments_recent_utility_bill')
                                    ->label('Comments on Recent Utility Bill')
                                    ->rows(5)
                                    ->maxLength(500)
                                    ->visible(fn($livewire) => !$livewire instanceof \Filament\Resources\Pages\CreateRecord)
                                    ->disabled(fn($record) => $record && $record->status === 'fix_needed'),
                            ]),
                        ]),

                    ]),
                    Step::make('Declaration')->schema([
                        Checkbox::make('info_is_true')
                            ->label('I declare that all information provided is true and accurate.')
                            ->required(),
                        Checkbox::make('applicants_are_jewish')
                            ->label('I declare that the applicants are Jewish.')
                            ->required(),
                        Checkbox::make('parent_is_of_bukharian_descent')
                            ->label('I declare that at least one parent is of Bukharian descent.')
                            ->required(),
                        Checkbox::make('applicant_has_attended_school_in_the_past_year')
                            ->label('I declare that the applicant has attended school in the past year.')
                            ->required(),
                        SignaturePad::make('declearation_signature')
                            ->label('Declaration Signature')
                            ->extraAttributes(['style' => 'width:350px; height:150px;'])
                            ->backgroundColor('rgba(255,255,255,1)')
                            ->penColor('#000')
                            ->dotSize(1.0)
                            ->lineMinWidth(0.5)
                            ->lineMaxWidth(0.5)
                            ->throttle(16)
                            ->minDistance(1)
                            ->velocityFilterWeight(1.0)
                            ->required(),
                        DatePicker::make('declearation_date')
                            ->label('Declaration Date')
                            //Auto fill with current date
                            ->default(now())
                            ->required(),
                        Textarea::make('additional_notes')
                            ->label('Additional Notes')
                            ->nullable()
                            ->rows(3)
                            ->maxLength(500),
                    ]),

                    Step::make('Status')->schema([
                        Select::make('status')
                            ->label('Application Status')
                            ->options([
                                'submitted' => 'Submitted',
                                // 'pending' => 'Pending',
                                'approved' => 'Approved',
                                'rejected' => 'Rejected',
                                'fix_needed' => 'Fix Needed',
                                'resubmitted' => 'Resubmitted',
                            ])
                            ->required(),
                    ]),
                ])
                    ->columnSpanFull()
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->label('ID')->sortable(),
                Tables\Columns\TextColumn::make('first_name')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('date_of_birth')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('gender')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('current_school_name')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('current_school_location')->searchable()->sortable(),
                Tables\Columns\BadgeColumn::make('status')
                    ->formatStateUsing(fn($state) => [
                        'submitted' => 'Submitted',
                        'approved' => 'Approved',
                        'rejected' => 'Rejected',
                        'fix_needed' => 'Fix Needed',
                        'resubmitted' => 'Resubmitted',
                    ][$state] ?? $state)
                    ->colors([
                        'primary' => 'submitted',
                        'success' => 'approved',
                        'danger' => 'rejected',
                        'warning' => 'fix_needed',
                        'info' => 'resubmitted',
                    ])
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')->dateTime()->sortable(),
            ])->defaultSort('created_at', 'desc')
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\ViewAction::make(),
            ])
            ->headerActions([
                ExportAction::make()->exporter(ApplicationExporter::class),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
                ExportBulkAction::make()->exporter(ApplicationExporter::class),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListApplications::route('/'),
            'create' => Pages\CreateApplication::route('/create'),
            'edit' => Pages\EditApplication::route('/{record}/edit'),
        ];
    }

    public function syncChildRepeater($childrenCount, callable $set)
    {
        $existing = $this->data['childInfos'] ?? [];

        $count = intval($childrenCount);

        // Reuse existing values, fill rest with empty
        $new = array_slice($existing, 0, $count);

        while (count($new) < $count) {
            $new[] = [
                'first_name' => '',
                'last_name' => '',
                'date_of_birth' => null,
                'gender' => null,
                'learning_issue' => false,
                'learning_issue_what_description' => null,
                'name_of_school' => '',
                'grade' => '',
                'help_selecting_jewish_school' => false,
                'current_school_attending' => '',
                'years_attended' => '',
                'street_address' => '',
                'city' => '',
                'state' => '',
            ];
        }

        $set('childInfos', $new);
    }
}
