<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Link extends Model
{
    use HasFactory;
    protected $fillable = ['destination_url', 'short_url', 'tags', 'click_count', 'user_id'];


    public function user()
    {
        return $this->belongsTo(User::class);
    }

}
