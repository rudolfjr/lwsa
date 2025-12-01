<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Inventory extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'inventory';

    protected $fillable = [
        'product_id',
        'quantity',
        'total_cost_value',
        'total_sale_value',
        'projected_profit',
        'last_movement_at',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'total_cost_value' => 'decimal:2',
        'total_sale_value' => 'decimal:2',
        'projected_profit' => 'decimal:2',
        'last_movement_at' => 'datetime',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function recalculate(): void
    {
        $product = $this->product;
        $this->total_cost_value = $this->quantity * $product->cost_price;
        $this->total_sale_value = $this->quantity * $product->sale_price;
        $this->projected_profit = $this->total_sale_value - $this->total_cost_value;
    }
}
