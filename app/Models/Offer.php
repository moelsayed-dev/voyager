<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Offer extends Model
{
    use HasFactory;
    protected $fillable = [
        'user_id', 'trip_id', 'offer_price', 'number_of_seats_needed'
    ];

    public function owner()
    {
        return $this->belongsTo('App\Models\User', 'user_id');
    }
    public function trip()
    {
        return $this->belongsTo('App\Models\Trip');
    }
}
