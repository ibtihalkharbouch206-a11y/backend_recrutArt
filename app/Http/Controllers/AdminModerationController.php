<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Models\Offre;
use App\Models\PortfolioItem;
use App\Models\Message;
use App\Models\User;
use App\Models\Candidature;

class AdminModerationController extends Controller
{
    public function getPendingContent()
    {
        $offres     = Offre::where('status', 'pending')->with('user')->get();
        $portfolios = PortfolioItem::where('status', 'pending')->with('user')->get();
        // Exclude messages sent by admin accounts (internal notifications)
        $messages   = Message::where('status', 'pending')
            ->whereHas('sender', function ($q) {
                $q->where('role', '!=', 'admin');
            })
            ->with('sender', 'receiver')
            ->get();

        return response()->json([
            'offres'     => $offres,
            'portfolios' => $portfolios,
            'messages'   => $messages,
        ]);
    }

    public function moderateOffre(Request $request, $id)
    {
        $request->validate(['status' => 'required|in:approved,rejected']);
        $offre = Offre::findOrFail($id);
        $offre->update(['status' => $request->status]);

        // A9: audit log every admin moderation action
        Log::info('ADMIN_MODERATE_OFFRE', [
            'admin_id' => Auth::id(),
            'offre_id' => $id,
            'status'   => $request->status,
        ]);

        return response()->json(['message' => 'Offre updated']);
    }

    public function moderatePortfolio(Request $request, $id)
    {
        $request->validate(['status' => 'required|in:approved,rejected']);
        $item = PortfolioItem::findOrFail($id);
        $item->update(['status' => $request->status]);

        // A9: audit log
        Log::info('ADMIN_MODERATE_PORTFOLIO', [
            'admin_id'  => Auth::id(),
            'item_id'   => $id,
            'status'    => $request->status,
        ]);

        return response()->json(['message' => 'Portfolio item updated']);
    }

    public function moderateMessage(Request $request, $id)
    {
        $request->validate(['status' => 'required|in:approved,rejected']);
        $msg = Message::findOrFail($id);
        $msg->update(['status' => $request->status]);

        // A9: audit log
        Log::info('ADMIN_MODERATE_MESSAGE', [
            'admin_id'   => Auth::id(),
            'message_id' => $id,
            'status'     => $request->status,
        ]);

        return response()->json(['message' => 'Message updated']);
    }

    public function getUsers()
    {
        $users = User::where('role', '!=', 'admin')->with('profil')->get();
        return response()->json($users);
    }

    public function deleteUser($id)
    {
        $user = User::findOrFail($id);
        
        // Prevent deletion of admin accounts
        if ($user->role === 'admin') {
            return response()->json(['error' => 'Cannot delete admin accounts'], 403);
        }

        // A9: audit log
        Log::info('ADMIN_DELETE_USER', [
            'admin_id' => Auth::id(),
            'user_id'  => $id,
            'email'    => $user->email,
        ]);

        $user->delete();
        return response()->json(['message' => 'User deleted successfully']);
    }

