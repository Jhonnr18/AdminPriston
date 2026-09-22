<?php

namespace App\Models\ShopCoin;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CoinShop extends Model
{
    protected $connection = 'shopcoin';

    protected $table = 'CoinShop';

    protected $primaryKey = 'ID';

    public $timestamps = false;

    protected $fillable = [
        'Name',
        'Message',
        'Discount',
        'Active',
    ];

    public function tabs(): HasMany
    {
        return $this->hasMany(CoinShopTab::class, 'CoinShopID', 'ID');
    }
}
