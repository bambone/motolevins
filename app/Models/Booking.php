<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Booking extends Model
{
    /** @use HasFactory<\Database\Factories\BookingFactory> */
    use HasFactory;

    protected $fillable = [
        'bike_id',
        'start_date',
        'end_date',
        'status',
        'price_per_day_snapshot',
        'total_price',
        'customer_name',
        'phone',
        'phone_normalized',
        'source',
        'customer_comment',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'status' => \App\Enums\BookingStatus::class,
        'price_per_day_snapshot' => 'integer',
        'total_price' => 'integer',
    ];

    public function bike()
    {
        return $this->belongsTo(Bike::class);
    }
}
