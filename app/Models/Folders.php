<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Folders extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'created_by',
    ];


    public function userOpenais()
    {
        return $this->hasMany(UserOpenai::class, 'folder_id');
    }
}
