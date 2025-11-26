<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\BelongsToTenant;

class ClienteMovimento extends Model
{
	use BelongsToTenant;
    protected $fillable = [
        'cliente_id','data','descricao','documento_tipo','documento_numero','debito','credito'
    ];

    protected $casts = [
        'data' => 'date',
        'debito' => 'decimal:2',
        'credito' => 'decimal:2',
    ];

    public function cliente() { return $this->belongsTo(Entidade::class, 'cliente_id'); }
}
