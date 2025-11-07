<?php

namespace App\Http\Controllers\Config;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class EmpresaController extends Controller
{
    public function show(Request $request)
    {
        Log::info('📋 EmpresaController show called');

        $row = DB::table('empresa')->where('id', 1)->first();

        if (!$row) {
            Log::info('➕ Criando registro inicial da empresa');
            DB::table('empresa')->insert([
                'id' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $row = DB::table('empresa')->where('id', 1)->first();
        }

        Log::info('✅ Dados da empresa carregados', [
            'id' => $row->id,
            'nome' => $row->nome,
            'logo_path' => $row->logo_path
        ]);

        return Inertia::render('Config/Empresa/Index', [
            'company'    => $row, 
            'filesRoute' => route('files.private.show'),
        ]);
    }

    public function update(Request $request)
    {
        Log::info('📤 EmpresaController update called', $request->all());

        try {
            $data = $request->validate([
                'nome'            => ['nullable', 'string', 'max:255'],
                'morada'          => ['nullable', 'string', 'max:255'],
                'codigo_postal'   => ['nullable', 'string', 'max:20'],
                'localidade'      => ['nullable', 'string', 'max:255'],
                'nif'             => ['nullable', 'string', 'max:32'],
                'logo'            => ['nullable', 'file', 'mimetypes:image/png,image/jpeg,image/webp,image/svg+xml', 'max:4096'],
                'remove_logo'     => ['nullable', 'boolean'],
                '_method'         => ['required', 'string', 'in:put'],
            ]);

            Log::info('✅ Validação passada', $data);

            $row = DB::table('empresa')->where('id', 1)->first();
            if (!$row) {
                 DB::table('empresa')->insert(['id' => 1, 'created_at' => now(), 'updated_at' => now()]);
                 $row = DB::table('empresa')->where('id', 1)->first();
            }

            $logoPath = $row->logo_path ?? null;

            if ($request->boolean('remove_logo') && $logoPath) {
                Log::info('🗑️ Removendo logo atual', ['path' => $logoPath]);
                Storage::disk('private')->delete($logoPath);
                $logoPath = null;
            }

            if ($request->hasFile('logo')) {
                Log::info('🖼️ Fazendo upload de nova logo');
                if ($logoPath) {
                    Storage::disk('private')->delete($logoPath);
                }
                $logoPath = $request->file('logo')->store('empresa', 'private');
                Log::info('✅ Logo salva', ['path' => $logoPath]);
            }

            $updateData = [
                'nome'          => $data['nome'] ?? null,
                'morada'        => $data['morada'] ?? null,
                'codigo_postal' => $data['codigo_postal'] ?? null,
                'localidade'    => $data['localidade'] ?? null,
                'nif'           => $data['nif'] ?? null,
                'logo_path'     => $logoPath,
                'updated_at'    => now(),
            ];

            Log::info('💾 Salvando dados na BD:', $updateData);

            $result = DB::table('empresa')->where('id', 1)->update($updateData);

            Log::info('✅ Update result:', ['affected_rows' => $result]);

            return redirect()->route('config.empresa')
                ->with('success', 'Dados da empresa atualizados com sucesso.');
        } catch (\Exception $e) {
            Log::error('❌ Error updating empresa', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return back()->with('error', 'Erro ao atualizar dados da empresa: ' . $e->getMessage());
        }
    }

    /**
     * ✅ NOVO MÉTODO: Serve a logo da empresa do disco 'private'.
     */
    public function logo()
    {
        $row = DB::table('empresa')->where('id', 1)->first();
        
        // Verifica se o registro existe, se há um path e se o arquivo existe no disco
        if (!$row || !$row->logo_path || !Storage::disk('private')->exists($row->logo_path)) {
            // Se a logo não existe ou o path está inválido, retorna 404
            abort(404, 'Logo da empresa não encontrada.');
        }

        // Retorna o arquivo como uma resposta HTTP, com headers corretos
        return Storage::disk('private')->response($row->logo_path);
    }
}