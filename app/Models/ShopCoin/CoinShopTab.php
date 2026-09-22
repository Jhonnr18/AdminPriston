<?php

namespace App\Models\ShopCoin;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CoinShopTab extends Model
{
    protected $connection = 'shopcoin';

    protected $table = 'CoinShopTab';

    protected $primaryKey = 'ID';

    public $timestamps = false;

    protected $fillable = [
        'CoinShopID',
        'Name',
        'ParentID',
        'Discount',
        'Bulk',
        'MaxBulk',
        'ListOrder',
    ];

    public function shop(): BelongsTo
    {
        return $this->belongsTo(CoinShop::class, 'CoinShopID', 'ID');
    }

    public function items(): HasMany
    {
        return $this->hasMany(CoinShopItem::class, 'TabID', 'ID');
    }
}
