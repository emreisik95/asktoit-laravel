<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentPlans extends Model
{
    use HasFactory;
    protected $table = 'plans';

    // gateway_products
    public function gateway_products()
    {
        return $this->hasMany(GatewayProducts::class, 'plan_id', 'id');
    }

}
