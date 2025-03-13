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
        Schema::create('asset_transfers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('asset_id');
            $table->unsignedBigInteger('from_employee_id');
            $table->unsignedBigInteger('to_employee_id');
            $table->date('transfer_date');
            $table->unsignedBigInteger('created_by');
            $table->foreign('asset_id')->references('id')->on('asset_management')->onDelete('cascade');
            $table->foreign('from_employee_id')->references('id')->on('employees')->onDelete('cascade');
            $table->foreign('to_employee_id')->references('id')->on('employees')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('asset_transfers');
    }
};
