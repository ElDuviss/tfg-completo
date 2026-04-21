<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AnalysisController extends Controller
{
    public function storeJson(Request $request)
    {
        $request->validate([
            'user_id' => 'required|integer',
            'slug' => 'required|string',
            'cuestionario' => 'required',
            'fotos' => 'required|array|min:4',
        ]);

        $json = [
            'user_id' => $request->user_id,
            'slug' => $request->slug,
            'cuestionario' => $request->cuestionario,
            'fotos' => $request->fotos,
        ];

        if (!Storage::disk('local')->exists('analysis')) {
            Storage::disk('local')->makeDirectory('analysis');
        }

        $filename = 'analysis/' . time() . '.json';
        Storage::disk('local')->put($filename, json_encode($json, JSON_PRETTY_PRINT));

        return response()->json(['success' => true, 'file' => $filename]);
    }

}