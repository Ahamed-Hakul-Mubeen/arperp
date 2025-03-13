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
        Schema::create('advances', function (Blueprint $table) {
            $table->bigIncrements("id");
            $table->integer('advance_id');
            $table->date('date');
            $table->decimal('amount', 16, 2)->default('0.0');
            $table->decimal('balance', 16, 2)->default('0.0');
            $table->integer('account_id');
            $table->integer('customer_id');
            $table->integer('payment_method');
            $table->integer('status')->default('0');
            $table->string('reference')->nullable();
            $table->string('add_receipt')->nullable();
            $table->text('description')->nullable();
            $table->string('currency_code')->nullable()->default(null);
            $table->string('currency_symbol')->nullable()->default(null);
            $table->double('exchange_rate')->default(0);
            $table->integer('created_by')->default('0');
            $table->unsignedBigInteger('created_user');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('advances');
    }
};
