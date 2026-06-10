<?php

namespace App\Policies;

use App\Models\Offre;
use App\Models\User;

class OffrePolicy
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
     * Any authenticated user can view any single offre.
     */
    public function view(User $user, Offre $offre): bool
    {
        return true;
    }

    /**
     * Only the owner can update an offre.
     */
    public function update(User $user, Offre $offre): bool
    {
        return $user->id === $offre->user_id;
    }

    /**
     * Only the owner can delete an offre.
     */
    public function delete(User $user, Offre $offre): bool
    {
        return $user->id === $offre->user_id;
    }

    /**
     * Only the owner can mark an offre as completed.
     */
    public function markAsCompleted(User $user, Offre $offre): bool
    {
        return $user->id === $offre->user_id;
    }
}
