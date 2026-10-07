<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ScraperTask extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'source_name',
        'target_url',
        'category',
        'status',
        'frequency',
        'items_scraped',
        'last_run_at',
        'last_log',
        'user_id',
    ];

    protected $casts = [
        'last_run_at' => 'datetime',
        'items_scraped' => 'integer',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function properties()
    {
        return $this->hasMany(Property::class);
    }
}
