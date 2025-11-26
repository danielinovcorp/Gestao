<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\BelongsToTenant;


class Event extends Model
{
	use BelongsToTenant;
	protected $fillable = ['user_id', 'title', 'start', 'end', 'color'];
	protected $casts = ['start' => 'datetime', 'end' => 'datetime'];
}
