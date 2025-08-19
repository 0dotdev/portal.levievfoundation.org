<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Document extends Model
{
    public function getPreviewUrl(): ?string
    {
        // If using Google Drive (based on your GoogleDriveService)
        if (str_starts_with($this->file_path, 'drive:')) {
            $fileId = str_replace('drive:', '', $this->file_path);
            return "https://drive.google.com/file/d/{$fileId}/preview";
        }

        // If using local storage
        if ($this->file_path) {
            return url(Storage::url($this->file_path));
        }

        return null;
    }
    protected $fillable = [
        'application_id',
        'document_type',
        'document_name',
        'file_path',
        'mime_type',
        'file_size',
        'status',
        'comments',
        'reviewed_by',
        'reviewed_at',
    ];

    protected $casts = [
        'file_size' => 'integer',
        'reviewed_at' => 'datetime',
    ];

    /**
     * Document types - now all types can belong to any application
     */
    const ALL_DOCUMENT_TYPES = [
        'government_id' => 'Government ID',
        'marriage_certificate' => 'Marriage Certificate',
        'recent_utility_bill' => 'Recent Utility Bill',
        'school_report_card_2_years' => 'School Report Card for the Past 2 Years',
    ];

    /**
     * Get the application that owns this document
     */
    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class, 'application_id');
    }

    /**
     * Get the user who reviewed this document
     */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    /**
     * Get human readable document type
     */
    public function getDocumentTypeNameAttribute(): string
    {
        return self::ALL_DOCUMENT_TYPES[$this->document_type] ?? $this->document_type;
    }

    /**
     * Get document URL for preview
     */
    public function getDocumentUrlAttribute(): ?string
    {
        return $this->file_path ? url('/google-drive-preview/' . $this->file_path) : null;
    }

    /**
     * Check if document is approved
     */
    public function getIsApprovedAttribute(): bool
    {
        return $this->status === 'approved';
    }

    /**
     * Check if document is rejected
     */
    public function getIsRejectedAttribute(): bool
    {
        return $this->status === 'rejected';
    }

    /**
     * Check if document is pending
     */
    public function getIsPendingAttribute(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Scope for pending documents
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope for approved documents
     */
    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    /**
     * Scope for rejected documents
     */
    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }
}
