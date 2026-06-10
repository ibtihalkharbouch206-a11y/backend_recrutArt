<?php

namespace App\Mail;

use App\Models\Candidature;
use App\Models\Offre;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class CandidatureSent extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Candidature $candidature,
        public Offre $offre,
        public User $artisan
    ) {}

    public function build()
    {
        return $this->subject("Nouvelle candidature: {$this->offre->titre}")
            ->view('emails.candidature_sent');
    }
}

