<?php

namespace App\Filament\Exports;

use App\Models\Application;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;

class ApplicationExporter extends Exporter
{
    protected static ?string $model = Application::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('id'),
            ExportColumn::make('user_id'),
            ExportColumn::make('first_name')->label('Child First Name'),
            ExportColumn::make('last_name')->label('Child Last Name'),
            ExportColumn::make('date_of_birth')->label('Child Date of Birth'),
            ExportColumn::make('gender')->label('Child Gender'),
            ExportColumn::make('current_school_name')->label('Child Current School Name'),
            ExportColumn::make('current_school_location')->label('Child Current School Location'),
            ExportColumn::make('current_grade')->label('Child Current Grade'),
            ExportColumn::make('school_year_applying_for')->label('Child School Year Applying For'),
            ExportColumn::make('school_wish_to_apply_in')->label('Child School Wish to Apply In'),
            ExportColumn::make('father_first_name')->label('Father Name')->formatStateUsing(fn($record) => $record->parent->father_first_name  . ' ' . $record->parent->father_last_name),
            ExportColumn::make('father_phone')->label('Father Phone'),
            ExportColumn::make('father_email')->label('Father Email'),
            ExportColumn::make('mother_first_name')->label('Mother Name')->formatStateUsing(fn($record) => $record->parent->mother_first_name  . ' ' . $record->parent->mother_last_name),
            ExportColumn::make('mother_phone')->label('Mother Phone'),
            ExportColumn::make('mother_email')->label('Mother Email'),
            ExportColumn::make('father_address')->label('Home Address')->formatStateUsing(fn($record) => $record->parent->father_address . ', ' . $record->parent->father_city . ', ' . $record->parent->father_state . ' ' . $record->parent->father_zip),
            ExportColumn::make('mother_address')->label('Mother Address')->formatStateUsing(fn($record) => $record->parent->mother_address . ', ' . $record->parent->mother_city . ', ' . $record->parent->mother_state . ' ' . $record->parent->mother_zip),
            ExportColumn::make('family_status')->label('Family Status'),
            ExportColumn::make('no_of_children_in_household')->label('No. of Children in Household'),
            ExportColumn::make('synagogue_affiliation')->label('Synagogue Affiliation'),
            // ExportColumn::make('government_id')->label('Government ID')
            //     ->formatStateUsing(fn($state) => $state ? static::getGoogleDriveUrl($state) : null),
            // ExportColumn::make('marriage_certificate')
            //     ->formatStateUsing(fn($state) => $state ? static::getGoogleDriveUrl($state) : null),
            // ExportColumn::make('recent_report_card')
            //     ->formatStateUsing(fn($state) => $state ? static::getGoogleDriveUrl($state) : null),
            // ExportColumn::make('recent_utility_bill')
            //     ->formatStateUsing(fn($state) => $state ? static::getGoogleDriveUrl($state) : null),
            // ExportColumn::make('status_government_id'),
            // ExportColumn::make('status_marriage_certificate'),
            // ExportColumn::make('status_recent_report_card'),
            // ExportColumn::make('status_recent_utility_bill'),
            // ExportColumn::make('comments_government_id'),
            // ExportColumn::make('comments_marriage_certificate'),
            // ExportColumn::make('comments_recent_report_card'),
            // ExportColumn::make('comments_recent_utility_bill'),
            ExportColumn::make('info_is_true')->label('Parent Info is True'),
            ExportColumn::make('applicants_are_jewish')->label('Applicants are Jewish'),
            ExportColumn::make('parent_is_of_bukharian_descent')->label('Parent is of Bukharian Descent'),
            ExportColumn::make('applicants_are_jewish')->label('Applicants are Jewish'),
            ExportColumn::make('parent_is_of_bukharian_descent')->label('Parent is of Bukharian Descent'),
            ExportColumn::make('declaration_date')->label('Declaration Date'),
            ExportColumn::make('additional_notes')->label('Additional Notes'),
            ExportColumn::make('status')->label('Status'),
            ExportColumn::make('created_at'),
            // ...removed childInfos export columns...
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Your application export has completed and ' . number_format($export->successful_rows) . ' ' . str('row')->plural($export->successful_rows) . ' exported.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' ' . number_format($failedRowsCount) . ' ' . str('row')->plural($failedRowsCount) . ' failed to export.';
        }

        return $body;
    }

    protected static function getGoogleDriveUrl($path)
    {
        // If you have a custom logic to generate a public Google Drive URL, implement it here.
        // For now, just return the path as a placeholder, or update as needed.
        // Example: return 'https://drive.google.com/uc?id=' . $path;
        return $path;
    }
}
