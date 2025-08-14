<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Filament\Models\Contracts\FilamentUser;
use Illuminate\Auth\MustVerifyEmail as MustVerifyEmailTrait;

class User extends Authenticatable implements FilamentUser, MustVerifyEmail
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, MustVerifyEmailTrait;

    /**
     * Determine if the user can access the Filament panel.
     *
     * @param \Filament\Panel $panel
     * @return bool
     */
    public function canAccessPanel(\Filament\Panel $panel): bool
    {
        if ($panel->getId() === 'dashboard') {
            return true; // Allow all authenticated users to access the panel
        }
        if ($panel->getId() === 'admin') {
            return $this->roles === 'admin'; // Only allow admin users to access the admin panel
        }
        return false;
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'roles',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function applications()
    {
        return $this->hasManyThrough(Application::class, ParentInfo::class, 'user_id', 'parent_id');
    }

    /**
     * Get parent info for this user
     */
    public function parentInfo()
    {
        return $this->hasOne(ParentInfo::class);
    }

    /**
     * Get parent-level documents for this user
     */
    public function documents()
    {
        return $this->hasMany(Document::class, 'reference_id')
            ->where('reference_type', 'parent');
    }

    /**
     * Alias for documents() for clearer semantics
     */
    public function parentDocuments()
    {
        return $this->documents();
    }
}
