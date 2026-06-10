<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('profils', function (Blueprint $table) {
            $table->string('nom_entreprise')->nullable()->after('user_id');
            $table->string('email_entreprise')->nullable()->after('nom_entreprise');
            $table->string('adresse')->nullable()->after('telephone');
            $table->string('site_web')->nullable()->after('adresse');
            $table->string('effectif')->nullable()->after('site_web');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('profils', function (Blueprint $table) {
            $table->dropColumn(['nom_entreprise', 'email_entreprise', 'adresse', 'site_web', 'effectif']);
        });
    }
};
