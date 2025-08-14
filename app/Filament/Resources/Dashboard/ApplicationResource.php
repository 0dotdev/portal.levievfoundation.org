<?php

namespace App\Filament\Resources\Dashboard;

use App\Filament\Resources\Dashboard\ApplicationResource\Pages;
use App\Filament\Resources\Dashboard\ApplicationResource\RelationManagers;
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
use Filament\Forms\Components\Fieldset;

class ApplicationResource extends Resource
{

    use CommonTrait;
    protected static ?string $model = Application::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $slug = 'applications';



    public static function form(Form $form): Form
    {
        // Always refresh token before showing the form
        app(\App\Http\Controllers\GoogleDriveTokenController::class)->refreshAccessToken();
        return $form
            ->schema([
                Wizard::make([
                    Step::make('Parent Information')->schema([
                        Section::make('Father Information')->columns([
                            'sm' => 3,
                            'xl' => 4,
                        ])
                            ->schema([
                                TextInput::make('father_first_name')->label('First Name')->required(),
                                TextInput::make('father_last_name')->label('Last Name')->required(),
                                TextInput::make('father_phone')->label('Phone')->required()->tel(),
                                TextInput::make('father_email')->label('Email')->email()->required(),
                                Fieldset::make('Address')
                                    ->schema([
                                        TextInput::make('father_address')->label('Street Address')->required(),
                                        TextInput::make('father_city')->label('City')->required(),
                                        Select::make('father_state')->label('State')->options(self::states())->required(),
                                        TextInput::make('father_pincode')->label('Pincode')->required(),
                                    ])
                                    ->columns(4),

                            ]),
                        Section::make('Mother Information')->columns([
                            'sm' => 3,
                            'xl' => 4,
                        ])->schema([
                            TextInput::make('mother_first_name')->label('First Name')->required(),
                            TextInput::make('mother_last_name')->label('Last Name')->required(),
                            TextInput::make('mother_phone')->label('Phone')->required()->tel(),
                            TextInput::make('mother_email')->label('Email')->email()->required(),
                            Checkbox::make('mother_has_different_address')->label('Mother has a different address?')->reactive(),
                            Fieldset::make('Address')
                                ->schema([
                                    TextInput::make('mother_address')->label('Street Address')->required(),
                                    TextInput::make('mother_city')->label('City')->required(),
                                    Select::make('mother_state')->label('State')->options(self::states())->required(),
                                    TextInput::make('mother_pincode')->label('Pincode')->required(),
                                ])
                                ->columns(4)->hidden(fn(callable $get) => !$get('mother_has_different_address')),
                        ]),
                        Section::make('Family Information')->columns([
                            'sm' => 3,
                            'xl' => 4,
                        ])->schema([
                            Select::make('family_status')
                                ->options(self::familyStatuses())
                                ->required(),
                            Select::make('no_of_children_in_household')->label('Number of children in household')->options(self::householdChildren())->required(),
                            TextInput::make('synagogue_affiliation')
                                ->placeholder(' Please enter name and address.')
                                ->label('Affiliated with any synagogues?')
                                ->columnSpan(2)
                                ->required(),
                        ]),
                    ]),
                    Step::make('Children Information')->schema([
                        Repeater::make('children')
                            ->label('Children')
                            ->maxItems(fn(callable $get) => (int) $get('no_of_children_in_household') ?: null)
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
                                    Select::make('school_wish_to_apply_in')->label('Schools Wish to Apply In')->multiple()->maxItems(3)->options(self::applyingSchools())->required(),
                                    Select::make('attended_school_past_year')
                                        ->label('Is applicant has attended school in the past year.')
                                        ->options(['yes' => 'Yes', 'no' => 'No'])
                                        ->required(),
                                    FileUpload::make('recent_report_card')
                                        ->label('2 Years School Report Card')
                                        ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png'])
                                        ->maxSize(10240)
                                        ->multiple()
                                        ->maxFiles(2)
                                        ->previewable(false)
                                        ->directory('imgs/docs')
                                        ->visibility('private')
                                        ->required()
                                        ->disk('google'),

                                ])->columns(3)->visible(fn(callable $get) => $get('is_applying_for_grant'))->columnSpanFull(),
                            ])->defaultItems(fn(callable $get) => (int) $get('no_of_children_in_household') ?: 1)
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
                                        ->directory('imgs/docs')
                                        ->visibility('private')
                                        ->required()
                                        ->enableDownload()
                                        ->enableOpen()
                                        ->disk('google')
                                        ->saveUploadedFileUsing(function ($file, $record) {
                                            $path = 'imgs/docs/' . $file->getClientOriginalName();
                                            $stream = fopen($file->getRealPath(), 'r');
                                            Log::info('Uploading to Google Drive', ['path' => $path]);
                                            try {
                                                app(GoogleDriveService::class)->writeStreamWithRetry($path, $stream);
                                                Log::info('Upload success', ['path' => $path]);
                                            } catch (\Exception $e) {
                                                Log::error('Upload failed', ['error' => $e->getMessage()]);
                                            }
                                            if (is_resource($stream)) {
                                                fclose($stream);
                                            }
                                            return $path;
                                        }),
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
                                            Log::info('Uploading to Google Drive', ['path' => $path]);
                                            try {
                                                app(GoogleDriveService::class)->writeStreamWithRetry($path, $stream);
                                                Log::info('Upload success', ['path' => $path]);
                                            } catch (\Exception $e) {
                                                Log::error('Upload failed', ['error' => $e->getMessage()]);
                                            }
                                            if (is_resource($stream)) {
                                                fclose($stream);
                                            }
                                            return $path;
                                        }),
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
                                            Log::info('Uploading to Google Drive', ['path' => $path]);
                                            try {
                                                app(GoogleDriveService::class)->writeStreamWithRetry($path, $stream);
                                                Log::info('Upload success', ['path' => $path]);
                                            } catch (\Exception $e) {
                                                Log::error('Upload failed', ['error' => $e->getMessage()]);
                                            }
                                            if (is_resource($stream)) {
                                                fclose($stream);
                                            }
                                            return $path;
                                        }),
                                ]),
                            ]
                            : [
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
                                        }),
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
                            ->required(),
                        Checkbox::make('applicants_are_jewish')
                            ->label('I declare that the applicants are Jewish.')
                            ->required(),
                        Checkbox::make('parent_is_of_bukharian_descent')
                            ->label('I declare that at least one parent is of Bukharian descent.')
                            ->required(),
                        Group::make([
                            Textarea::make('additional_notes')
                                ->label('Additional Notes')
                                ->nullable()
                                ->rows(2)
                                ->maxLength(500),
                            DatePicker::make('declearation_date')
                                ->label('Declaration Date')
                                //Auto fill with current date
                                ->default(now())
                                ->columns(2)
                                ->required(),
                            SignaturePad::make('declearation_signature')
                                ->label('Declaration Signature')
                                ->extraAttributes(['style' => 'width:220px; height:80px;'])
                                ->backgroundColor('rgba(255,255,255,1)')
                                ->penColor('#222')
                                ->dotSize(0.7)
                                ->lineMinWidth(0.3)
                                ->lineMaxWidth(1.2)
                                ->throttle(8)
                                ->minDistance(0.5)
                                ->velocityFilterWeight(0.7)
                                ->required(),
                        ])->columns(3),


                    ]),
                ])->skippable()
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordUrl(null)
            ->columns([
                Tables\Columns\TextColumn::make('id')->label('ID')->sortable(),
                TextColumn::make('first_name')->searchable()->sortable(),
                TextColumn::make('date_of_birth')->searchable()->sortable(),
                TextColumn::make('gender')->searchable()->sortable(),
                TextColumn::make('current_school_name')->searchable()->sortable(),
                TextColumn::make('current_school_location')->searchable()->sortable(),
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

            ])->defaultSort('created_at', 'desc')
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->visible(
                        fn($record) =>
                        $record->user_id === auth()->id() &&
                            $record->status === 'fix_needed'
                    ),
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
