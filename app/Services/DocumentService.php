<?php

namespace App\Services;

use App\Models\User;
use App\Models\ParentInfo;
use App\Models\Application;
use App\Models\Document;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\UploadedFile;

class DocumentService
{
    /**
     * Upload a parent document (shared across all children)
     */
    public function uploadParentDocument(ParentInfo $parent, string $documentType, UploadedFile $file, string $uploadPath = null): Document
    {
        // Handle file upload (you'll implement your Google Drive upload logic)
        $filePath = $uploadPath ?? $this->uploadFileToGoogleDrive($file);

        return Document::create([
            'parent_id' => $parent->id,
            'application_id' => null,  // null for parent documents
            'document_type' => $documentType,
            'document_name' => $file->getClientOriginalName(),
            'file_path' => $filePath,
            'mime_type' => $file->getMimeType(),
            'file_size' => $file->getSize(),
            'status' => 'pending',
        ]);
    }

    /**
     * Upload a child document (specific to one application)
     */
    public function uploadChildDocument(Application $application, string $documentType, UploadedFile $file, string $uploadPath = null): Document
    {
        $filePath = $uploadPath ?? $this->uploadFileToGoogleDrive($file);

        return Document::create([
            'parent_id' => null, // null for child documents
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
     * Get all documents for an application (parent + child documents)
     */
    public function getApplicationDocuments(Application $application): array
    {
        return [
            'parent_documents' => $application->parent->documents()->get(),
            'child_documents' => $application->documents()->get(),
        ];
    }

    /**
     * Get document summary for admin review
     */
    public function getDocumentSummaryForApplication(Application $application): array
    {
        $parentDocs = $application->parent->documents()->get();
        $childDocs = $application->documents()->get();
        return [
            'parent_documents' => [
                'government_id' => $parentDocs->where('document_type', 'government_id')->first(),
                'marriage_certificate' => $parentDocs->where('document_type', 'marriage_certificate')->first(),
                'recent_utility_bill' => $parentDocs->where('document_type', 'recent_utility_bill')->first(),
            ],
            'child_documents' => [
                'school_report_card_2_years' => $childDocs->where('document_type', 'school_report_card_2_years')->first(),
            ],
            'missing_documents' => $this->getMissingDocuments($application),
            'pending_review_count' => $parentDocs->concat($childDocs)->where('status', 'pending')->count(),
        ];
    }

    /**
     * Check what documents are missing for an application
     */
    protected function getMissingDocuments(Application $application): array
    {
        $required = [
            'parent' => ['government_id', 'marriage_certificate', 'recent_utility_bill'],
            'child' => ['school_report_card_2_years'],
        ];

        $parentDocs = $application->user->parentDocuments()->pluck('document_type')->toArray();
        $childDocs = $application->documents()->pluck('document_type')->toArray();

        return [
            'parent_missing' => array_diff($required['parent'], $parentDocs),
            'child_missing' => array_diff($required['child'], $childDocs),
        ];
    }

    /**
     * Placeholder for Google Drive upload
     */
    protected function uploadFileToGoogleDrive(UploadedFile $file): string
    {
        // Your existing Google Drive upload logic
        return 'placeholder-file-id';
    }
}

// Usage Examples:

/*
// Upload parent document (shared across all children)
$documentService = new DocumentService();
$parentDoc = $documentService->uploadParentDocument($user, 'government_id', $file);

// Upload child document (specific to one application)  
$childDoc = $documentService->uploadChildDocument($application, 'school_report_card_2_years', $file);

// Get all documents for an application
$docs = $documentService->getApplicationDocuments($application);
// Returns: parent_documents, child_documents, all_documents

// For admin review - get complete document status
$summary = $documentService->getDocumentSummaryForApplication($application);
// Returns organized view of all required documents with status
*/
