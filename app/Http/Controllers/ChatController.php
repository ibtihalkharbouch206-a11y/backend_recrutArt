<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Support\Facades\Auth;

class ChatController extends Controller
{
    /**
     * Get all conversations for the authenticated user.
     */
    public function getConversations()
    {
        $userId = Auth::id();

        $conversations = Conversation::with(['offre', 'recruteur.profil', 'artisan.profil'])
            ->where(function($query) use ($userId) {
                $query->where('recruteur_id', $userId)
                      ->orWhere('artisan_id', $userId);
            })
            ->get()
            ->map(function ($conv) use ($userId) {
                $lastMessage = $conv->messages()->latest()->first();
                $unreadCount = $conv->messages()
                    ->where('sender_id', '!=', $userId)
                    ->where('is_read', false)
                    ->count();

                $otherUser = $conv->recruteur_id === $userId ? $conv->artisan : $conv->recruteur;

                return [
                    'id' => $conv->id,
                    'offre_titre' => $conv->offre ? ($conv->offre->titre ?: $conv->offre->domaine) : 'Offre supprimée',
                    'other_user' => [
                        'id' => $otherUser->id,
                        'nom' => $otherUser->nom,
                        'photo_url' => $otherUser->profil ? $otherUser->profil->photo_url : null,
                    ],
                    'last_message' => $lastMessage ? $lastMessage->text : 'Aucun message',
                    'last_message_time' => $lastMessage ? $lastMessage->created_at : $conv->created_at,
                    'unread_count' => $unreadCount,
                ];
            })
            ->sortByDesc('last_message_time')
            ->values();

        return response()->json($conversations);
    }

    /**
     * Get messages for a specific conversation.
     * Marks unread messages as read.
     */
    public function getMessages($id)
    {
        $userId = Auth::id();

        $conversation = Conversation::findOrFail($id);

        if ($conversation->recruteur_id !== $userId && $conversation->artisan_id !== $userId) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Mark messages as read
        Message::where('conversation_id', $id)
            ->where('sender_id', '!=', $userId)
            ->where('is_read', false)
            ->update(['is_read' => true]);

        $messages = $conversation->messages()
            ->with('sender')
            ->oldest()
            ->get()
            ->map(function ($msg) use ($userId) {
                return [
                    'id' => $msg->id,
                    'text' => $msg->text,
                    'sender_id' => $msg->sender_id,
                    'is_mine' => $msg->sender_id === $userId,
                    'is_read' => $msg->is_read,
                    'created_at' => $msg->created_at,
                ];
            });

        return response()->json($messages);
    }

    /**
     * Send a new message in a conversation.
     */
    public function sendMessage(Request $request, $id)
    {
        $request->validate([
            'text' => 'required|string|max:1000',
        ]);

        $userId = Auth::id();
        $conversation = Conversation::findOrFail($id);

        if ($conversation->recruteur_id !== $userId && $conversation->artisan_id !== $userId) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $receiverId = $conversation->recruteur_id === $userId ? $conversation->artisan_id : $conversation->recruteur_id;

        $message = Message::create([
            'conversation_id' => $id,
            'sender_id' => $userId,
            'receiver_id' => $receiverId,
            'text' => $request->text,
            'status' => 'approved', // Auto approve direct chat
            'is_read' => false,
        ]);

        return response()->json([
            'id' => $message->id,
            'text' => $message->text,
            'sender_id' => $message->sender_id,
            'is_mine' => true,
            'is_read' => $message->is_read,
            'created_at' => $message->created_at,
        ], 201);
    }
}
