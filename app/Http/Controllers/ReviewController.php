<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Review;
use App\Models\Offre;
use App\Models\Candidature;
use Illuminate\Support\Facades\Auth;

class ReviewController extends Controller
{
    /**
     * Get reviews for a specific artisan.
     */
    public function artisanReviews($id)
    {
        $reviews = Review::with('recruteur.profil')
            ->where('artisan_id', $id)
            ->latest()
            ->get();

        $average = $reviews->avg('rating');
        $count = $reviews->count();

        return response()->json([
            'reviews' => $reviews,
            'average' => $average ? round($average, 1) : 0,
            'count' => $count
        ]);
    }

    /**
     * Check if the authenticated user can leave a review for the given artisan.
     * Condition : l'artisan doit avoir une candidature 'acceptee' sur une offre du recruteur,
     * et le recruteur ne doit pas avoir encore noté l'artisan pour cette offre.
     */
    public function canReview($artisanId)
    {
        $recruteurId = Auth::id();

        if (!$recruteurId) {
            return response()->json(['can_review' => false]);
        }

        // Chercher toutes les offres du recruteur où l'artisan a été accepté
        $offresAvecArtisan = Offre::where('user_id', $recruteurId)
            ->whereHas('candidatures', function ($query) use ($artisanId) {
                $query->where('user_id', $artisanId)
                      ->where('status', 'acceptee');
            })
            ->get();

        foreach ($offresAvecArtisan as $offre) {
            $existing = Review::where('offre_id', $offre->id)
                ->where('artisan_id', $artisanId)
                ->where('recruteur_id', $recruteurId)
                ->first();

            if (!$existing) {
                return response()->json([
                    'can_review' => true,
                    'can_edit'   => false,
                    'offre_id'   => $offre->id,
                ]);
            }
        }

        $existing = Review::where('artisan_id', $artisanId)
            ->where('recruteur_id', $recruteurId)
            ->first();

        if ($existing) {
            return response()->json([
                'can_review' => false,
                'can_edit'   => true,
                'review'     => $existing,
                'offre_id'   => $existing->offre_id,
            ]);
        }

        return response()->json(['can_review' => false, 'can_edit' => false]);
    }

    /**
     * Store a new review.
     */
    public function store(Request $request)
    {
        $request->validate([
            'offre_id'   => 'required|exists:offres,id',
            'artisan_id' => 'required|exists:users,id',
            'rating'     => 'required|integer|min:1|max:5',
            'comment'    => 'nullable|string',
        ]);

        $recruteurId = Auth::id();

        // Vérifier que l'offre appartient bien au recruteur connecté
        $offre = Offre::where('id', $request->offre_id)
            ->where('user_id', $recruteurId)
            ->first();

        if (!$offre) {
            return response()->json(['message' => 'Offre introuvable ou non autorisée.'], 403);
        }

        // Vérifier que l'artisan a bien été accepté pour cette offre
        $candidature = Candidature::where('offre_id', $request->offre_id)
            ->where('user_id', $request->artisan_id)
            ->where('status', 'acceptee')
            ->first();

        if (!$candidature) {
            return response()->json(['message' => "Cet artisan n'a pas été accepté pour cette offre."], 403);
        }

        // Empêcher les doublons d'avis
        $dejaNote = Review::where('offre_id', $request->offre_id)
            ->where('artisan_id', $request->artisan_id)
            ->where('recruteur_id', $recruteurId)
            ->exists();

        if ($dejaNote) {
            return response()->json(['message' => 'Vous avez déjà évalué cet artisan pour cette mission.'], 409);
        }

        $review = Review::create([
            'offre_id'    => $request->offre_id,
            'recruteur_id' => $recruteurId,
            'artisan_id'  => $request->artisan_id,
            'rating'      => $request->rating,
            'comment'     => $request->comment,
        ]);

        return response()->json($review, 201);
    }

    /**
     * Update an existing review (recruiter only, own review).
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'rating'  => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string',
        ]);

        $review = Review::findOrFail($id);

        if ($review->recruteur_id !== Auth::id()) {
            return response()->json(['message' => 'Non autorisé.'], 403);
        }

        $review->update([
            'rating'  => $request->rating,
            'comment' => $request->comment,
        ]);

        return response()->json($review);
    }
}
