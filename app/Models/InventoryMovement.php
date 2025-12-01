<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class InventoryMovement extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'product_id',
        'type',
        'quantity',
        'unit_cost',
        'reference_type',
        'reference_id',
        'notes',
        'user_id',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'unit_cost' => 'decimal:2',
    ];

    public const TYPE_ENTRY = 'entry';
    public const TYPE_EXIT = 'exit';

    public const REFERENCE_SALE = 'sale';
    public const REFERENCE_ADJUSTMENT = 'adjustment';
    public const REFERENCE_MANUAL = 'manual';

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isEntry(): bool
    {
        return $this->type === self::TYPE_ENTRY;
    }

    public function isExit(): bool
    {
        return $this->type === self::TYPE_EXIT;
    }
}
