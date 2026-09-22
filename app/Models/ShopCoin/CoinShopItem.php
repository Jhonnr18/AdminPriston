<?php

namespace App\Models\ShopCoin;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CoinShopItem extends Model
{
    protected $connection = 'shopcoin';

    protected $table = 'CoinShopItem';

    protected $primaryKey = 'ID';

    public $timestamps = false;

    protected $fillable = [
        'TabID',
        'Name',
        'Description',
        'Code',
        'Image',
        'Value',
        'Discount',
        'Bulk',
        'MaxBulk',
        'IsSpec',
        'IsQuantity',
        'Disabled',
        'ListOrder',
    ];

    public function tab(): BelongsTo
    {
        return $this->belongsTo(CoinShopTab::class, 'TabID', 'ID');
    }
}
