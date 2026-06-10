<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ResetPasswordMail extends Mailable
{
    use Queueable, SerializesModels;

    public $token;
    public $email;

    public function __construct($token, $email)
    {
        $this->token = $token;
        $this->email = $email;
    }

    public function build()
    {
        // Using an environment variable or config for the frontend URL is best practice,
        // but hardcoding to React's default port 3000 or reading from APP_URL for simplicity here.
        // Assuming frontend is running on http://localhost:3000
        $frontendUrl = env('FRONTEND_URL', 'http://localhost:3001');
        $resetUrl = $frontendUrl . '/reset-password?token=' . $this->token . '&email=' . urlencode($this->email);

        return $this->subject('Réinitialisation de votre mot de passe')
                    ->view('emails.reset_password')
                    ->with(['resetUrl' => $resetUrl]);
    }
}
