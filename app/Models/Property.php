<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Property extends Model
{
    use HasFactory;

    protected $fillable = [
        'source',
        'source_name',
        'source_id',
        'title',
        'property_type',
        'listing_type',
        'price_usd',
        'price',
        'currency',
        'area_sqm',
        'price_per_sqm',
        'province',
        'district',
        'commune',
        'address',
        'location',
        'city',
        'bedrooms',
        'bathrooms',
        'url',
        'source_url',
        'image_url',
        'images',
        'urgency_tag',
        'status',
        'latitude',
        'longitude',
        'is_featured',
        'scraper_task_id',
        'created_by',
    ];

    protected $casts = [
        'price_usd' => 'float',
        'price' => 'float',
        'area_sqm' => 'float',
        'price_per_sqm' => 'float',
        'latitude' => 'float',
        'longitude' => 'float',
        'is_featured' => 'boolean',
        'images' => 'array',
    ];

    public function scraperTask()
    {
        return $this->belongsTo(ScraperTask::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getEffectivePriceAttribute()
    {
        return $this->price_usd ?? $this->price ?? 0;
    }

    public function getFormattedPriceAttribute()
    {
        $val = $this->effective_price;
        if ($val <= 0) {
            return 'Contact for Price';
        }

        $isRent = strtolower($this->listing_type ?? '') === 'rent';

        // Anomaly handling for sale listings with tiny placeholder or unit prices ($1 - $49)
        if (!$isRent && $val < 50) {
            if ($this->area_sqm && $this->area_sqm > 50) {
                $totalVal = round($val * $this->area_sqm);
                return '$' . number_format($totalVal, 0) . ' ($' . number_format($val, 1) . '/m²)';
            }
            return 'Contact for Price';
        }

        $formatted = '$' . number_format($val, 0);
        if ($isRent) {
            return $formatted . '/mo';
        }
        return $formatted;
    }

    public function getDisplayLocationAttribute()
    {
        if ($this->location) {
            return $this->location . ($this->city ? ', ' . $this->city : '');
        }
        $parts = array_filter([$this->district, $this->province ?? $this->city]);
        return count($parts) > 0 ? implode(', ', $parts) : 'Cambodia';
    }

    public function getComputedPricePerSqmAttribute()
    {
        // Suppress $/sqm for rentals to avoid confusing metrics like $4/m²
        if (strtolower($this->listing_type ?? '') === 'rent') {
            return null;
        }

        if ($this->price_per_sqm && $this->price_per_sqm > 0) {
            return $this->price_per_sqm;
        }
        if ($this->effective_price > 0 && $this->area_sqm && $this->area_sqm > 0) {
            return round($this->effective_price / $this->area_sqm, 2);
        }
        return null;
    }

    public static function formatDisplayImageUrl(?string $url): string
    {
        if (empty($url)) {
            return 'https://images.unsplash.com/photo-1600585154340-be6161a56a0c?auto=format&fit=crop&w=800&q=80';
        }

        if (
            str_contains($url, 'synpanel.com') ||
            str_contains($url, 'synassets') ||
            str_contains($url, 'realestate.com.kh') ||
            str_contains($url, 'harbor-property.com') ||
            str_contains($url, 'c21.com.kh') ||
            str_contains($url, 'cambodia-realestate.com') ||
            str_contains($url, 'propnexkh.com') ||
            str_contains($url, 'bayonapp.com')
        ) {
            return '/proxy/image?url=' . urlencode($url);
        }

        return $url;
    }

    public function getDisplayImageUrlAttribute(): string
    {
        return self::formatDisplayImageUrl($this->image_url);
    }

    public function getGalleryImagesAttribute(): array
    {
        $imgs = $this->images;
        if (is_array($imgs) && count($imgs) > 0) {
            return array_values(array_map(function($u) {
                return is_string($u) ? self::formatDisplayImageUrl($u) : $u;
            }, array_filter($imgs)));
        }

        return [$this->display_image_url];
    }
}
