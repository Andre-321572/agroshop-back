<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Partenaire;
use Illuminate\Http\Request;

class PartenaireController extends Controller
{
    public function index()
    {
        $partenaires = Partenaire::where('actif', true)->orderBy('created_at', 'desc')->get();
        return response()->json(['data' => $partenaires]);
    }
}
