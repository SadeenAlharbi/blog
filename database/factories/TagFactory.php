<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\Tag>
 */
class TagFactory extends Factory
{
    public function definition(): array
    {
        // Arabic display names with stable ASCII slugs (Str::slug would strip Arabic).
        $tags = [
            'رؤية 2030' => 'vision-2030',
            'التحول الرقمي' => 'digital-transformation',
            'الذكاء الاصطناعي' => 'artificial-intelligence',
            'السياحة' => 'tourism',
            'التراث' => 'heritage',
            'نيوم' => 'neom',
            'الاقتصاد' => 'economy',
            'التقنية' => 'technology',
            'الثقافة' => 'culture',
            'المجتمع' => 'society',
            'المشاريع الوطنية' => 'national-projects',
            'الطاقة المتجددة' => 'renewable-energy',
            'التعليم' => 'education',
            'ريادة الأعمال' => 'entrepreneurship',
            'الرياضة' => 'sports',
            'المشاريع الكبرى' => 'giga-projects',
        ];

        $name = fake()->unique()->randomElement(array_keys($tags));

        return [
            'name' => $name,
            'slug' => $tags[$name],
        ];
    }
}
