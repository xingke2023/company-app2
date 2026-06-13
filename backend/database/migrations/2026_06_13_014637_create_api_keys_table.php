<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('api_keys', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name', 100)->comment('用途说明，如「进销存小程序服务账号」');
            $table->string('key', 64)->unique()->comment('永久有效的 API Key，Bearer token 形式传入');
            $table->timestamp('last_used_at')->nullable();
            $table->timestamps();

            $table->index('key');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('api_keys');
    }
};
