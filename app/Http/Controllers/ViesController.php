<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ViesController extends Controller
{
    public function validateVies(Request $request)
    {
        $request->validate([
            'country' => 'required|string|size:2',
            'vat'     => 'required|string',
        ]);

        // TODO: chamar teu ViesService real
        return response()->json([
            'valid'   => true,
            'name'    => 'Empresa Exemplo',
            'address' => $request->country,
        ]);
    }
}
