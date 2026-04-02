<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Vendor extends Model
{
    use HasUuids, HasFactory;

    protected $fillable = [
        'vendor_code',
        'name',
        'category_id',
        'contact_person',
        'email',
        'phone',
        'address',
        'city',
        'state',
        'country',
        'tax_id',
        'bank_name',
        'bank_account_number',
        'rating',
        'notes',
        'status',
    ];

    /* ----------------------------------------------------------------
     * Scopes
     * ---------------------------------------------------------------- */

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /* ----------------------------------------------------------------
     * Relationships
     * ---------------------------------------------------------------- */

    public function category()
    {
        return $this->belongsTo(VendorCategory::class, 'category_id');
    }

    public function purchaseOrders()
    {
        return $this->hasMany(PurchaseOrder::class);
    }

    /* ----------------------------------------------------------------
     * Accessors
     * ---------------------------------------------------------------- */

    public function getPurchaseOrderCountAttribute(): int
    {
        return $this->purchaseOrders()->count();
    }

    /* ----------------------------------------------------------------
     * Auto-generate vendor code
     * ---------------------------------------------------------------- */

    public static function generateVendorCode(): string
    {
        $latest = self::where('vendor_code', 'like', 'VND-%')
            ->orderBy('vendor_code', 'desc')
            ->first();

        if ($latest) {
            $lastNumber = (int) substr($latest->vendor_code, 4);
            $nextNumber = $lastNumber + 1;
        } else {
            $nextNumber = 1;
        }

        return 'VND-' . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);
    }

    /* ----------------------------------------------------------------
     * Serialization
     * ---------------------------------------------------------------- */

    public function toApiArray(): array
    {
        return [
            'id' => $this->id,
            'vendor_code' => $this->vendor_code,
            'name' => $this->name,
            'category_id' => $this->category_id,
            'category' => $this->category?->name,
            'contact_person' => $this->contact_person,
            'email' => $this->email,
            'phone' => $this->phone,
            'address' => $this->address,
            'city' => $this->city,
            'state' => $this->state,
            'country' => $this->country,
            'tax_id' => $this->tax_id,
            'bank_name' => $this->bank_name,
            'bank_account_number' => $this->bank_account_number,
            'rating' => $this->rating,
            'notes' => $this->notes,
            'purchase_order_count' => $this->purchase_order_count,
            'status' => $this->status,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
