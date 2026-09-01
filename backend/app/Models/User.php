<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    // Roles, from most to least privileged:
    // super_admin -> COTIA platform admin: creates/manages client organisations,
    //                belongs to no organisation, does not touch any org's project data
    // ed          -> Executive Director: admin of ONE organisation, manages its
    //                users/roles/projects, sees everything within that organisation
    // meo         -> M&E Officer   -> manages projects/forms, sees all data & reports within the organisation
    // po          -> Project Officer -> manages field officers on their project(s), reviews & compiles reports
    // fo          -> Field Officer -> collects data only, sees their own submissions
    public const ROLE_SUPER_ADMIN = 'super_admin';
    public const ROLE_ED = 'ed';
    public const ROLE_MEO = 'meo';
    public const ROLE_PO = 'po';
    public const ROLE_FO = 'fo';

    protected $fillable = [
        'organization_id', 'name', 'email', 'phone', 'password', 'role', 'supervisor_id', 'is_active', 'avatar_path',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ED;
    }

    public function isSuperAdmin(): bool
    {
        return $this->role === self::ROLE_SUPER_ADMIN;
    }

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public function supervisor()
    {
        return $this->belongsTo(User::class, 'supervisor_id');
    }

    public function projects()
    {
        return $this->belongsToMany(Project::class);
    }

    public function submissions()
    {
        return $this->hasMany(FormSubmission::class, 'collected_by');
    }
}
