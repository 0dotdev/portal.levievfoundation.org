<?php

namespace App\Services;

use App\Models\User;
use App\Models\ParentInfo;
use App\Models\NewApplication;
use App\Models\Document;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\UploadedFile;

class ApplicationService
{
    /**
     * Create parent info and multiple applications for children
     */
    public function createApplicationsForFamily(User $user, array $parentData, array $childrenData): array
    {
        return DB::transaction(function () use ($user, $parentData, $childrenData) {
            // Create parent record
            $parent = $this->createParentInfo($user, $parentData);

            // Create applications for each child
            $applications = [];
            foreach ($childrenData as $childData) {
                $applications[] = $this->createChildApplication($parent, $childData);
            }

            return [
                'parent' => $parent,
                'applications' => $applications
            ];
        });
    }

    /**
     * Create parent information record
     */
    protected function createParentInfo(User $user, array $data): ParentInfo
    {
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
            'father_address_line_1' => $data['father_address_line_1'],
            'father_address_line_2' => $data['father_address_line_2'] ?? null,
            'father_city' => $data['father_city'],
            'father_state' => $data['father_state'],
            'father_pincode' => $data['father_pincode'],
            'father_country' => $data['father_country'] ?? 'USA',
            'mother_has_different_address' => $data['mother_has_different_address'] ?? false,
            'mother_address_line_1' => $data['mother_address_line_1'] ?? null,
            'mother_address_line_2' => $data['mother_address_line_2'] ?? null,
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
    protected function createChildApplication(ParentInfo $parent, array $childData): NewApplication
    {
        return NewApplication::create([
            'parent_id' => $parent->id,
            'child_first_name' => $childData['first_name'],
            'child_last_name' => $childData['last_name'],
            'child_date_of_birth' => $childData['date_of_birth'],
            'child_gender' => $childData['gender'],
            'current_school_name' => $childData['current_school_name'],
            'current_school_location' => $childData['current_school_location'],
            'current_grade' => $childData['current_grade'],
            'school_year_applying_for' => $childData['school_year_applying_for'],
            'school_wish_to_apply_in' => $childData['school_wish_to_apply_in'],
            'is_applying_for_grant' => $childData['is_applying_for_grant'] ?? true,
            'applicant_has_attended_school_in_past_year' => $childData['applicant_has_attended_school_in_past_year'] ?? false,
            'additional_notes' => $childData['additional_notes'] ?? null,
            'submitted_at' => now(),
        ]);
    }

    /**
     * Upload and create parent document
     */
    public function uploadParentDocument(ParentInfo $parent, string $documentType, UploadedFile $file, string $uploadPath = null): Document
    {
        // Handle file upload (you'll need to implement your Google Drive upload logic)
        $filePath = $uploadPath ?? $this->uploadFileToGoogleDrive($file);

        return Document::create([
            'parent_id' => $parent->id,
            'document_type' => $documentType,
            'document_name' => $file->getClientOriginalName(),
            'file_path' => $filePath,
            'mime_type' => $file->getMimeType(),
            'file_size' => $file->getSize(),
            'status' => 'pending',
        ]);
    }

    /**
     * Upload and create child document
     */
    public function uploadChildDocument(NewApplication $application, string $documentType, UploadedFile $file, string $uploadPath = null): Document
    {
        // Handle file upload
        $filePath = $uploadPath ?? $this->uploadFileToGoogleDrive($file);

        return Document::create([
            'application_id' => $application->id,
            'document_type' => $documentType,
            'document_name' => $file->getClientOriginalName(),
            'file_path' => $filePath,
            'mime_type' => $file->getMimeType(),
            'file_size' => $file->getSize(),
            'status' => 'pending',
        ]);
    }

    /**
     * Update parent information (syncs across all children)
     */
    public function updateParentInfo(ParentInfo $parent, array $data): ParentInfo
    {
        $parent->update($data);
        return $parent->fresh();
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
                'missing_documents' => $this->getMissingDocuments($application),
                'pending_documents' => $application->documents()->pending()->count(),
            ];
        })->toArray();
    }

    /**
     * Get missing documents for an application
     */
    protected function getMissingDocuments(NewApplication $application): array
    {
        $requiredChildDocuments = ['school_report_card_2_years'];
        $existingDocuments = $application->documents()->pluck('document_type')->toArray();

        return array_diff($requiredChildDocuments, $existingDocuments);
    }

    /**
     * Placeholder for Google Drive upload - implement your existing logic
     */
    protected function uploadFileToGoogleDrive(UploadedFile $file): string
    {
        // Implement your Google Drive upload logic here
        // Return the file ID or path
        return 'placeholder-file-id';
    }
}
