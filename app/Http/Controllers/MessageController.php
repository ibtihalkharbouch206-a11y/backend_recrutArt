<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Message;
use Illuminate\Support\Facades\Auth;

class MessageController extends Controller
{
    public function index(Request $request)
    {
        $userId = Auth::id();

        $query = Message::with(['sender', 'receiver']);

        $query->where(function ($q) use ($userId) {
            $q->where('sender_id', $userId)
              ->orWhere('receiver_id', $userId);
        });

        return response()->json($query->oldest()->get()->map(function($msg) {
            return [
                'id' => $msg->id,
                'sender_name' => $msg->sender ? $msg->sender->nom : 'Utilisateur',
                'user_id' => $msg->sender_id,
                'text' => $msg->text,
                'status' => $msg->status,
                'created_at' => $msg->created_at
            ];
        }));
    }

    public function store(Request $request)
    {
        $request->validate([
            'text' => 'required|string|max:1000',
            'receiver_id' => 'nullable|exists:users,id'
        ]);

        $message = Message::create([
            'sender_id' => Auth::id(),
            'receiver_id' => $request->receiver_id,
            'text' => $request->text,
            'status' => 'pending'
        ]);

        $message->load('sender');

        return response()->json([
            'id' => $message->id,
            'sender_name' => $message->sender ? $message->sender->nom : 'Utilisateur',
            'user_id' => $message->sender_id,
            'text' => $message->text,
            'status' => $message->status,
            'created_at' => $message->created_at
        ], 201);
    }

    public function contactAdmin(Request $request)
    {
        $request->validate([
            'message' => 'required|string|max:5000'
        ]);

        $user = Auth::user();
        $text = $request->input('message');

        // Create a message in the database for the admin
        $admin = \App\Models\User::where('role', 'admin')->first();
        if ($admin) {
            \App\Models\Message::create([
                'sender_id' => $user->id,
                'receiver_id' => $admin->id,
                'text' => $text,
                'status' => 'pending'
            ]);
        }

        // Try to send email to admin, but don't fail if SMTP is not configured
        try {
            $safeName = str_replace(["\r", "\n"], '', $user->nom);
            \Illuminate\Support\Facades\Mail::raw(
                "Message de la part de l'utilisateur : {$safeName} ({$user->email})\n\n" .
                "Rôle : {$user->role}\n\n" .
                "Message :\n{$text}",
                function ($mail) use ($safeName, $user) {
                    $mail->to('admin@artisanrecruit.com')
                         ->subject("RecruART - Message pour l'administrateur de {$safeName}");
                }
            );
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Erreur envoi email admin: " . $e->getMessage());
        }

        return response()->json(['message' => 'Message envoyé à l\'administrateur avec succès.']);
    }
}
