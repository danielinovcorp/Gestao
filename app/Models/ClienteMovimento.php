<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClienteMovimento extends Model
{
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
