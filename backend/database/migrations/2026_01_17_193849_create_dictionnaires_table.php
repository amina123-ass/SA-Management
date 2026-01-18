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
        // État dossier
        Schema::create('etat_dossiers', function (Blueprint $table) {
            $table->id();
            $table->string('libelle');
            $table->string('code')->unique();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Nature de don
        Schema::create('nature_dons', function (Blueprint $table) {
            $table->id();
            $table->string('libelle');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Type assistance
        Schema::create('type_assistances', function (Blueprint $table) {
            $table->id();
            $table->string('libelle');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Détails type assistance
        Schema::create('detail_type_assistances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('type_assistance_id')->constrained()->onDelete('cascade');
            $table->string('libelle');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // État don
        Schema::create('etat_dons', function (Blueprint $table) {
            $table->id();
            $table->string('libelle');
            $table->string('code')->unique();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Commune
        Schema::create('communes', function (Blueprint $table) {
            $table->id();
            $table->string('nom');
            $table->string('code')->unique();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Milieu
        Schema::create('milieux', function (Blueprint $table) {
            $table->id();
            $table->string('libelle');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Questions de sécurité
        
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('milieux');
        Schema::dropIfExists('communes');
        Schema::dropIfExists('etat_dons');
        Schema::dropIfExists('detail_type_assistances');
        Schema::dropIfExists('type_assistances');
        Schema::dropIfExists('nature_dons');
        Schema::dropIfExists('etat_dossiers');
    }
};