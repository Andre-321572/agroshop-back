<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Services\AiAssistantService;
use Illuminate\Http\Request;

class AiAssistantController extends Controller
{
    protected $aiService;

    public function __construct(AiAssistantService $aiService)
    {
        $this->aiService = $aiService;
    }

    public function chat(Request $request)
    {
        $validated = $request->validate([
            'prompt' => 'required|string|min:3'
        ]);

        $reponse = $this->aiService->interroger($validated['prompt']);

        return response()->json([
            'reponse' => $reponse
        ]);
    }
}
