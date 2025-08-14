<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ParentInfo extends Model
{

    /**
     * The table associated with the model.
     *
     * @var string
     */
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'parents';

    protected $fillable = [
        'user_id',
        'father_first_name',
        'father_last_name',
        'father_phone',
        'father_email',
        'mother_first_name',
        'mother_last_name',
        'mother_phone',
        'mother_email',
        'father_address_line_1',
        'father_address_line_2',
        'father_city',
        'father_state',
        'father_pincode',
        'father_country',
        'mother_has_different_address',
        'mother_address_line_1',
        'mother_address_line_2',
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
    ];

    protected $casts = [
        'mother_has_different_address' => 'boolean',
        'info_is_true' => 'boolean',
        'applicants_are_jewish' => 'boolean',
        'parent_is_of_bukharian_descent' => 'boolean',
        'declaration_date' => 'date',
    ];

    /**
     * Get the user that owns this parent record
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get all applications (children) for this parent
     */
    public function applications(): HasMany
    {
        return $this->hasMany(Application::class, 'parent_id');
    }

    /**
     * Get all documents belonging to this parent
     */
    public function documents(): HasMany
    {
        return $this->hasMany(Document::class, 'parent_id');
    }
}
