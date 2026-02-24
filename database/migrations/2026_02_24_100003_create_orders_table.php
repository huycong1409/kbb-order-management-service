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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')->constrained()->cascadeOnDelete();
            $table->string('order_code');               // Mã đơn hàng
            $table->string('package_code')->nullable(); // Mã Kiện Hàng
            $table->dateTime('order_date');             // Ngày đặt hàng
            $table->string('status')->default('completed'); // Trạng thái đơn hàng

            // Phí tính 1 lần cho toàn đơn (dù có nhiều sản phẩm)
            $table->decimal('fixed_fee', 15, 2)->default(0);   // Phí cố định
            $table->decimal('service_fee', 15, 2)->default(0); // Phí Dịch Vụ
            $table->decimal('payment_fee', 15, 2)->default(0); // Phí thanh toán
            $table->decimal('pi_ship', 15, 2)->default(0);     // Phí vận chuyển

            // Thông tin giao hàng
            $table->string('tracking_number')->nullable();  // Mã vận đơn
            $table->string('shipping_carrier')->nullable(); // Đơn vị vận chuyển
            $table->string('payment_method')->nullable();   // Phương thức thanh toán
            $table->string('buyer_username')->nullable();   // Người mua
            $table->string('recipient_name')->nullable();   // Tên người nhận
            $table->string('phone')->nullable();            // Số điện thoại
            $table->string('province')->nullable();         // Tỉnh/Thành phố
            $table->text('address')->nullable();            // Địa chỉ nhận hàng
            $table->text('note')->nullable();               // Ghi chú

            $table->timestamps();

            $table->unique(['shop_id', 'order_code']);
            $table->index(['shop_id', 'order_date']);
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
