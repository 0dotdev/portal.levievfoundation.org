<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChildInfo extends Model
{
    protected $fillable = [
        'application_id',
        'first_name',
        'last_name',
        'date_of_birth',
        'gender',
        'current_school_name',
        'current_school_location',
        'is_child_applying_for_grant',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'is_child_applying_for_grant' => 'boolean',
    ];

    public function application()
    {
        return $this->belongsTo(Application::class);
    }
}
