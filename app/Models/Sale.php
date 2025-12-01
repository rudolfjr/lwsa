<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Sale extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'code',
        'status',
        'total_amount',
        'total_cost',
        'profit_margin',
        'profit_percentage',
        'failure_reason',
        'user_id',
        'completed_at',
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
        'total_cost' => 'decimal:2',
        'profit_margin' => 'decimal:2',
        'profit_percentage' => 'decimal:2',
        'completed_at' => 'datetime',
    ];

    public const STATUS_PENDING = 'pending';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_FAILED = 'failed';
    public const STATUS_CANCELLED = 'cancelled';

    public function items(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isProcessing(): bool
    {
        return $this->status === self::STATUS_PROCESSING;
    }

    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    public function isFailed(): bool
    {
        return $this->status === self::STATUS_FAILED;
    }

    public function isCancelled(): bool
    {
        return $this->status === self::STATUS_CANCELLED;
    }

    public function calculateTotals(): void
    {
        $this->total_amount = $this->items->sum('subtotal');
        $this->total_cost = $this->items->sum(fn ($item) => $item->unit_cost * $item->quantity);
        $this->profit_margin = $this->total_amount - $this->total_cost;
        $this->profit_percentage = $this->total_cost > 0
            ? (($this->profit_margin / $this->total_cost) * 100)
            : 0;
    }

    public static function generateCode(): string
    {
        $date = now()->format('Ymd');
        $lastSale = static::whereDate('created_at', today())
            ->orderBy('id', 'desc')
            ->first();

        $sequence = $lastSale ? ((int) substr($lastSale->code, -4)) + 1 : 1;

        return sprintf('SAL-%s-%04d', $date, $sequence);
    }
}
