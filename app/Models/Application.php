<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Application extends Model
{
    protected $fillable = [
        'user_id',
        'first_name',
        'last_name',
        'date_of_birth',
        'gender',
        'current_school_name',
        'current_school_location',
        'current_grade',
        'school_year_applying_for',
        'school_wish_to_apply_in',
        'is_applying_for_grant',
        'attended_school_past_year',
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
        'attended_school_past_year' => 'boolean',
        'child_date_of_birth' => 'date',
        'submitted_at' => 'datetime',
        'reviewed_at' => 'datetime',
    ];

    /**
     * Get the user that owns this application
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get the parent through user relationship
     */
    public function parent()
    {
        return $this->hasOneThrough(
            ParentInfo::class,
            User::class,
            'id', // Foreign key on users table
            'user_id', // Foreign key on parent_infos table
            'user_id', // Local key on applications table
            'id' // Local key on users table
        );
    }


    /**
     * Get documents specific to this application (child documents)
     */
    public function documents()
    {
        return $this->hasMany(Document::class, 'reference_id')
            ->where('reference_type', 'child');
    }

    /**
     * Get parent documents through user relationship
     */
    public function parentDocuments()
    {
        return $this->user->documents();
    }

    /**
     * Get ALL documents for this application (both parent and child documents)
     */
    public function allDocuments()
    {
        return Document::where(function ($query) {
            $query->where('reference_type', 'child')
                ->where('reference_id', $this->id);
        })->orWhere(function ($query) {
            $query->where('reference_type', 'parent')
                ->where('reference_id', $this->user_id);
        })->get();
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
