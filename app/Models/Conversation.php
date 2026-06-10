<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Conversation extends Model
{
    use HasFactory;

    protected $fillable = ['offre_id', 'recruteur_id', 'artisan_id'];

    public function offre()
    {
        return $this->belongsTo(Offre::class);
    }

    public function recruteur()
    {
        return $this->belongsTo(User::class, 'recruteur_id');
    }

    public function artisan()
    {
        return $this->belongsTo(User::class, 'artisan_id');
    }

    public function messages()
    {
        return $this->hasMany(Message::class);
    }
}
