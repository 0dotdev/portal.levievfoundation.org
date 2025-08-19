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
        // Parent Information
        'father_first_name',
        'father_last_name',
        'father_phone',
        'father_email',
        'father_address',
        'father_city',
        'father_state',
        'father_pincode',
        'father_country',
        'mother_first_name',
        'mother_last_name',
        'mother_phone',
        'mother_email',
        'mother_has_different_address',
        'mother_address',
        'mother_city',
        'mother_state',
        'mother_pincode',
        'mother_country',
        'family_status',
        'no_of_children_in_household',
        'synagogue_affiliation',
        'declaration_signature',
        'declaration_date',
        'info_is_true',
        'applicants_are_jewish',
        'parent_is_of_bukharian_descent',
        // Application Status
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
        'mother_has_different_address' => 'boolean',
        'info_is_true' => 'boolean',
        'applicants_are_jewish' => 'boolean',
        'parent_is_of_bukharian_descent' => 'boolean',
        'date_of_birth' => 'date',
        'declaration_date' => 'date',
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
     * Get all documents for this application
     */
    public function documents()
    {
        return $this->hasMany(Document::class, 'application_id');
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
