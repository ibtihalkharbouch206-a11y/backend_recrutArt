<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Candidature;
use App\Models\Offre;
use App\Models\Review;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use App\Mail\CandidatureSent;

class CandidatureController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'offre_id' => 'required|exists:offres,id',
        ]);

        $offreId = (int) $request->offre_id;

        $already = Candidature::where('user_id', Auth::id())
            ->where('offre_id', $offreId)
            ->exists();
        if ($already) {
            return response()->json(['message' => 'Déjà postulé à cette offre.'], 409);
        }

        $candidature = Candidature::create([
            'user_id' => Auth::id(),
            'offre_id' => $offreId,
            'motivation' => $request->motivation,
            'experience' => $request->experience,
        ]);

        // Email simple au recruteur
        $offre = Offre::with('user')->find($offreId);
        $recruteur = $offre?->user;
        $artisan = $request->user();
        if ($recruteur && $recruteur->email) {
            try {
                Mail::to($recruteur->email)->send(new CandidatureSent($candidature, $offre, $artisan));
            } catch (\Throwable $e) {
                // Ne bloque pas l'API si le mail échoue
            }
        }

        return response()->json($candidature);
    }

    public function myCandidatures()
    {
        return response()->json(
            Candidature::with('offre')
                ->where('user_id', Auth::id())
                ->get()
        );
    }

    public function candidaturesRecues()
    {
        $recruteurId = Auth::id();
        $offreIds = Offre::where('user_id', $recruteurId)->pluck('id');

        $candidatures = Candidature::with(['user.profil', 'offre'])
            ->whereIn('offre_id', $offreIds)
            ->latest()
            ->get();

        $reviews = Review::where('recruteur_id', $recruteurId)
            ->whereIn('offre_id', $offreIds)
            ->get()
            ->keyBy(fn ($review) => $review->offre_id . '-' . $review->artisan_id);

        return response()->json(
            $candidatures->map(function ($candidature) use ($reviews) {
                $key = $candidature->offre_id . '-' . $candidature->user_id;
                $candidature->review = $reviews->get($key);
                return $candidature;
            })
        );
    }

    public function getStats()
    {
        // Récupérer toutes les offres du recruteur connecté
        $offreIds = Offre::where('user_id', Auth::id())->pluck('id');

        // Récupérer les statistiques des candidatures
        $total = Candidature::whereIn('offre_id', $offreIds)->count();
        $en_attente = Candidature::whereIn('offre_id', $offreIds)->where('status', 'en_attente')->count();
        $acceptee = Candidature::whereIn('offre_id', $offreIds)->where('status', 'acceptee')->count();
        $refusee = Candidature::whereIn('offre_id', $offreIds)->where('status', 'refusee')->count();

        return response()->json([
            'total' => $total,
            'en_attente' => $en_attente,
            'acceptee' => $acceptee,
            'refusee' => $refusee
        ]);
    }

    public function byOffre($offre_id)
    {
        $offre = Offre::findOrFail($offre_id);
        if ($offre->user_id !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        return response()->json(
            Candidature::with('user')
                ->where('offre_id', $offre_id)
                ->get()
        );
    }

    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:en_attente,acceptee,refusee',
        ]);

        $candidature = Candidature::with('offre')->findOrFail($id);

        // Only the owner of the offer (recruiter) may change the candidature status.
        if ($candidature->offre->user_id !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $candidature->update(['status' => $request->status]);

        if ($request->status === 'acceptee') {
            \App\Models\Conversation::firstOrCreate([
                'offre_id' => $candidature->offre_id,
                'recruteur_id' => $candidature->offre->user_id,
                'artisan_id' => $candidature->user_id,
            ]);

            // No change to portfolio visibility here — admin may only delete inappropriate items.
        }

        return response()->json($candidature);
    }
}