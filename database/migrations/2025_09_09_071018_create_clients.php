<?php

use App\Classes\Consts;
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
        Schema::create('clients', function (Blueprint $table) {
            $table->comment("La liste des clients.");
            $table->uuid('id')->primary();

            $table->string('phone')->unique()->comment("Numero de telephone du client.");
            $table->string('name')->nullable()->comment("Nom du client.");
            $table->string('c_number')->comment("Numero du compteur.");
            $table->string('address')->nullable()->comment("Adresse du client | avenue");
            $table->enum('provider', Consts::PROVIDERS)->default('AIRTEL')->comment("Fournisseur de service.");
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('clients');
    }
};
