<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ContactController extends Controller
{
    /**
     * POST /api/contact
     * Traitement du formulaire de contact public.
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'nom' => 'required|string|max:100',
            'email' => 'required|email|max:150',
            'telephone' => 'nullable|string|max:20',
            'sujet' => 'required|string|max:200',
            'message' => 'required|string|min:10',
        ], [
            'nom.required' => 'Votre nom est obligatoire.',
            'email.required' => 'Votre adresse e-mail est obligatoire.',
            'sujet.required' => 'Le sujet du message est obligatoire.',
            'message.required' => 'Veuillez saisir votre message (10 caractères minimum).',
        ]);

        // Ici, un mail ou une notification administrative peut être envoyé(e)
        
        return response()->json([
            'status' => 'success',
            'message' => 'Votre message a bien été envoyé ! Notre équipe vous répondra dans les plus brefs délais.',
        ]);
    }
}
