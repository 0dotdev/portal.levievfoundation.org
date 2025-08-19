<?php

namespace App\Filament\Resources\Dashboard;

use App\Filament\Resources\Dashboard\ApplicationResource\Pages;
use App\Models\Application;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\Admin\ApplicationResource\RelationManagers\DocumentsRelationManager;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Wizard;
use Filament\Forms\Components\Wizard\Step;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use App\Notifications\ApplicationStatusNotification;
use App\Models\User;
use App\Models\Document;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Log;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Contracts\HasForms;
use Filament\Tables\Actions\ExportAction;
use App\Filament\Exports\ApplicationExporter;
use Filament\Tables\Actions\ExportBulkAction;
use Filament\Tables\Columns\TextColumn;
use Saade\FilamentAutograph\Forms\Components\SignaturePad;
use Illuminate\Support\Facades\Storage;
use App\Services\GoogleDriveService;
use App\Traits\CommonTrait;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\Fieldset;
use Filament\Infolists\Components\TextEntry;
use Illuminate\Support\HtmlString;

class ApplicationResource extends Resource
{
    use CommonTrait;

    protected static ?string $model = Application::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $slug = 'applications';

    protected static ?string $recordTitleAttribute = 'first_name';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['parent']);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Wizard::make([
                    Step::make('Parent Information')->schema([
                        Section::make("Father's Information")->columns([
                            'sm' => 2,
                            'xl' => 4,
                        ])
                            ->schema([
                                TextInput::make('father_first_name')->label('First Name')->required(),
                                TextInput::make('father_last_name')->label('Last Name')->required(),
                                TextInput::make('father_phone')
                                    ->label('Phone')
                                    ->mask('(999) 999-9999')
                                    ->placeholder('')
                                    ->required()
                                    ->tel(),
                                TextInput::make('father_email')->label('Email')->email()->required(),
                                Fieldset::make('Address')
                                    ->schema([
                                        TextInput::make('father_address')->label('Street Address')->required(),
                                        TextInput::make('father_city')->label('City')->required(),
                                        Select::make('father_state')->label('State')->options(self::states())->required(),
                                        TextInput::make('father_pincode')->label('Zipcode')->required()->maxLength(6)
                                    ])
                                    ->columns(4),

                            ]),
                        Section::make("Mother's Information")->columns([
                            'sm' => 3,
                            'xl' => 4,
                        ])->schema([
                            TextInput::make('mother_first_name')->label('First Name')->required(),
                            TextInput::make('mother_last_name')->label('Last Name')->required(),
                            TextInput::make('mother_phone')->label('Phone')
                                ->mask('(999) 999-9999')
                                ->placeholder('')
                                ->required()
                                ->tel(),
                            TextInput::make('mother_email')->label('Email')->email()->required(),
                            Checkbox::make('mother_has_different_address')->label('Mother has a different address?')->reactive(),
                            Fieldset::make('Address')
                                ->schema([
                                    TextInput::make('mother_address')->label('Street Address')->required()
                                        ->hidden(fn(callable $get) => !$get('mother_has_different_address')),
                                    TextInput::make('mother_city')->label('City')->required(),
                                    Select::make('mother_state')->label('State')->options(self::states())->required(),
                                    TextInput::make('mother_pincode')->label('Zipcode')->required()->numeric()->maxLength(6),
                                ])
                                ->columns(4)->hidden(fn(callable $get) => !$get('mother_has_different_address')),
                        ]),
                        Section::make('Family Information')->columns([
                            'sm' => 3,
                            'xl' => 4,
                        ])->schema([
                            Select::make('family_status')
                                ->options(self::familyStatuses())
                                ->required()->default('married'),
                            Select::make('no_of_children_in_household')
                                ->label('Number of children in household')
                                ->options(self::householdChildren())
                                ->required()
                                ->reactive()
                                ->afterStateUpdated(function ($state, $set) {
                                    if (is_numeric($state)) {
                                        $set('children', array_fill(0, (int)$state, []));
                                    }
                                }),
                            TextInput::make('synagogue_affiliation')
                                ->placeholder(' Please enter name and address.')
                                ->label('Affiliated with any synagogues?')
                                ->required(),
                        ]),
                    ]),
                    Step::make('Children Information')->schema([
                        Repeater::make('children')
                            ->label('Childrens Information')
                            ->schema([
                                TextInput::make('first_name')->label('First Name')->required(),
                                TextInput::make('last_name')->label('Last Name')->required(),
                                DatePicker::make('date_of_birth')->label('Date of Birth')->maxDate(now())->minDate(now()->subYears(150))->required(),
                                Select::make('gender')->label('Gender')->options(static::genders())->required(),
                                TextInput::make('current_school_name')->label('Current School Name')->required(),
                                Select::make('current_school_location')->label('Current School Location')->options(self::states())->required(),
                                Select::make('current_grade')->label('Current Grade')->options(self::schoolGrades())->required(),
                                Checkbox::make('is_applying_for_grant')->label('Is this student applying for grant?')->reactive()->columnSpan(4),
                                Group::make([
                                    Select::make('school_year_applying_for')->label('School Year Applying For')->options(self::applyingYears())->required(),
                                    Select::make('school_wish_to_apply_in')->label('Schools You Wish to Apply To')->multiple()->maxItems(3)->options(self::applyingSchools())->required(),
                                    Checkbox::make('attended_school_past_year')
                                        ->label('Has the applicant attended school in the past year?'),
                                    FileUpload::make('recent_report_card')
                                        ->label('School Report Card for the Past 2 Years')
                                        ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png'])
                                        ->maxSize(10240)
                                        ->multiple()
                                        ->previewable(false)
                                        ->maxFiles(2)
                                        ->directory('documents/report-cards')
                                        ->visibility('private')
                                        ->required(),

                                ])->columns(3)->visible(fn(callable $get) => $get('is_applying_for_grant'))->columnSpanFull(),
                            ])
                            ->reactive()
                            ->minItems(fn($get) => max((int)$get('no_of_children_in_household'), 1))
                            ->defaultItems(fn($get) => max((int)$get('no_of_children_in_household'), 1))
                            ->addActionLabel('Add additional child')
                            ->itemLabel(function ($state, $livewire) {
                                return 'Child Info';
                            })
                            ->columns(4)
                    ]),
                    Step::make('Documents')->schema(
                        fn($livewire) => $livewire instanceof \Filament\Resources\Pages\CreateRecord
                            ? [
                                Grid::make(3)->schema([
                                    FileUpload::make('government_id')
                                        ->label('Government ID')
                                        ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png'])
                                        ->maxSize(10240)
                                        ->multiple()
                                        ->maxFiles(2)
                                        ->previewable(false)
                                        ->directory('documents/government_id')
                                        ->required()
                                        ->visibility('private'),
                                    FileUpload::make('marriage_certificate')
                                        ->label('Ketuba/Jewish Marriage Certificate')
                                        ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png'])
                                        ->maxSize(10240)
                                        ->multiple()
                                        ->maxFiles(2)
                                        ->previewable(false)
                                        ->directory('documents/marriage_certificate')
                                        ->required()
                                        ->visibility('private'),
                                    FileUpload::make('recent_utility_bill')
                                        ->label('Recent Utility Bill (Electric, Gas or Cable Bill)')
                                        ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png'])
                                        ->maxSize(10240)
                                        ->multiple()
                                        ->maxFiles(2)
                                        ->required()
                                        ->previewable(false)
                                        ->directory('documents/recent_utility_bill')
                                        ->visibility('private')
                                ]),
                            ]
                            : [
                                Grid::make(2)->schema([
                                    FileUpload::make('government_id')
                                        ->label('Government ID')
                                        ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png'])
                                        ->maxSize(10240)
                                        ->multiple()
                                        ->maxFiles(2)
                                        ->previewable(false)
                                        ->directory('documents')
                                        ->visibility('private')
                                        ->required(),
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
                                        ->label('School Report Card for the Past 2 Years')
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
                                        }),
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
                                        }),
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
                                        }),
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
                            ]
                    ),
                    Step::make('Declaration')->schema([
                        Checkbox::make('info_is_true')
                            ->label('I declare that all information provided is true and accurate.')
                            ->rule('accepted')
                            ->required(),
                        Checkbox::make('applicants_are_jewish')
                            ->label('I declare that the applicants are Jewish.')
                            ->rule('accepted')
                            ->required(),
                        Checkbox::make('parent_is_of_bukharian_descent')
                            ->label('I declare that at least one parent is of Bukharian descent.')
                            ->rule('accepted')
                            ->required(),
                        Group::make([
                            Textarea::make('additional_notes')
                                ->label('Additional Notes')
                                ->nullable()
                                ->rows(4)
                                ->maxLength(500)
                                ->columnSpanFull(),
                            Grid::make(2)->schema([
                                DatePicker::make('declaration_date')
                                    ->label('Declaration Date')
                                    //Auto fill with current date
                                    ->default(now())
                                    ->required()
                                    ->columnSpan(1),
                                SignaturePad::make('declaration_signature')
                                    ->label('Declaration Signature')
                                    ->extraAttributes(['style' => 'width:220px; height:90px;'])
                                    ->backgroundColor('rgba(255,255,255,1)')
                                    ->penColor('#222')
                                    ->dotSize(0.7)
                                    ->lineMinWidth(0.3)
                                    ->lineMaxWidth(1.2)
                                    ->throttle(8)
                                    ->minDistance(0.5)
                                    ->velocityFilterWeight(0.7)
                                    ->required()
                                    ->columnSpan(1),
                            ])->columns(3),

                        ])->columns(1),


                    ]),
                ])->columnSpanFull()->persistStepInQueryString()->nextAction(
                    fn(Action $action) => $action->label('Proceed to Next Step'),
                )->previousAction(
                    fn(Action $action) => $action->label('Back to Previous Step'),
                )
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordUrl(null)
            ->columns([
                Tables\Columns\TextColumn::make('id')->label('ID')->sortable(),
                TextColumn::make('first_name')
                    ->label('Name')
                    ->formatStateUsing(fn($record) => $record->first_name . ' ' . $record->last_name)
                    ->searchable(['first_name', 'last_name']),
                TextColumn::make('date_of_birth')->searchable(),
                TextColumn::make('gender')->searchable()->formatStateUsing(fn($state) => self::genders()[$state] ?? $state),
                TextColumn::make('current_school_name')->label('Current School')->description(fn($record): string => $record->current_school_location)->searchable(),
                TextColumn::make('current_grade')->searchable(),
                TextColumn::make('is_applying_for_grant')->label('Applying for Grant?')
                    ->formatStateUsing(fn($state) => $state ? 'Yes' : 'No')
                    ->searchable(),
                TextColumn::make('created_at')
                    ->label('Created At')
                    ->date()
                    ->sortable()
                    ->searchable(),
                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn($state) => self::applicationStatus()[$state] ?? $state)
                    ->colors(self::applicationColors()),

            ])->defaultSort('created_at', 'desc')
            ->filters([])->actions([
                Tables\Actions\EditAction::make()
                    ->visible(
                        fn($record) =>
                        $record->user_id === \Illuminate\Support\Facades\Auth::id() &&
                            $record->status === 'fix_needed'
                    ),
                Tables\Actions\ViewAction::make()
                    ->form([
                        Section::make('Applicant Information')
                            ->schema([
                                TextInput::make('first_name')
                                    ->label('Full Name')
                                    ->formatStateUsing(fn($record) => $record->first_name . ' ' . $record->last_name)
                                    ->disabled(),
                                TextInput::make('date_of_birth')
                                    ->disabled(),
                                TextInput::make('gender')
                                    ->formatStateUsing(fn($state) => ucfirst($state))
                                    ->disabled(),
                                TextInput::make('current_school_name')
                                    ->label('Current School')
                                    ->formatStateUsing(fn($record) => $record->current_school_name . ' (' . $record->current_school_location . ')')
                                    ->disabled(),
                                TextInput::make('current_grade')
                                    ->disabled(),
                            ])->columns(3),

                        Section::make('Application Details')
                            ->schema([
                                TextInput::make('school_year_applying_for')
                                    ->label('Applying for Year')
                                    ->disabled(),
                                TextInput::make('is_applying_for_grant')
                                    ->label('Applying for Grant')
                                    ->formatStateUsing(fn($state) => $state ? 'Yes' : 'No')
                                    ->disabled(),
                                TextArea::make('school_wish_to_apply_in')
                                    ->label('Applying for School')
                                    ->formatStateUsing(function ($state) {
                                        if (is_array($state)) {
                                            return !empty($state) ? implode(', ', $state) : null;
                                        }

                                        $decoded = json_decode($state, true);

                                        if (empty($decoded)) {
                                            return null;
                                        }

                                        return implode(', ', $decoded);
                                    })
                                    ->disabled(),
                                TextInput::make('attended_school_past_year')
                                    // ->label('attended_school_past_year')
                                    ->formatStateUsing(fn($state) => $state ? 'Yes' : 'No')
                                    ->disabled(),
                                TextInput::make('status')
                                    ->formatStateUsing(fn($state) => ucfirst($state))
                                    ->disabled(),
                                Textarea::make('admin_comments')
                                    ->label('Review Comments')
                                    ->rows(2)
                            ])->columns(3),
                        Section::make('Student Documents')
                            ->schema([
                                Repeater::make('documents')
                                    ->relationship('documents', fn(Builder $query) => $query->where('reference_type', 'child'))
                                    ->schema([
                                        Grid::make(3)
                                            ->schema([
                                                Select::make('document_type')
                                                    ->options([
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
                                                Select::make('status')
                                                    ->options([
                                                        'pending' => 'Pending Review',
                                                        'approved' => 'Approved',
                                                        'rejected' => 'Rejected'
                                                    ])
                                                    ->required(),
                                                Textarea::make('comments')
                                                    ->label('Comments')
                                                    ->rows(2),
                                            ]),

                                    ])
                                    ->columns(2)
                                    ->addable(false)
                                    ->deletable(false)
                                    ->reorderable(false),
                            ]),

                        Section::make('Parent Documents')
                            ->schema([
                                Repeater::make('parentDocuments')
                                    ->relationship('parentDocuments')
                                    ->schema([
                                        Grid::make(3)
                                            ->schema([
                                                Select::make('document_type')
                                                    ->options([
                                                        'government_id' => 'Government ID',
                                                        'marriage_certificate' => 'Marriage Certificate',
                                                        'recent_utility_bill' => 'Recent Utility Bill'
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
                                                Select::make('status')
                                                    ->options([
                                                        'pending' => 'Pending Review',
                                                        'approved' => 'Approved',
                                                        'rejected' => 'Rejected'
                                                    ])
                                                    ->required(),
                                                Textarea::make('comments')
                                                    ->label('Comments')
                                                    ->rows(2)
                                            ]),
                                    ])
                                    ->columns(1)
                                    ->addable(false)
                                    ->deletable(false)
                                    ->reorderable(false)

                            ]),
                        Section::make('Parent Information')
                            ->schema([
                                Grid::make(2)->schema([
                                    Group::make([
                                        TextInput::make('parent.father_first_name')
                                            ->label('Father Name')
                                            ->formatStateUsing(fn($record) => $record->parent->father_first_name . ' ' . $record->parent->father_last_name)
                                            ->disabled(),
                                        TextInput::make('parent.father_phone')
                                            ->formatStateUsing(fn($record) => $record->parent->father_phone)
                                            ->disabled(),
                                        TextInput::make('parent.father_email')
                                            ->formatStateUsing(fn($record) => $record->parent->father_email)
                                            ->disabled(),
                                        TextInput::make('parent.father_address')
                                            ->label('Address')
                                            ->formatStateUsing(fn($record) =>
                                            $record->parent->father_address . ', ' .
                                                $record->parent->father_city . ', ' .
                                                $record->parent->father_state . ' ' .
                                                $record->parent->father_pincode)
                                            ->disabled(),
                                    ]),
                                    Group::make([
                                        TextInput::make('parent.mother_first_name')
                                            ->label('Mother Name')
                                            ->formatStateUsing(fn($record) => $record->parent->mother_first_name . ' ' . $record->parent->mother_last_name)
                                            ->disabled(),
                                        TextInput::make('parent.mother_phone')
                                            ->formatStateUsing(fn($record) => $record->parent->mother_phone)
                                            ->disabled(),
                                        TextInput::make('parent.mother_email')
                                            ->formatStateUsing(fn($record) => $record->parent->mother_email)
                                            ->disabled(),
                                        TextInput::make('parent.mother_address')
                                            ->label('Address')
                                            ->formatStateUsing(fn($record) =>
                                            $record->parent->mother_has_different_address ?
                                                $record->parent->mother_address . ', ' .
                                                $record->parent->mother_city . ', ' .
                                                $record->parent->mother_state . ' ' .
                                                $record->parent->mother_pincode
                                                : 'Same as Father')
                                            ->disabled(),
                                    ]),
                                    TextInput::make('parent.family_status')
                                        ->label('Family Status')
                                        ->formatStateUsing(fn($record) => ucfirst($record->parent->family_status))
                                        ->disabled(),
                                    TextInput::make('parent.synagogue_affiliation')
                                        ->label('Synagogue Affiliation')
                                        ->formatStateUsing(fn($record) => $record->parent->synagogue_affiliation)
                                        ->disabled(),
                                ]),

                            ]),
                    ]),
            ])
            ->bulkActions([]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListApplications::route('/'),
            'create' => Pages\CreateApplication::route('/create'),
            'edit' => Pages\EditApplication::route('/{record}/edit'),
        ];
    }
}
