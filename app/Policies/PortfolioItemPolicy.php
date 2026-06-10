<?php

namespace App\Policies;

use App\Models\PortfolioItem;
use App\Models\User;

class PortfolioItemPolicy
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
     * Only the owner can delete their portfolio item.
     */
    public function delete(User $user, PortfolioItem $portfolioItem): bool
    {
        return $user->id === $portfolioItem->user_id;
    }
}
