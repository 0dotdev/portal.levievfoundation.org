<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Document extends Model
{
    protected $fillable = [
        'parent_id',
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
     * Document types
     */
    const PARENT_DOCUMENT_TYPES = [
        'government_id' => 'Government ID',
        'marriage_certificate' => 'Marriage Certificate',
        'recent_utility_bill' => 'Recent Utility Bill',
    ];

    const CHILD_DOCUMENT_TYPES = [
        'school_report_card_2_years' => '2 Years School Report Card',
    ];

    const ALL_DOCUMENT_TYPES = [
        'government_id' => 'Government ID',
        'marriage_certificate' => 'Marriage Certificate',
        'recent_utility_bill' => 'Recent Utility Bill',
        'school_report_card_2_years' => '2 Years School Report Card',
    ];

    /**
     * Get the parent that owns this document (if parent-level)
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(ParentInfo::class, 'parent_id');
    }

    /**
     * Get the application that owns this document (if child-level)
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
     * Check if document is parent-level
     */
    public function getIsParentDocumentAttribute(): bool
    {
        return in_array($this->document_type, array_keys(self::PARENT_DOCUMENT_TYPES));
    }

    /**
     * Check if document is child-level
     */
    public function getIsChildDocumentAttribute(): bool
    {
        return in_array($this->document_type, array_keys(self::CHILD_DOCUMENT_TYPES));
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
     * Scope for parent documents
     */
    public function scopeParentDocuments($query)
    {
        return $query->whereIn('document_type', array_keys(self::PARENT_DOCUMENT_TYPES));
    }

    /**
     * Scope for child documents
     */
    public function scopeChildDocuments($query)
    {
        return $query->whereIn('document_type', array_keys(self::CHILD_DOCUMENT_TYPES));
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
