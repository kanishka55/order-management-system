<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\Order;
use App\Models\NotificationHistory;

class Customer extends Model
{
    protected $guaeded = ['id'];

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function notificationsHistory()
    {
        return $this->hasMany(NotificationHistory::class);
    }
}
