<?php

namespace App\Services;

use App\Models\User;
use App\Models\Application;
use App\Models\Document;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;

class ApplicationService
{
    /**
     * Create applications for family
     */
    public function createApplicationsForFamily(User $user, array $formData): array
    {
        return DB::transaction(function () use ($user, $formData) {
            // Filter children who are applying for grants
            $childrenApplyingForGrants = collect($formData['children'])
                ->filter(function ($childData) {
                    return isset($childData['is_applying_for_grant']) && $childData['is_applying_for_grant'] === true;
                });

            // Create applications only for children applying for grants
            $applications = [];
            foreach ($childrenApplyingForGrants as $childData) {
                $application = $this->createApplication($user, $formData, $childData);
                $applications[] = $application;

                // Handle child documents (report card) - only for children applying for grants
                if (!empty($childData['recent_report_card'])) {
                    $this->createDocument($application->id, 'school_report_card_2_years', $childData['recent_report_card']);
                }
            }

            // Handle parent documents for all applications (only if there are applications)
            if (!empty($applications)) {
                foreach ($applications as $application) {
                    if (!empty($formData['government_id'])) {
                        $this->createDocument($application->id, 'government_id', $formData['government_id']);
                    }
                    if (!empty($formData['marriage_certificate'])) {
                        $this->createDocument($application->id, 'marriage_certificate', $formData['marriage_certificate']);
                    }
                    if (!empty($formData['recent_utility_bill'])) {
                        $this->createDocument($application->id, 'recent_utility_bill', $formData['recent_utility_bill']);
                    }
                }
            }

            return [
                'applications' => $applications,
                'total_children' => count($formData['children']),
                'children_applying_for_grants' => $childrenApplyingForGrants->count(),
                'children_not_applying' => count($formData['children']) - $childrenApplyingForGrants->count()
            ];
        });
    }

    /**
     * Create application for a child with parent information included
     */
    protected function createApplication(User $user, array $parentData, array $childData)
    {
        return Application::create([
            'user_id' => $user->id,
            // Child Information
            'first_name' => $childData['first_name'],
            'last_name' => $childData['last_name'],
            'date_of_birth' => $childData['date_of_birth'],
            'gender' => $childData['gender'],
            'current_school_name' => $childData['current_school_name'],
            'current_school_location' => $childData['current_school_location'],
            'current_grade' => $childData['current_grade'],
            'school_year_applying_for' => $childData['school_year_applying_for'] ?? null,
            'school_wish_to_apply_in' => $childData['school_wish_to_apply_in'] ?? [],
            'is_applying_for_grant' => $childData['is_applying_for_grant'] ?? true,
            'attended_school_past_year' => $childData['attended_school_past_year'] ?? false,
            // Parent Information
            'father_first_name' => $parentData['father_first_name'],
            'father_last_name' => $parentData['father_last_name'],
            'father_phone' => $parentData['father_phone'],
            'father_email' => $parentData['father_email'],
            'father_address' => $parentData['father_address'],
            'father_city' => $parentData['father_city'],
            'father_state' => $parentData['father_state'],
            'father_pincode' => $parentData['father_pincode'],
            'father_country' => $parentData['father_country'] ?? 'USA',
            'mother_first_name' => $parentData['mother_first_name'],
            'mother_last_name' => $parentData['mother_last_name'],
            'mother_phone' => $parentData['mother_phone'],
            'mother_email' => $parentData['mother_email'],
            'mother_has_different_address' => $parentData['mother_has_different_address'] ?? false,
            'mother_address' => $parentData['mother_address'] ?? null,
            'mother_city' => $parentData['mother_city'] ?? null,
            'mother_state' => $parentData['mother_state'] ?? null,
            'mother_pincode' => $parentData['mother_pincode'] ?? null,
            'mother_country' => $parentData['mother_country'] ?? null,
            'family_status' => $parentData['family_status'],
            'no_of_children_in_household' => $parentData['no_of_children_in_household'],
            'synagogue_affiliation' => $parentData['synagogue_affiliation'],
            'declaration_signature' => $parentData['declaration_signature'],
            'declaration_date' => $parentData['declaration_date'],
            'info_is_true' => $parentData['info_is_true'] ?? false,
            'applicants_are_jewish' => $parentData['applicants_are_jewish'] ?? false,
            'parent_is_of_bukharian_descent' => $parentData['parent_is_of_bukharian_descent'] ?? false,
            // Application Status
            'additional_notes' => $parentData['additional_notes'] ?? null,
            'status' => 'submitted',
            'submitted_at' => now(),
        ]);
    }

    /**
     * Create document records and store the files
     * @return Document|array<Document>
     */
    protected function createDocument(int $applicationId, string $documentType, $files)
    {
        // If no files provided, return null
        if (empty($files)) {
            return null;
        }

        // Convert single file to array for consistent handling
        $filesArray = is_array($files) ? $files : [$files];
        $documents = [];

        foreach ($filesArray as $file) {
            if ($file instanceof UploadedFile) {
                // For direct file uploads
                $originalName = $file->getClientOriginalName();
                $extension = $file->getClientOriginalExtension();
                $nameWithoutExt = pathinfo($originalName, PATHINFO_FILENAME);

                // Store with original name
                $filePath = $file->storeAs(
                    'documents/' . $documentType,
                    $originalName,
                    'local'
                );

                $documents[] = Document::create([
                    'application_id' => $applicationId,
                    'document_type' => $documentType,
                    'document_name' => $originalName, // Keep original name for display
                    'file_path' => $filePath,
                    'mime_type' => $file->getMimeType(),
                    'file_size' => $file->getSize(),
                    'status' => 'pending'
                ]);
            } else {
                // For files already uploaded by Filament
                $originalPath = (string)$file;
                $originalName = basename($originalPath);
                $extension = pathinfo($originalName, PATHINFO_EXTENSION);
                $nameWithoutExt = pathinfo($originalName, PATHINFO_FILENAME);

                // Keep original path
                $documents[] = Document::create([
                    'application_id' => $applicationId,
                    'document_type' => $documentType,
                    'document_name' => $originalName, // Keep original name for display
                    'file_path' => $originalPath,
                    'mime_type' => null,
                    'file_size' => null,
                    'status' => 'pending'
                ]);
            }
        }

        // Return single document if only one was created, otherwise return array
        return count($documents) === 1 ? $documents[0] : $documents;
    }

    /**
     * Get all applications with their status for a user
     */
    public function getApplicationsStatus(User $user): array
    {
        $applications = $user->applications()->with('documents')->get();

        return $applications->map(function ($application) {
            return [
                'id' => $application->id,
                'child_name' => $application->first_name . ' ' . $application->last_name,
                'status' => $application->status,
                'missing_documents' => $this->getMissingDocuments($application),
                'pending_documents' => $application->documents()->pending()->count(),
            ];
        })->toArray();
    }

    /**
     * Get missing documents for an application
     */
    protected function getMissingDocuments(Application $application): array
    {
        $requiredDocuments = ['government_id', 'marriage_certificate', 'recent_utility_bill', 'school_report_card_2_years'];
        $existingDocuments = $application->documents()->pluck('document_type')->toArray();

        return array_diff($requiredDocuments, $existingDocuments);
    }
}
