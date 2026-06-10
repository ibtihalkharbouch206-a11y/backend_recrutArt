<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Offre;
use App\Models\Profil;
use Illuminate\Support\Facades\Auth;

class OffreController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        if ($user && strtolower((string) $user->role) === 'admin') {
            return response()->json(Offre::latest()->get());
        }

        $userId = Auth::id();
        return response()->json(
            Offre::where('status', 'approved')
                ->orWhere('user_id', $userId)
                ->latest()
                ->get()
        );
    }

    public function suggestions(Request $request)
    {
        $profil = Profil::where('user_id', Auth::id())->first();
        if (!$profil) {
            return response()->json([]);
        }

        $keywords = [];
        if ($profil->metier) $keywords[] = $profil->metier;
        if ($profil->competences) {
            $parts = preg_split('/[,;\n]+/', $profil->competences);
            foreach (($parts ?: []) as $p) {
                $k = trim($p);
                if ($k !== '') $keywords[] = $k;
            }
        }

        $keywords = array_values(array_unique(array_slice($keywords, 0, 8)));
        if (count($keywords) === 0) return response()->json([]);

        $query = Offre::query()->where('status', 'approved')->latest();
        $query->where(function ($q) use ($keywords) {
            foreach ($keywords as $k) {
                $q->orWhere('titre', 'like', "%{$k}%")
                  ->orWhere('description', 'like', "%{$k}%");
            }
        });

        if ($profil->ville) {
            $query->orderByRaw('CASE WHEN ville = ? THEN 0 ELSE 1 END', [$profil->ville]);
        }

        return response()->json($query->limit(10)->get());
    }

    public function store(Request $request)
    {
        $request->validate([
            'titre'        => 'required|string|max:255',
            'description'  => 'required|string',
            'ville'        => 'nullable|string|max:100',
            'type_contrat' => 'nullable|string|max:50',
            'temps'        => 'nullable|string|max:50',
            'prix'         => 'nullable|string|max:50',
        ]);

        $offre = Offre::create([
            'titre'        => $request->titre,
            'description'  => $request->description,
            'ville'        => $request->ville,
            'type_contrat' => $request->type_contrat,
            'temps'        => $request->temps,
            'prix'         => $request->prix,
            'user_id'      => Auth::id(),
            'status'       => 'approved',
        ]);

        return response()->json($offre);
    }

    public function show($id)
    {
        $offre = Offre::findOrFail($id);
        // A1: ensure the user is authorised to view this offre
        $this->authorize('view', $offre);
        return response()->json($offre);
    }

    public function update(Request $request, $id)
    {
        $offre = Offre::findOrFail($id);
        // A1: replaces manual if-check — policy's before() still lets admins through
        $this->authorize('update', $offre);

        $offre->update($request->only([
            'titre', 'description', 'ville', 'type_contrat', 'temps', 'prix'
        ]));

        return response()->json($offre);
    }

    public function destroy($id)
    {
        $offre = Offre::findOrFail($id);
        // A1: replaces manual if-check
        $this->authorize('delete', $offre);

        $offre->delete();

        return response()->json(['message' => 'deleted']);
    }

    public function markAsCompleted($id)
    {
        $offre = Offre::findOrFail($id);
        // A1: replaces manual if-check
        $this->authorize('markAsCompleted', $offre);

        $offre->update(['status' => 'completed']);

        return response()->json($offre);
    }
}