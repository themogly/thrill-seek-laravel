<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('type');
            $table->string('summary')->nullable();
            $table->text('description')->nullable();
            $table->string('image')->nullable();
            $table->string('page_path')->nullable();
            $table->unsignedInteger('price_pence')->nullable();
            $table->unsignedInteger('deposit_pence')->nullable();
            $table->string('price_note')->nullable();
            $table->boolean('show_from_price')->default(false);
            $table->string('duration')->nullable();
            $table->json('features')->nullable();
            $table->json('weight_charges')->nullable();
            $table->json('repeat_pricing')->nullable();
            $table->boolean('highlight')->default(false);
            $table->boolean('featured_on_home')->default(false);
            $table->boolean('active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('product_add_ons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->unsignedInteger('price_pence');
            $table->string('note')->nullable();
            $table->boolean('purchasable')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_add_ons');
        Schema::dropIfExists('products');
    }
};
