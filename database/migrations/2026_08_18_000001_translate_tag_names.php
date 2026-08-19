<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Rename existing English tags to Arabic display names, keyed by their
     * (unchanged) ASCII slug. Non-destructive: only renames, keeps IDs, slugs
     * and all post_tag relationships intact. Safe to run once.
     */
    public function up(): void
    {
        if (! Schema::hasTable('tags')) {
            return;
        }

        $map = [
            'vision-2030' => 'رؤية 2030',
            'digital-transformation' => 'التحول الرقمي',
            'artificial-intelligence' => 'الذكاء الاصطناعي',
            'tourism' => 'السياحة',
            'heritage' => 'التراث',
            'neom' => 'نيوم',
            'economy' => 'الاقتصاد',
            'technology' => 'التقنية',
            'culture' => 'الثقافة',
            'society' => 'المجتمع',
            'national-projects' => 'المشاريع الوطنية',
            'renewable-energy' => 'الطاقة المتجددة',
            'education' => 'التعليم',
            'entrepreneurship' => 'ريادة الأعمال',
            'sports' => 'الرياضة',
            'giga-projects' => 'المشاريع الكبرى',
        ];

        foreach ($map as $slug => $name) {
            DB::table('tags')->where('slug', $slug)->update(['name' => $name]);
        }
    }

    public function down(): void
    {
        // Non-reversible data migration; intentionally a no-op.
    }
};
