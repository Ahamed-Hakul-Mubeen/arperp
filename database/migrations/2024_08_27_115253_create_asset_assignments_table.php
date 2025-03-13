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
        Schema::create('asset_assignments', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('asset_id')->unsigned();
            $table->bigInteger('employee_id')->unsigned();
            $table->date('assigned_date');
            $table->enum('status', ['assigned', 'unassigned'])->default('assigned');
            $table->bigInteger('created_by')->unsigned();
            $table->foreign('asset_id')->references('id')->on('asset_management')->onDelete('cascade');
            $table->foreign('employee_id')->references('id')->on('employees')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('asset_assignments');
    }
};
