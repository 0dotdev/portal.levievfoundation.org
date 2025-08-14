<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Application extends Model
{
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
        'child_attended_school_past_year',
        'status',
        'additional_notes',
        'admin_comments',
        'submitted_at',
        'reviewed_at',
        'reviewed_by',
    ];

    protected $casts = [
        'school_wish_to_apply_in' => 'array',
        'is_applying_for_grant' => 'boolean',
        'child_attended_school_past_year' => 'boolean',
        'child_date_of_birth' => 'date',
        'submitted_at' => 'datetime',
        'reviewed_at' => 'datetime',
    ];

    /**
     * Get the parent that owns this application
     */
    public function parent()
    {
        return $this->belongsTo(ParentInfo::class, 'parent_id');
    }

    /**
     * Get the user through parent relationship
     */
    public function user()
    {
        return $this->hasOneThrough(User::class, ParentInfo::class, 'id', 'id', 'parent_id', 'user_id');
    }


    /**
     * Get documents specific to this application (child documents)
     */
    public function documents()
    {
        return $this->hasMany(Document::class, 'application_id');
    }

    /**
     * Get parent documents through user relationship
     */
    public function parentDocuments()
    {
        return $this->user->parentDocuments();
    }

    /**
     * Get ALL documents for this application (both parent and child documents)
     */
    public function allDocuments()
    {
        $childDocuments = $this->documents;
        $parentDocuments = $this->user->documents()->whereNull('application_id')->get();

        return $childDocuments->concat($parentDocuments);
    }

    public function getSchoolWishToApplyInAttribute($value)
    {
        if (is_array($value)) {
            return $value;
        }
        if (empty($value)) {
            return [];
        }
        return json_decode($value, true);
    }

    public function getGovernmentIdUrlAttribute()
    {
        return $this->government_id ? url('/google-drive-preview/' . $this->government_id) : null;
    }
    public function getRecentReportCardUrlAttribute()
    {
        return $this->recent_report_card ? url('/google-drive-preview/' . $this->recent_report_card) : null;
    }
    public function getMarriageCertificateUrlAttribute()
    {
        return $this->marriage_certificate ? url('/google-drive-preview/' . $this->marriage_certificate) : null;
    }
    public function getRecentUtilityBillUrlAttribute()
    {
        return $this->recent_utility_bill ? url('/google-drive-preview/' . $this->recent_utility_bill) : null;
    }
}
