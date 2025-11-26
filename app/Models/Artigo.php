<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Concerns\BelongsToTenant;

class Artigo extends Model
{
    use SoftDeletes, BelongsToTenant;

    protected $table = 'artigos';

    protected $fillable = [
        'referencia',
        'nome',
        'descricao',
        'preco',
        'iva_id',
        'foto_path',
        'observacoes',
        'estado',
    ];

    protected $casts = [
        'preco' => 'decimal:2',
    ];

    // Scope de estado (se quiser usar depois)
    public function scopeEstado($q, ?string $estado)
    {
        if (in_array($estado, ['ativo', 'inativo'], true)) {
            $q->where('estado', $estado);
        }
    }

    // URL segura da foto via rota files.private.show
    protected $appends = ['foto_url'];

    public function getFotoUrlAttribute(): ?string
    {
        if (!$this->foto_path) return null;
        return route('files.private.show', ['path' => $this->foto_path]);
    }
}
