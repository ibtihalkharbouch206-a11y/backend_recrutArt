<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Carbon\Carbon;
use App\Mail\ResetPasswordMail;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $request->validate([
            'nom'      => 'required',
            'email'    => 'required|email|unique:users',
            // A7: enforce strong password — min 8, upper+lower, digit, symbol
            'password' => ['required', Password::min(8)->mixedCase()->numbers()->symbols()],
            'role'     => 'required|in:artisan,recruteur',
        ]);

        $user = new User([
            'nom'      => $request->nom,
            'email'    => $request->email,
            'password' => Hash::make($request->password),
        ]);
        $user->role = $request->role;
        $user->save();

        // Automatically create user profile
        if ($user->role === 'recruteur') {
            \App\Models\Profil::create([
                'user_id'          => $user->id,
                'nom_entreprise'   => $request->nom_entreprise,
                'metier'           => $request->secteur_activite ?? 'Non spécifié',
                'email_entreprise' => $request->email_entreprise,
                'telephone'        => $request->telephone,
                'adresse'          => $request->adresse,
                'site_web'         => $request->site_web,
                'effectif'         => $request->effectif,
                'description'      => $request->description,
                'ville'            => 'Non spécifié',
                'experience'       => 0,
            ]);
        } else {
            \App\Models\Profil::create([
                'user_id'    => $user->id,
                'metier'     => $request->specialite ?? 'Non spécifié',
                'ville'      => 'Non spécifié',
                'experience' => 0,
            ]);
        }

        // A9: log successful registration
        Log::info('AUTH_REGISTER', [
            'user_id' => $user->id,
            'email'   => $user->email,
            'ip'      => $request->ip(),
        ]);

        $token = \App\Services\JwtService::encode([
            'sub'   => $user->id,
            'email' => $user->email,
            'role'  => $user->role,
        ]);

        return response()->json([
            'user'  => $user,
            'token' => $token,
        ]);
    }

    public function login(Request $request)
    {
        $request->validate([
            'email'    => 'required',
            'password' => 'required',
        ]);

        $user = User::where('email', $request->email)->first();
        $passwordValid = false;

        if ($user) {
            $passwordValid = Hash::check($request->password, $user->password);
        }

        if (!$user || !$passwordValid) {
            // A9: log failed login attempt with IP for monitoring
            Log::warning('AUTH_LOGIN_FAILED', [
                'email' => $request->email,
                'ip'    => $request->ip(),
            ]);

            return response()->json(['message' => 'Identifiants incorrects.'], 401);
        }

        // A9: log successful login
        Log::info('AUTH_LOGIN_SUCCESS', [
            'user_id' => $user->id,
            'email'   => $user->email,
            'ip'      => $request->ip(),
        ]);

        $token = \App\Services\JwtService::encode([
            'sub'   => $user->id,
            'email' => $user->email,
            'role'  => $user->role,
        ]);

        return response()->json([
            'user'  => $user,
            'token' => $token,
        ]);
    }

    public function logout(Request $request)
    {
        $header = $request->header('Authorization');
        if ($header && str_starts_with($header, 'Bearer ')) {
            $token = substr($header, 7);
            $payload = \App\Services\JwtService::decode($token);
            if ($payload && isset($payload['jti']) && isset($payload['exp'])) {
                \App\Services\TokenBlacklistService::blacklist($payload['jti'], $payload['exp']);
            }
        }

        return response()->json([
            'message' => 'Logged out',
        ]);
    }

    public function forgotPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
        ], [
            'email.required' => 'Veuillez saisir votre adresse email.',
            'email.email'    => 'Adresse email invalide.',
        ]);

        $user = User::where('email', $request->email)->first();

        if ($user) {
            $token = Str::random(60);

            DB::table('password_reset_tokens')->updateOrInsert(
                ['email' => $request->email],
                [
                    'email'      => $request->email,
                    'token'      => Hash::make($token),
                    'created_at' => Carbon::now(),
                ]
            );

            try {
                Mail::to($request->email)->send(new ResetPasswordMail($token, $request->email));
            } catch (\Exception $e) {
                Log::error('PASSWORD_RESET_MAIL_FAILED', [
                    'email'   => $request->email,
                    'message' => $e->getMessage(),
                ]);

                return response()->json([
                    'message' => 'Le lien de réinitialisation a été créé mais l\'email n\'a pas pu être envoyé. Vérifiez la configuration mail du serveur (fichier .env).',
                ], 503);
            }
        }

        return response()->json([
            'message' => 'Si cette adresse est enregistrée, vous recevrez un email avec les instructions de réinitialisation.',
        ]);
    }

    public function resetPassword(Request $request)
    {
        $request->validate([
            'email'    => 'required|email',
            'token'    => 'required',
            // A7: reset must also meet the strong password policy
            'password' => ['required', 'confirmed', Password::min(8)->mixedCase()->numbers()->symbols()],
        ], [
            'password.confirmed' => 'Les mots de passe ne correspondent pas.',
        ]);

        $resetRecord = DB::table('password_reset_tokens')->where('email', $request->email)->first();

        if (!$resetRecord || !Hash::check($request->token, $resetRecord->token)) {
            return response()->json(['message' => 'Lien de réinitialisation invalide.'], 400);
        }

        // A07: check token expiration (60 minutes)
        if (Carbon::parse($resetRecord->created_at)->addMinutes(60)->isPast()) {
            DB::table('password_reset_tokens')->where('email', $request->email)->delete();
            return response()->json(['message' => 'Lien de réinitialisation expiré.'], 400);
        }

        $user = User::where('email', $request->email)->first();
        $user->password = Hash::make($request->password);
        $user->save();

        DB::table('password_reset_tokens')->where('email', $request->email)->delete();

        // A9: log password reset
        Log::info('AUTH_PASSWORD_RESET', [
            'user_id' => $user->id,
            'email'   => $user->email,
            'ip'      => $request->ip(),
        ]);

        return response()->json(['message' => 'Mot de passe réinitialisé avec succès.']);
    }
}