<?php

use App\Models\BankAccount;
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
        Schema::create('transfer_history', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('amount');
            $table->string('receivers_name');
            $table->string('receivers_account_number');
            $table->foreignIdFor(BankAccount::class, 'bank_account_id');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transfer_history');
    }
};
