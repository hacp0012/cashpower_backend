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
        Schema::create('purchases', function (Blueprint $table) {
            $table->comment("Liste des achats d'electricite.");
            $table->id();

            $table->enum('state', ['INWAIT', 'INWAIT_PAIEMENT', 'FAILED', 'SUCCESS'])->default('INWAIT');
            $table->float('amount')->comment("Montant");
            $table->enum('currency', ['USD', 'CDF'])->default('CDF')->comment("Devise");
            $table->enum('provider', Consts::PROVIDERS)->default('AIRTEL')->comment("Fournisseur de service de paiement");
            $table->string('phone')->comment("Numero de telephone soit du client.");
            $table->string('c_number')->comment("Numero du compteur");
            $table->string('buyer')->comment("Numero du client | de l'acheteur.");
            $table->string('key_code')->nullable()->comment("Cle de credit payee.");
            $table->json('response')->nullable()->comment("Reponse du fournisseur de service apres requette.");
            $table->json('request')->nullable()->comment("Requette effectue depuis l'api Whatsapp.");
            $table->string('transaction_ref')->nullable()->comment("Reference de la transaction.");
            
            $table->timestamp('expire_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchases');
    }
};
