<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Profil extends Model
{
    protected $fillable = [
        'user_id',
        'nom_entreprise',
        'email_entreprise',
        'metier',
        'ville',
        'telephone',
        'adresse',
        'site_web',
        'effectif',
        'experience',
        'competences',
        'description',
        'photo_path',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}