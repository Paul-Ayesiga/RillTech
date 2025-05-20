<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Permission\Models\Permission as SpatiePermission;

class Permission extends SpatiePermission
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'guard_name',
        'group_id',
    ];

    /**
     * Get the group that this permission belongs to.
     */
    public function group(): BelongsTo
    {
        return $this->belongsTo(PermissionGroup::class, 'group_id');
    }

    /**
     * Make sure roles are always loaded with the permission
     */
    protected $with = ['roles'];
}
