<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CompanyKey extends Model
{
    protected $fillable = [
        'company_user_id',
        'key',
    ];

    protected $hidden = [
        'key',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */

    public function companyUser(){
        return $this->belongsTo(CompanyUser::class);
    }
}
