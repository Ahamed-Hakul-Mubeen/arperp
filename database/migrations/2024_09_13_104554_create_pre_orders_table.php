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
        Schema::create('pre_orders', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('pre_order_id');
            $table->unsignedBigInteger('vender_id');
            $table->date('issue_date');
            $table->date('send_date')->nullable();
            $table->integer('category_id');
            $table->integer('status')->default('0');
            $table->integer('discount_apply')->default('0');
            $table->integer('is_convert')->default('0');
            $table->integer('converted_bill_id')->default('0');
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
        Schema::dropIfExists('pre_orders');
    }
};
