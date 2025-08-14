<?php

namespace App\Services;

use App\Models\User;
use App\Models\ParentInfo;
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
            // Create parent record
            $parent = $this->createParentInfo($user, $formData);

            // Create applications for each child
            $applications = [];
            foreach ($formData['children'] as $childData) {
                $application = $this->createApplication($user, $childData);
                $applications[] = $application;


                // Handle child documents
                if (!empty($childData['recent_report_card'])) {
                    $this->createDocument($application->id, 'child', 'school_report_card_2_years', $childData['recent_report_card']);
                }
            }

            // Handle parent documents
            if (!empty($formData['government_id'])) {
                $this->createDocument($user->id, 'parent', 'government_id', $formData['government_id']);
            }
            if (!empty($formData['marriage_certificate'])) {
                $this->createDocument($user->id, 'parent', 'marriage_certificate', $formData['marriage_certificate']);
            }
            if (!empty($formData['recent_utility_bill'])) {
                $this->createDocument($user->id, 'parent', 'recent_utility_bill', $formData['recent_utility_bill']);
            }

            return [
                'parent' => $parent,
                'applications' => $applications
            ];
        });
    }

    /**
     * Create parent information record if it doesn't exist
     */
    protected function createParentInfo(User $user, array $data): ParentInfo
    {
        // Check if parent info already exists for this user
        $existingParentInfo = ParentInfo::where('user_id', $user->id)->first();
        if ($existingParentInfo) {
            return $existingParentInfo;
        }

        return ParentInfo::create([
            'user_id' => $user->id,
            'father_first_name' => $data['father_first_name'],
            'father_last_name' => $data['father_last_name'],
            'father_phone' => $data['father_phone'],
            'father_email' => $data['father_email'],
            'mother_first_name' => $data['mother_first_name'],
            'mother_last_name' => $data['mother_last_name'],
            'mother_phone' => $data['mother_phone'],
            'mother_email' => $data['mother_email'],
            'father_address' => $data['father_address'],
            'father_city' => $data['father_city'],
            'father_state' => $data['father_state'],
            'father_pincode' => $data['father_pincode'],
            'father_country' => $data['father_country'] ?? 'USA',
            'mother_has_different_address' => $data['mother_has_different_address'] ?? false,
            'mother_address' => $data['mother_address'] ?? null,
            'mother_city' => $data['mother_city'] ?? null,
            'mother_state' => $data['mother_state'] ?? null,
            'mother_pincode' => $data['mother_pincode'] ?? null,
            'mother_country' => $data['mother_country'] ?? null,
            'family_status' => $data['family_status'],
            'no_of_children_in_household' => $data['no_of_children_in_household'],
            'synagogue_affiliation' => $data['synagogue_affiliation'],
            'declaration_signature' => $data['declaration_signature'],
            'declaration_date' => $data['declaration_date'],
            'info_is_true' => $data['info_is_true'] ?? false,
            'applicants_are_jewish' => $data['applicants_are_jewish'] ?? false,
            'parent_is_of_bukharian_descent' => $data['parent_is_of_bukharian_descent'] ?? false,
        ]);
    }

    /**
     * Create application for a child
     */
    protected function createApplication(User $user, array $childData)
    {
        return Application::create([
            'user_id' => $user->id,
            'first_name' => $childData['first_name'],
            'last_name' => $childData['last_name'],
            'date_of_birth' => $childData['date_of_birth'],
            'gender' => $childData['gender'],
            'current_school_name' => $childData['current_school_name'],
            'current_school_location' => $childData['current_school_location'],
            'current_grade' => $childData['current_grade'],
            'school_year_applying_for' => $childData['school_year_applying_for'],
            'school_wish_to_apply_in' => json_encode($childData['school_wish_to_apply_in'] ?? []),
            'is_applying_for_grant' => $childData['is_applying_for_grant'] ?? true,
            'attended_school_past_year' => $childData['attended_school_past_year'] ?? false,
            'additional_notes' => $childData['additional_notes'] ?? null,
            'status' => 'submitted',
            'submitted_at' => now(),
        ]);
    }

    /**
     * Create document records and store the files
     * @return Document|array<Document>
     */
    protected function createDocument(int $referenceId, string $referenceType, string $documentType, $files)
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
                    'reference_id' => $referenceId,
                    'reference_type' => $referenceType,
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
                    'reference_id' => $referenceId,
                    'reference_type' => $referenceType,
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
     * Get all applications with their status for a parent
     */
    public function getApplicationsStatus(ParentInfo $parent): array
    {
        $applications = $parent->applications()->with('documents')->get();

        return $applications->map(function ($application) {
            return [
                'id' => $application->id,
                'child_name' => $application->child_full_name,
                'status' => $application->status,
                'missing_documents' => [],
                'pending_documents' => $application->documents()->pending()->count(),
            ];
        })->toArray();
    }

    /**
     * Get missing documents for an application
     */
    protected function getMissingDocuments(Application $application): array
    {
        $requiredChildDocuments = ['school_report_card_2_years'];
        $existingDocuments = $application->documents()->pluck('document_type')->toArray();

        return array_diff($requiredChildDocuments, $existingDocuments);
    }

    // No longer needed as we're using local storage
}
