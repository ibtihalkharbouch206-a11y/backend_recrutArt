<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use Notifiable;

    protected $fillable = [
        'nom',
        'email',
        'password'
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];


    public function profil()
    {
        return $this->hasOne(Profil::class);
    }

    public function offres()
    {
        return $this->hasMany(Offre::class);
    }

    public function candidatures()
    {
        return $this->hasMany(Candidature::class);
    }

    public function portfolioItems()
    {
        return $this->hasMany(PortfolioItem::class);
    }
}