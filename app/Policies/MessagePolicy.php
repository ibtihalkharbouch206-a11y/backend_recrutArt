<?php

namespace App\Policies;

use App\Models\Message;
use App\Models\User;

class MessagePolicy
{
    /**
     * Admins bypass all policy checks.
     */
    public function before(User $user, string $ability): bool|null
    {
        if (strtolower((string) $user->role) === 'admin') {
            return true;
        }
        return null;
    }

    /**
     * Only the sender or receiver can view a message.
     */
    public function view(User $user, Message $message): bool
    {
        return $user->id === $message->sender_id
            || $user->id === $message->receiver_id;
    }
}
