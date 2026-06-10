<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Profil;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class ProfilController extends Controller
{
    public function storeOrUpdate(Request $request)
    {
        $request->validate([
            'ville' => 'required',
            'telephone' => 'nullable|string',
            'description' => 'nullable',
            'nom_entreprise' => 'nullable|string',
            'email_entreprise' => 'nullable|email',
            'adresse' => 'nullable|string',
            'site_web' => 'nullable|string',
            'effectif' => 'nullable|string',
        ]);

        $metier = $request->input('metier') ?? $request->input('specialite') ?? $request->input('secteur') ?? 'Non spécifié';
        $experience = $request->input('experience') ?? 0;
        $competences = $request->input('competences') ?? $request->input('localisation');

        $user = Auth::user();
        if ($request->has('nom')) {
            $user->nom = $request->input('nom');
            $user->save();
        }

        $profil = Profil::updateOrCreate(
            ['user_id' => $user->id],
            [
                'nom_entreprise'   => $request->nom_entreprise,
                'email_entreprise' => $request->email_entreprise,
                'metier'           => $metier,
                'ville'            => $request->ville,
                'telephone'        => $request->telephone,
                'adresse'          => $request->adresse,
                'site_web'         => $request->site_web,
                'effectif'         => $request->effectif,
                'experience'       => $experience,
                'competences'      => $competences,
                'description'      => $request->description,
            ]
        );

        return response()->json([
            'profil' => $profil,
            'user' => $user
        ]);
    }

    public function show()
    {
        $profil = Profil::where('user_id', Auth::id())->first();
        if (!$profil) return response()->json(null);

        return response()->json([
            ...$profil->toArray(),
            'photo_url' => $profil->photo_path ? Storage::url($profil->photo_path) : null,
        ]);
    }

    public function uploadPhoto(Request $request)
    {
        $request->validate([
            'photo' => 'required|image|max:4096',
        ]);

        $profil = Profil::firstOrCreate(['user_id' => Auth::id()], [
            'metier' => '—',
            'ville' => '—',
            'experience' => 0,
        ]);

        if ($profil->photo_path) {
            Storage::disk('public')->delete($profil->photo_path);
        }

        $path = $request->file('photo')->store('profiles', 'public');
        $profil->photo_path = $path;
        $profil->save();

        return response()->json([
            'photo_path' => $profil->photo_path,
            'photo_url' => Storage::url($profil->photo_path),
        ]);
    }

    public function showPublic($id)
    {
        $user = \App\Models\User::with('profil')->find($id);
        if (!$user) {
            return response()->json(['message' => 'Utilisateur introuvable.'], 404);
        }

        $profil = $user->profil;
        $profilData = null;

        if ($profil) {
            $profilData = $profil->toArray();
            // A01: Filter out sensitive information for public profiles
            unset($profilData['telephone'], $profilData['adresse'], $profilData['email_entreprise']);
            $profilData['photo_url'] = $profil->photo_path ? Storage::url($profil->photo_path) : null;
        }

        $userData = $user->toArray();
        unset($userData['email']);

        return response()->json([
            'user' => $userData,
            'profil' => $profilData,
        ]);
    }
}