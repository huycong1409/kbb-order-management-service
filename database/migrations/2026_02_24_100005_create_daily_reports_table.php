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
        Schema::create('daily_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')->constrained()->cascadeOnDelete();
            $table->date('report_date');

            // ADS - nhập tay, format ₫324.431 → auto convert → 324431 * 1.08
            $table->string('ads_raw_input')->nullable();           // Giá trị nhập thô (₫324.431)
            $table->decimal('ads_fee', 15, 2)->default(0);        // ADS sau convert * 1.08
            $table->decimal('ads_refund', 15, 2)->default(0);     // Hoàn ADS (nhập tay)
            // ads_cost = ads_fee - ads_refund (computed)
            // profit_before_ads = SUM order profits on this date (computed from orders)
            // daily_profit = profit_before_ads - ads_cost (computed)

            $table->timestamps();

            $table->unique(['shop_id', 'report_date']);
            $table->index(['shop_id', 'report_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('daily_reports');
    }
};
