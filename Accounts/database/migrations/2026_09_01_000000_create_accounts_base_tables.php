<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('app_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value');
            $table->string('type')->default('string');
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('processed_events', function (Blueprint $table) {
            $table->id();
            $table->uuid('event_id')->unique();
            $table->string('topic', 60);
            $table->timestamp('processed_at');
            $table->timestamps();
        });

        Schema::create('ext_orders', function (Blueprint $table) {
            $table->unsignedBigInteger('id')->primary();
            $table->string('order_number')->nullable();
            $table->string('status', 40)->nullable();
            $table->string('invoice_type', 40)->nullable();
            $table->string('invoice_number')->nullable();
            $table->date('invoice_date')->nullable();
            $table->string('place_of_supply')->nullable();
            $table->string('supplier_gstin', 20)->nullable();
            $table->decimal('subtotal', 14, 2)->default(0);
            $table->decimal('delivery_fee', 14, 2)->default(0);
            $table->decimal('platform_fee', 14, 2)->default(0);
            $table->decimal('total', 14, 2)->default(0);
            $table->unsignedBigInteger('restaurant_id')->nullable();
            $table->unsignedBigInteger('branch_id')->nullable();
            $table->json('tax_breakdown')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->index('created_at');
        });

        Schema::create('ext_payouts', function (Blueprint $table) {
            $table->unsignedBigInteger('id')->primary();
            $table->string('uuid')->nullable();
            $table->unsignedBigInteger('restaurant_id')->nullable();
            $table->unsignedBigInteger('driver_id')->nullable();
            $table->string('restaurant_name')->nullable();
            $table->string('driver_name')->nullable();
            $table->decimal('gross_amount', 14, 2)->default(0);
            $table->decimal('platform_commission', 14, 2)->default(0);
            $table->decimal('gst_on_commission', 14, 2)->default(0);
            $table->decimal('payment_gateway_fee', 14, 2)->default(0);
            $table->decimal('pre_tax_amount', 14, 2)->default(0);
            $table->decimal('tds_amount', 14, 2)->default(0);
            $table->string('tds_section', 20)->nullable();
            $table->decimal('tcs_amount', 14, 2)->default(0);
            $table->decimal('net_amount', 14, 2)->default(0);
            $table->string('status', 30)->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ext_payouts');
        Schema::dropIfExists('ext_orders');
        Schema::dropIfExists('processed_events');
        Schema::dropIfExists('app_settings');
    }
};
