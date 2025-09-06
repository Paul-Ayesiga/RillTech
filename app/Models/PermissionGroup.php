<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PermissionGroup extends Model
{
    protected $fillable = [
        'name',
        'description',
        'color',
        'display_order',
    ];

    /**
     * Get the permissions that belong to this group.
     */
    public function permissions()
    {
        return $this->hasMany(\Spatie\Permission\Models\Permission::class, 'group_id');
    }
}
