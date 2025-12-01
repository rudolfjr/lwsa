<?php

namespace App\Console\Commands;

use App\Models\AuditLog;
use App\Services\InventoryService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ArchiveStaleInventory extends Command
{
    protected $signature = 'inventory:archive-stale {--days=90 : Number of days without movement}';
    protected $description = 'Archive or flag inventory items that have not been updated in the specified number of days';

    public function __construct(
        protected InventoryService $inventoryService
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $days = (int) $this->option('days');

        $this->info("Looking for inventory items with no movement in the last {$days} days...");

        $staleInventory = $this->inventoryService->getStaleInventory($days);

        if ($staleInventory->isEmpty()) {
            $this->info('No stale inventory items found.');
            return Command::SUCCESS;
        }

        $this->info("Found {$staleInventory->count()} stale inventory items.");

        $archived = 0;
        foreach ($staleInventory as $inventory) {
            $inventory->product->update(['is_active' => false]);

            AuditLog::log(
                'archive',
                'Inventory',
                $inventory->id,
                ['is_active' => true],
                ['is_active' => false, 'reason' => "No movement in {$days} days"]
            );

            $archived++;

            $this->line("  - Archived: {$inventory->product->sku} ({$inventory->product->name})");
        }

        Log::info("Archived {$archived} stale inventory items", [
            'days' => $days,
            'count' => $archived,
        ]);

        $this->info("Successfully archived {$archived} inventory items.");

        return Command::SUCCESS;
    }
}
