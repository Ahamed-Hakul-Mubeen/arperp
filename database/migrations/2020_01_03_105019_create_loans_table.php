<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateLoansTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('loans', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->integer('employee_id');
            $table->integer('loan_option');
            $table->string('title');
            $table->decimal('amount',15,2)->default('0.0');
            $table->string('type')->nullable();
            $table->date('start_date');
            $table->date('end_date');
            $table->integer('no_of_months')->nullable();
            $table->integer('pending_months')->nullable();
            $table->string('last_emi')->nullable();
            $table->string('reason');
            $table->integer('created_by');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('loans');
    }
}
