<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->unique()->constrained()->onDelete('cascade');
            $table->integer('quantity')->default(0);
            $table->decimal('total_cost_value', 14, 2)->default(0);
            $table->decimal('total_sale_value', 14, 2)->default(0);
            $table->decimal('projected_profit', 14, 2)->default(0);
            $table->timestamp('last_movement_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('quantity');
            $table->index('last_movement_at');
            $table->index('updated_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory');
    }
};
