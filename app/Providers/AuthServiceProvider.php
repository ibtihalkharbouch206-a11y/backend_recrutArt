<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use App\Models\Offre;
use App\Models\PortfolioItem;
use App\Models\Message;
use App\Policies\OffrePolicy;
use App\Policies\PortfolioItemPolicy;
use App\Policies\MessagePolicy;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        Offre::class         => OffrePolicy::class,
        PortfolioItem::class => PortfolioItemPolicy::class,
        Message::class       => MessagePolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        $this->registerPolicies();
    }
}
