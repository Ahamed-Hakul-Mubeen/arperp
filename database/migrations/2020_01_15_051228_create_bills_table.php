<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBillsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(
            'bills', function (Blueprint $table){
            $table->bigIncrements('id');
            $table->string('bill_id')->default('0');
            $table->string('project_id')->nullable()->default(null);
            $table->string('actual_bill_number')->nullable()->default(null);
            $table->integer('vender_id');
            $table->date('bill_date');
            $table->date('due_date');
            $table->integer('order_number')->default('0');
            $table->integer('status')->default('0');
            $table->string('type')->nullable();
            $table->string('user_type')->nullable();
            $table->integer('shipping_display')->default('1');
            $table->date('send_date')->nullable();
            $table->integer('discount_apply')->default('0');
            $table->integer('category_id');
            $table->unsignedBigInteger('created_user');
            $table->integer('created_by')->default('0');
            $table->timestamps();
        }
        );
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('bills');
    }
}
