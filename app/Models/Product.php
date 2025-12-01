<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'sku',
        'name',
        'description',
        'cost_price',
        'sale_price',
        'is_active',
    ];

    protected $casts = [
        'cost_price' => 'decimal:2',
        'sale_price' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function inventory(): HasOne
    {
        return $this->hasOne(Inventory::class);
    }

    public function movements(): HasMany
    {
        return $this->hasMany(InventoryMovement::class);
    }

    public function saleItems(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }

    public function getProfitMarginAttribute(): float
    {
        return $this->sale_price - $this->cost_price;
    }

    public function getProfitPercentageAttribute(): float
    {
        if ($this->cost_price == 0) {
            return 0;
        }
        return (($this->sale_price - $this->cost_price) / $this->cost_price) * 100;
    }
}
