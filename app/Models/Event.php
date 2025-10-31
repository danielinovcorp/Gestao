<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
	protected $fillable = ['user_id', 'title', 'start', 'end', 'color'];
	protected $casts = ['start' => 'datetime', 'end' => 'datetime'];
}
