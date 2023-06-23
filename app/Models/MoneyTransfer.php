<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MoneyTransfer extends Model
{
    use HasFactory;
    protected $fillable = ['sender_id', 'getter_id', 'amount'];
}
