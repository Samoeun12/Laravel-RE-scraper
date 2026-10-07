<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('scraper_tasks', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('source_name'); // e.g., 'Realestate.com.kh', 'Khmer24', 'Zillow'
            $table->string('target_url');
            $table->string('category')->default('Condo'); // Condo, Villa, Land, Commercial
            $table->string('status')->default('idle'); // idle, running, completed, error
            $table->string('frequency')->default('Every 6 Hours');
            $table->integer('items_scraped')->default(0);
            $table->timestamp('last_run_at')->nullable();
            $table->text('last_log')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('properties', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('property_type'); // Condo, Apartment, Villa, Land, Office
            $table->string('listing_type')->default('Sale'); // Sale, Rent
            $table->decimal('price', 15, 2);
            $table->string('currency')->default('USD');
            $table->string('location');
            $table->string('city')->default('Phnom Penh');
            $table->integer('bedrooms')->default(1);
            $table->integer('bathrooms')->default(1);
            $table->float('area_sqm')->nullable();
            $table->string('source_name')->default('Manual');
            $table->string('source_url')->nullable();
            $table->string('image_url')->nullable();
            $table->string('status')->default('available'); // available, pending, sold, rented
            $table->boolean('is_featured')->default(false);
            $table->foreignId('scraper_task_id')->nullable()->constrained('scraper_tasks')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action'); // 'Logged in', 'Scraper started', 'Property added', etc.
            $table->text('description');
            $table->string('ip_address')->nullable();
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
        Schema::dropIfExists('activity_logs');
        Schema::dropIfExists('properties');
        Schema::dropIfExists('scraper_tasks');
    }
};
