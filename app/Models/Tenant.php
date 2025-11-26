<?php

namespace App\Models;

use Stancl\Tenancy\Database\Models\Tenant as BaseTenant;
use Illuminate\Support\Str;

class Tenant extends BaseTenant
{
    protected $fillable = [
        'id',
        'owner_id',
        'name',
        'slug',
        'data',
        'trial_ends_at',
        'plan',
        'plan_ends_at',
    ];

    protected $casts = [
        'data' => 'array',
        'trial_ends_at' => 'datetime',
        'plan_ends_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($tenant) {
            if (empty($tenant->id)) {
                $tenant->id = (string) Str::ulid();
            }
            if (empty($tenant->slug)) {
                $tenant->slug = Str::slug($tenant->name);
            }
        });
    }

    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'tenant_user')
            ->withPivot('role')
            ->withTimestamps();
    }
}