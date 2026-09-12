<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {

    public function up(): void
    {
        Schema::create('rest_corrections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rest_id')->constrained()->onDelete('cascade');
            $table->foreignId('admin_id')->nullable()->constrained()->onDelete('cascade');
            $table->time('new_break_in')->nullable();
            $table->time('new_break_out')->nullable();
            $table->string('approval_status')->default('承認待ち');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rest_corrections');
    }
};