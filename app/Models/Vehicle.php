<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * The "Vehicle" model is the storefront's Product. Table is still named `vehicles`
 * for backwards-compat (renames + FK rewrites are a separate phase) but the lean
 * column set is: title, slug, price, stock, vin (SKU), features, description, overview,
 * is_special, status + workflow timestamps + product_category_listing_option_id (Phase 7).
 */
class Vehicle extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'title',
        'slug',
        'status',
        'price',
        'stock',
        'features',
        'vin',
        'description',
        'overview',
        'composition_care',
        'shipping_returns',
        'is_special',
        'submitted_at',
        'approved_at',
        'approved_by',
        'rejection_reason',
        'product_category_listing_option_id',
        'import_key',
    ];

    protected function casts(): array
    {
        return [
            'submitted_at' => 'datetime',
            'approved_at' => 'datetime',
            'features' => 'array',
            'is_special' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** Same rule as dashboard “Staff listing” vs vendor: owner is console staff. */
    public function isStaffListing(): bool
    {
        return $this->user?->isStaff() ?? false;
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function images(): HasMany
    {
        return $this->hasMany(VehicleImage::class)->orderBy('sort_order');
    }

    public function variants(): HasMany
    {
        return $this->hasMany(VehicleVariant::class)->where('is_active', true)->orderBy('id');
    }

    public function allVariants(): HasMany
    {
        return $this->hasMany(VehicleVariant::class)->orderBy('id');
    }

    public function inquiries(): HasMany
    {
        return $this->hasMany(VehicleInquiry::class);
    }

    public function favoritedByUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'vehicle_favorites')->withTimestamps();
    }

    /**
     * Product category (added in Phase 7). Backed by listing_options under the
     * 'product_category' slug; nullable until categories are configured + assigned.
     */
    public function categoryOption(): BelongsTo
    {
        return $this->belongsTo(ListingOption::class, 'product_category_listing_option_id');
    }
}
