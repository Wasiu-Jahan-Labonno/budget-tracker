<?php

namespace App\Models;

use App\Enums\TransactionType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;


class Transaction extends Model
{
    protected $fillable = [
        'user_id','category_id','title','type','amount','occurred_on','is_salary','note'
    ];

    protected $casts = [
        'occurred_on' => 'date:Y-m-d',
        'is_salary'   => 'boolean',
        'type'        => TransactionType::class,
        'amount'      => 'decimal:2',
    ];


   
    public function user(): BelongsTo {
        return $this->belongsTo(User::class);
    }

    public function category(): BelongsTo {
        return $this->belongsTo(Category::class);
    }
}
