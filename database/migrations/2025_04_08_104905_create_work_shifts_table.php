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
        Schema::create('work_shifts', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Shift name
            $table->date('start_date'); // Start date of the shift
            $table->date('end_date'); // End date of the shift
            $table->string('shift_type')->nullable();
            // Regular shift fields
            $table->time('start_time')->nullable(); // Start time for regular shift
            $table->time('end_time')->nullable(); // End time for regular shift

            // Weekend selection (off days)
            $table->boolean('is_sunday_off')->default(false);
            $table->boolean('is_monday_off')->default(false);
            $table->boolean('is_tuesday_off')->default(false);
            $table->boolean('is_wednesday_off')->default(false);
            $table->boolean('is_thursday_off')->default(false);
            $table->boolean('is_friday_off')->default(false);
            $table->boolean('is_saturday_off')->default(false);

            // Scheduled shift fields (start and end times for each day)
            $table->time('sunday_start_time')->nullable();
            $table->time('sunday_end_time')->nullable();
            $table->time('monday_start_time')->nullable();
            $table->time('monday_end_time')->nullable();
            $table->time('tuesday_start_time')->nullable();
            $table->time('tuesday_end_time')->nullable();
            $table->time('wednesday_start_time')->nullable();
            $table->time('wednesday_end_time')->nullable();
            $table->time('thursday_start_time')->nullable();
            $table->time('thursday_end_time')->nullable();
            $table->time('friday_start_time')->nullable();
            $table->time('friday_end_time')->nullable();
            $table->time('saturday_start_time')->nullable();
            $table->time('saturday_end_time')->nullable();

            // Break time and description
            $table->json('break_time')->nullable(); // Break time (e.g., duration or description)
            $table->text('description')->nullable(); // Description for the shift

            // Department and employee fields
            $table->string('department')->nullable(); // Department name
            $table->json('employee')->nullable(); // Employee ID (foreign key)

            // Created by field (to track which user created the shift)
            $table->unsignedBigInteger('created_by'); // User ID who created the shift (foreign key)

            // Foreign key constraints (you can modify these according to your user and employee table)
           
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('work_shifts');
    }
};
