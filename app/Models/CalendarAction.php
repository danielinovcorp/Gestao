<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\BelongsToTenant;

class CalendarAction extends Model
{
	use HasFactory, BelongsToTenant;
	protected $fillable = ['nome', 'ativo'];
}
