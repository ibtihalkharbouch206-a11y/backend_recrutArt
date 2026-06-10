<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ProfilController;
use App\Http\Controllers\OffreController;
use App\Http\Controllers\CandidatureController;
use App\Http\Controllers\PortfolioController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\AdminModerationController;
use App\Http\Controllers\ReviewController;


Route::middleware('throttle:6,1')->group(function () {
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
    Route::post('/reset-password', [AuthController::class, 'resetPassword']);
});

Route::middleware('jwt.auth')->group(function () {

    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/profil', [ProfilController::class, 'show']);
    Route::get('/profil/{id}', [ProfilController::class, 'showPublic']);
    Route::post('/profil', [ProfilController::class, 'storeOrUpdate']);
    Route::post('/profil/photo', [ProfilController::class, 'uploadPhoto']);
    Route::get('/offres', [OffreController::class, 'index']);
    Route::get('/offres/suggestions', [OffreController::class, 'suggestions']);
    Route::post('/offres', [OffreController::class, 'store'])->middleware('role:recruteur');
    Route::get('/offres/{id}', [OffreController::class, 'show']);
    Route::put('/offres/{id}', [OffreController::class, 'update']);
    Route::delete('/offres/{id}', [OffreController::class, 'destroy']);
    Route::post('/candidatures', [CandidatureController::class, 'store'])->middleware('role:artisan');
    Route::get('/candidatures', [CandidatureController::class, 'myCandidatures']);
    Route::get('/candidatures-recues', [CandidatureController::class, 'candidaturesRecues']);
    Route::get('/candidatures-recues/stats', [CandidatureController::class, 'getStats']);
    Route::put('/candidatures/{id}/status', [CandidatureController::class, 'updateStatus']);
    Route::get('/candidatures/offre/{offre_id}', [CandidatureController::class, 'byOffre']);
    Route::get('/portfolio', [PortfolioController::class, 'index']);
    Route::get('/portfolio/public/{userId}', [PortfolioController::class, 'publicPortfolio']);
    Route::post('/portfolio', [PortfolioController::class, 'store']);
    Route::delete('/portfolio/{id}', [PortfolioController::class, 'destroy']);
    Route::get('/messages', [MessageController::class, 'index']);
    Route::get('/messages/{contactId}', [MessageController::class, 'index']);
    Route::post('/messages', [MessageController::class, 'store']);
    Route::post('/contact-admin', [MessageController::class, 'contactAdmin']);

    // Admin Moderation
    Route::middleware('is_admin')->group(function () {
        Route::get('/admin/stats', [AdminModerationController::class, 'getStats']);
        Route::get('/admin/companies', [AdminModerationController::class, 'getCompanies']);
        Route::get('/admin/candidatures', [AdminModerationController::class, 'getCandidatures']);
    Route::delete('/admin/candidatures/{id}', [AdminModerationController::class, 'deleteCandidature']);
        Route::get('/admin/pending', [AdminModerationController::class, 'getPendingContent']);
        Route::put('/admin/offres/{id}/moderate', [AdminModerationController::class, 'moderateOffre']);
        Route::put('/admin/portfolio/{id}/moderate', [AdminModerationController::class, 'moderatePortfolio']);
        Route::put('/admin/messages/{id}/moderate', [AdminModerationController::class, 'moderateMessage']);
        Route::get('/admin/users', [AdminModerationController::class, 'getUsers']);
        Route::get('/admin/users/{id}', [AdminModerationController::class, 'getUser']);
        Route::delete('/admin/users/{id}', [AdminModerationController::class, 'deleteUser']);
        Route::post('/admin/send-email/{id}', [AdminModerationController::class, 'sendEmailToUser']);
    });

    // Reviews and Completions
    Route::put('/offres/{id}/complete', [OffreController::class, 'markAsCompleted']);
    Route::get('/reviews/artisan/{id}', [ReviewController::class, 'artisanReviews']);
    Route::get('/reviews/can-review/{artisanId}', [ReviewController::class, 'canReview']);
    Route::post('/reviews', [ReviewController::class, 'store']);
    Route::put('/reviews/{id}', [ReviewController::class, 'update']);

    // Chat / Conversations
    Route::get('/conversations', [\App\Http\Controllers\ChatController::class, 'getConversations']);
    Route::get('/conversations/{id}/messages', [\App\Http\Controllers\ChatController::class, 'getMessages']);
    Route::post('/conversations/{id}/messages', [\App\Http\Controllers\ChatController::class, 'sendMessage']);

});
