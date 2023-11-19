<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Job extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'requirements',
        'salary',
        'location',
        'company_user_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */

    public function companyUser()
    {
        return $this->belongsTo(CompanyUser::class);
    }

    public function user() {
        return $this->belongsToMany(User::class);
    }
}
