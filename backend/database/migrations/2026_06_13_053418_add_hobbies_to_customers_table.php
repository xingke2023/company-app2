<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table): void {
            // 客户画像字段
            $table->string('gender', 20)->nullable()->after('notes');          // male / female / other
            $table->string('age_group', 20)->nullable()->after('gender');      // 18-25 / 26-35 / 36-45 / 46-55 / 55+
            $table->date('birthday')->nullable()->after('age_group');
            $table->string('city', 100)->nullable()->after('birthday');
            $table->string('industry', 100)->nullable()->after('city');        // 所在行业
            $table->string('income_level', 20)->nullable()->after('industry'); // low / medium / high / ultra-high
            $table->json('hobbies')->nullable()->after('income_level');        // ["高尔夫","阅读","旅游"]
            $table->json('tags')->nullable()->after('hobbies');                // ["VIP","价格敏感","高复购"]
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table): void {
            $table->dropColumn(['gender', 'age_group', 'birthday', 'city', 'industry', 'income_level', 'hobbies', 'tags']);
        });
    }
};
