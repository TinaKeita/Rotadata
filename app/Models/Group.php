<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Group extends Model
{
    protected $fillable = [
        'name',
        'admin_id'  
    ];
    public function admin()
    {
        return $this->belongsTo(User::class);
    }

    public function members()
    {
        return $this->belongsToMany(User::class, 'group_user');
    }

    public function costumes()
    {
        return $this->hasMany(Costume::class);
    }


}
