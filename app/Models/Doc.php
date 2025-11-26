<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Casts\AsArrayObject;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use App\Models\Concerns\BelongsToTenant;

class Doc extends Model
{
	use SoftDeletes, BelongsToTenant;

	protected $fillable = [
		'documentable_type',
		'documentable_id',
		'title',
		'original_name',
		'ext',
		'mime',
		'size',
		'tags',
		'notes',
		'disk',
		'path',
		'sha256',
		'uploaded_by',
		'valid_until',
	];

	protected $casts = [
		// criptografar metadados sensíveis
		'title' => 'encrypted:string',
		'original_name' => 'encrypted:string',
		'mime' => 'encrypted:string',
		'notes' => 'encrypted:string',
		'tags' => AsArrayObject::class,
		'valid_until' => 'datetime',
		'size' => 'integer',
	];

	public function documentable(): MorphTo
	{
		return $this->morphTo();
	}

	public function uploader()
	{
		return $this->belongsTo(User::class, 'uploaded_by');
	}

	public function scopeFilter($q, array $f)
	{
		if (!empty($f['q'])) {
			$q->where(function ($qq) use ($f) {
				$qq->where('title', 'like', '%' . $f['q'] . '%')
					->orWhere('original_name', 'like', '%' . $f['q'] . '%')
					->orWhereJsonContains('tags', $f['q']);
			});
		}
		if (!empty($f['type'])) {
			$q->where('documentable_type', $f['type']);
		}
		if (!empty($f['from'])) $q->whereDate('created_at', '>=', $f['from']);
		if (!empty($f['to']))   $q->whereDate('created_at', '<=', $f['to']);
		return $q;
	}
}
