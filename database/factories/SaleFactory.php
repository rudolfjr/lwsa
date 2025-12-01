<?php

namespace Database\Factories;

use App\Models\Sale;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class SaleFactory extends Factory
{
    protected $model = Sale::class;

    public function definition(): array
    {
        $createdAt = fake()->dateTimeBetween('-12 months', 'now');

        return [
            'code' => sprintf(
                'SAL-%s-%04d',
                $createdAt->format('Ymd'),
                fake()->unique()->numberBetween(1, 9999)
            ),
            'status' => Sale::STATUS_COMPLETED,
            'total_amount' => 0,
            'total_cost' => 0,
            'profit_margin' => 0,
            'profit_percentage' => 0,
            'user_id' => User::factory(),
            'completed_at' => $createdAt,
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ];
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Sale::STATUS_PENDING,
            'completed_at' => null,
        ]);
    }

    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Sale::STATUS_FAILED,
            'failure_reason' => 'Insufficient stock',
            'completed_at' => null,
        ]);
    }
}
