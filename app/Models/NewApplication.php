<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class NewApplication extends Model
{
    protected $table = 'new_applications';

    protected $fillable = [
        'parent_id',
        'child_first_name',
        'child_last_name',
        'child_date_of_birth',
        'child_gender',
        'current_school_name',
        'current_school_location',
        'current_grade',
        'school_year_applying_for',
        'school_wish_to_apply_in',
        'is_applying_for_grant',
        'applicant_has_attended_school_in_past_year',
        'status',
        'additional_notes',
        'admin_comments',
        'submitted_at',
        'reviewed_at',
        'reviewed_by',
    ];

    protected $casts = [
        'child_date_of_birth' => 'date',
        'school_wish_to_apply_in' => 'array',
        'is_applying_for_grant' => 'boolean',
        'applicant_has_attended_school_in_past_year' => 'boolean',
        'submitted_at' => 'datetime',
        'reviewed_at' => 'datetime',
    ];

    /**
     * Get the parent that owns this application
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(ParentInfo::class, 'parent_id');
    }

    /**
     * Get the user who reviewed this application
     */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    /**
     * Get all documents for this application (child-specific documents)
     */
    public function documents(): HasMany
    {
        return $this->hasMany(Document::class, 'application_id');
    }

    /**
     * Get child-specific documents (school report cards)
     */
    public function childDocuments(): HasMany
    {
        return $this->documents()->where('document_type', 'school_report_card_2_years');
    }

    /**
     * Get child's full name
     */
    public function getChildFullNameAttribute(): string
    {
        return $this->child_first_name . ' ' . $this->child_last_name;
    }

    /**
     * Check if application is pending review
     */
    public function getIsPendingAttribute(): bool
    {
        return in_array($this->status, ['submitted', 'pending', 'resubmitted']);
    }

    /**
     * Check if application needs documents
     */
    public function getNeedsDocumentsAttribute(): bool
    {
        return $this->status === 'fix_needed';
    }

    /**
     * Get application age in days
     */
    public function getApplicationAgeAttribute(): int
    {
        return $this->submitted_at ? $this->submitted_at->diffInDays(now()) : 0;
    }

    /**
     * Scope for applications needing review
     */
    public function scopePendingReview($query)
    {
        return $query->whereIn('status', ['submitted', 'pending', 'resubmitted']);
    }

    /**
     * Scope for approved applications
     */
    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    /**
     * Scope for rejected applications
     */
    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }
}
