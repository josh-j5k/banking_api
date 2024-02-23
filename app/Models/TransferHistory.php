<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TransferHistory extends Model
{
    use HasFactory;
    protected $table = 'transfer_history';
    protected $fillable = [
        'amount',
        'receivers_name',
        'receivers_account_number',
        'bank_account_id',
    ];
}
