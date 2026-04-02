<?php

namespace Database\Factories;

use App\Models\Department;
use App\Models\Employee;
use App\Models\PurchaseOrder;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Database\Eloquent\Factories\Factory;

class PurchaseOrderFactory extends Factory
{
    protected $model = PurchaseOrder::class;

    public function definition(): array
    {
        $subtotal = fake()->randomFloat(2, 1000, 50000);
        $tax = round($subtotal * 0.075, 2);
        $discount = 0;

        return [
            'po_number' => 'PO-' . fake()->unique()->numerify('######'),
            'vendor_id' => Vendor::factory(),
            'department_id' => Department::factory(),
            'requested_by' => Employee::factory(),
            'submitted_by' => User::factory(),
            'order_date' => fake()->dateTimeBetween('-30 days', 'now'),
            'expected_delivery_date' => fake()->dateTimeBetween('now', '+60 days'),
            'subtotal' => $subtotal,
            'tax_amount' => $tax,
            'discount_amount' => $discount,
            'total_amount' => $subtotal + $tax - $discount,
            'currency' => 'NGN',
            'notes' => fake()->sentence(),
            'status' => 'draft',
            'payment_status' => 'unpaid',
        ];
    }
}
