<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\Customer;
use App\Models\OrderItem;
use App\Models\Refund;
use App\Models\NotificationHistory;

class Order extends Model
{
    protected $guarded= ['id'];

    protected $casts = [
        'payment_data' => 'array',
        'processed_at' => 'datetime',
        'total_amount' => 'decimal:2',
        'refunded_amount' => 'decimal:2',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function refunds()
    {
        return $this->hasMany(Refund::class);
    }

    public function notificationsHistory()
    {
        return $this->hasMany(NotificationHistory::class);
    }

    public function getRemainingRefundableAmount(): float
    {
        return $this->total_amount - $this->refunded_amount;
    }
}
