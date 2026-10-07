<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Property extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'property_type',
        'listing_type',
        'price',
        'currency',
        'location',
        'city',
        'bedrooms',
        'bathrooms',
        'area_sqm',
        'source_name',
        'source_url',
        'image_url',
        'status',
        'is_featured',
        'scraper_task_id',
        'created_by',
    ];

    protected $casts = [
        'price' => 'float',
        'area_sqm' => 'float',
        'is_featured' => 'boolean',
    ];

    public function scraperTask()
    {
        return $this->belongsTo(ScraperTask::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getFormattedPriceAttribute()
    {
        if ($this->currency === 'USD') {
            return '$' . number_format($this->price, 0);
        }
        return number_format($this->price, 0) . ' ' . $this->currency;
    }
}
