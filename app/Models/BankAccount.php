<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BankAccount extends Model
{
    use HasFactory, HasUlids;

    protected $fillable = [
        'account_number',
        'account_type',
        'email',
        'account_holder',
        'account_balance',
        'user_id'
    ];



    public function transferHistory(): HasMany
    {
        return $this->hasMany(TransferHistory::class, 'bank_accounts_id', 'id');
    }
}
