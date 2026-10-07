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
            $table->string('source_name'); // e.g., 'Realestate.com.kh', 'Khmer24', 'Harbor Property', 'ARC Cambodia', 'Century 21'
            $table->string('target_url');
            $table->string('category')->default('Condo'); // Condo, Villa, Land, Commercial, etc.
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
            $table->string('source')->default('manual')->index();
            $table->string('source_name')->default('Manual');
            $table->string('source_id')->nullable();
            $table->text('title');
            $table->string('property_type')->default('Other')->index();
            $table->string('listing_type')->default('Sale')->index();
            $table->decimal('price_usd', 15, 2)->nullable()->index();
            $table->decimal('price', 15, 2)->nullable();
            $table->string('currency')->default('USD');
            $table->float('area_sqm')->nullable()->index();
            $table->float('price_per_sqm')->nullable()->index();
            $table->string('province')->nullable()->index();
            $table->string('district')->nullable()->index();
            $table->string('commune')->nullable();
            $table->text('address')->nullable();
            $table->string('location')->nullable();
            $table->string('city')->nullable();
            $table->integer('bedrooms')->nullable();
            $table->integer('bathrooms')->nullable();
            $table->text('url')->nullable();
            $table->text('source_url')->nullable();
            $table->text('image_url')->nullable();
            $table->string('urgency_tag')->nullable()->index();
            $table->string('status')->default('available')->index();
            $table->double('latitude')->nullable();
            $table->double('longitude')->nullable();
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
