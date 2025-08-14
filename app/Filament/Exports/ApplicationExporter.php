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
            ExportColumn::make('first_name'),
            ExportColumn::make('last_name'),
            ExportColumn::make('date_of_birth'),
            ExportColumn::make('gender'),
            ExportColumn::make('current_school_name'),
            ExportColumn::make('current_school_location'),
            ExportColumn::make('current_grade'),
            ExportColumn::make('school_year_applying_for'),
            ExportColumn::make('school_wish_to_apply_in'),
            ExportColumn::make('father_first_name'),
            ExportColumn::make('father_last_name'),
            ExportColumn::make('father_phone'),
            ExportColumn::make('father_email'),
            ExportColumn::make('mother_first_name'),
            ExportColumn::make('mother_last_name'),
            ExportColumn::make('mother_phone'),
            ExportColumn::make('mother_email'),
            ExportColumn::make('home_address'),
            ExportColumn::make('is_additional_address'),
            ExportColumn::make('additional_address'),
            ExportColumn::make('family_status'),
            ExportColumn::make('no_of_children_in_household'),
            ExportColumn::make('synagogue_affiliation'),
            ExportColumn::make('government_id')
                ->formatStateUsing(fn($state) => $state ? static::getGoogleDriveUrl($state) : null),
            ExportColumn::make('marriage_certificate')
                ->formatStateUsing(fn($state) => $state ? static::getGoogleDriveUrl($state) : null),
            ExportColumn::make('recent_report_card')
                ->formatStateUsing(fn($state) => $state ? static::getGoogleDriveUrl($state) : null),
            ExportColumn::make('recent_utility_bill')
                ->formatStateUsing(fn($state) => $state ? static::getGoogleDriveUrl($state) : null),
            ExportColumn::make('status_government_id'),
            ExportColumn::make('status_marriage_certificate'),
            ExportColumn::make('status_recent_report_card'),
            ExportColumn::make('status_recent_utility_bill'),
            ExportColumn::make('comments_government_id'),
            ExportColumn::make('comments_marriage_certificate'),
            ExportColumn::make('comments_recent_report_card'),
            ExportColumn::make('comments_recent_utility_bill'),
            ExportColumn::make('info_is_true'),
            ExportColumn::make('applicants_are_jewish'),
            ExportColumn::make('parent_is_of_bukharian_descent'),
            ExportColumn::make('applicant_has_attended_school_in_the_past_year'),
            ExportColumn::make('declearation_signature'),
            ExportColumn::make('declearation_date'),
            ExportColumn::make('additional_notes'),
            ExportColumn::make('status'),
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
