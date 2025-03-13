<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateInvoicesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(
            'invoices', function (Blueprint $table){
            $table->bigIncrements('id');
            $table->unsignedBigInteger('invoice_id');
            $table->unsignedBigInteger('customer_id');
            $table->string('actual_invoice_number')->nullable()->default(null);;
            $table->date('issue_date');
            $table->date('due_date');
            $table->date('send_date')->nullable();
            $table->integer('category_id');
            $table->text('ref_number')->nullable();
            $table->integer('status')->default('0');
            $table->integer('shipping_display')->default('1');
            $table->integer('discount_apply')->default('0');
            $table->integer('created_by')->default('0');
            $table->unsignedBigInteger('created_user');
            $table->string('currency_code')->nullable()->default(null);
            $table->string('currency_symbol')->nullable()->default(null);
            $table->double('exchange_rate');
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
        Schema::dropIfExists('invoices');
    }
}
