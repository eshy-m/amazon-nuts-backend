<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes; // Importamos SoftDeletes

class ContactMessage extends Model
{
    use HasFactory, SoftDeletes; // Lo activamos aquí

    protected $fillable = [
        'sender_name',
        'company',
        'email',
        'country',
        'product_interest',
        'message',
        'status'
    ];
}