    public function getStats()
    {
        $totalUsers = User::where('role', '!=', 'admin')->count();
        $totalCompanies = User::where('role', 'recruiter')->count();
        $totalOffers = Offre::where('status', 'approved')->count();
        $totalCandidatures = Candidature::count();
        $totalHires = Candidature::where('status', 'acceptee')->count();

        // Métiers les plus demandés (grouped by title or calculated)
        $topJobsRaw = Offre::select('titre', \Illuminate\Support\Facades\DB::raw('count(*) as count'))
            ->groupBy('titre')
            ->orderByDesc('count')
            ->limit(5)
            ->get();

        $topJobs = [];
        $predefinedJobs = ['Plombier', 'Électricien', 'Maçon', 'Menuisier', 'Mécanicien'];
        $predefinedCounts = [89, 76, 65, 54, 43];
        
        if ($topJobsRaw->count() > 0) {
            foreach ($topJobsRaw as $job) {
                $topJobs[] = [
                    'name' => $job->titre,
                    'count' => $job->count
                ];
            }
        } else {
            for ($i = 0; $i < count($predefinedJobs); $i++) {
                $topJobs[] = [
                    'name' => $predefinedJobs[$i],
                    'count' => $predefinedCounts[$i]
                ];
            }
        }

        // Recent activity
        $activities = [];

        // Users
        $recentUsers = User::where('role', '!=', 'admin')->latest()->limit(5)->get();
        foreach ($recentUsers as $u) {
            $activities[] = [
                'type' => 'user',
                'title' => $u->role === 'recruiter' 
                    ? "Nouvelle entreprise : {$u->nom}" 
                    : "Nouvel utilisateur inscrit : {$u->nom}",
                'created_at' => $u->created_at->toIso8601String()
            ];
        }

        // Offers
        $recentOffers = Offre::latest()->limit(5)->get();
        foreach ($recentOffers as $o) {
            $activities[] = [
                'type' => 'offer',
                'title' => "Offre publiée : {$o->titre}",
                'created_at' => $o->created_at->toIso8601String()
            ];
        }

        // Candidatures
        $recentCandidatures = Candidature::with('user', 'offre')->latest()->limit(5)->get();
        foreach ($recentCandidatures as $c) {
            $activities[] = [
                'type' => 'candidature',
                'title' => $c->user 
                    ? "Nouvelle candidature de {$c->user->nom} pour {$c->offre?->titre}" 
                    : "Nouvelle candidature reçue",
                'created_at' => $c->created_at->toIso8601String()
            ];
        }

        // Sort activities by created_at desc
        usort($activities, function ($a, $b) {
            return strcmp($b['created_at'], $a['created_at']);
        });

        $activities = array_slice($activities, 0, 6);

        return response()->json([
            'total_users' => $totalUsers,
            'total_companies' => $totalCompanies,
            'total_offers' => $totalOffers,
            'total_candidatures' => $totalCandidatures,
            'total_hires' => $totalHires,
            'top_jobs' => $topJobs,
            'recent_activity' => $activities
        ]);
    }

    public function getUser($id)
    {
        $user = User::with('profil')->findOrFail($id);
        return response()->json($user);
    }

    public function sendEmailToUser(Request $request, $id)
    {
        $request->validate([
            'subject' => 'required|string|max:255',
            'body' => 'required|string|max:5000',
        ]);

        $user = User::findOrFail($id);

        try {
            \Illuminate\Support\Facades\Mail::raw($request->body, function ($msg) use ($user, $request) {
                $msg->to($user->email)
                    ->subject($request->subject);
            });

            Log::info('ADMIN_SEND_EMAIL', [
                'admin_id' => Auth::id(),
                'user_id' => $id,
                'subject' => $request->subject,
            ]);

            return response()->json(['message' => 'Email envoyé']);
        } catch (\Exception $e) {
            Log::error('ADMIN_SEND_EMAIL_ERROR', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Erreur envoi email'], 500);
        }
    }

    public function getCompanies()
    {
        $companies = User::where('role', 'recruteur')
            ->with('profil')
            ->withCount('offres')
            ->get();

        return response()->json($companies);
    }

    public function getCandidatures()
    {
        return response()->json(
            Candidature::with(['user.profil', 'offre.user'])
                ->latest()
                ->get()
        );
    }

    public function deleteCandidature($id)
    {
        $c = Candidature::findOrFail($id);

        // Audit
        Log::info('ADMIN_DELETE_CANDIDATURE', [
            'admin_id' => Auth::id(),
            'candidature_id' => $id,
            'user_id' => $c->user_id,
            'offre_id' => $c->offre_id,
        ]);

        // Optionally delete related conversation
        \App\Models\Conversation::where('offre_id', $c->offre_id)
            ->where('artisan_id', $c->user_id)
            ->delete();

        $c->delete();

        return response()->json(['message' => 'Candidature supprimée']);
    }
}

