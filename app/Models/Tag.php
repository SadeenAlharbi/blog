<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Post;


class Tag extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
    ];

    /**
     * The single, central list of content categories (slug => Arabic name).
     * This is the ONE place to edit categories. Posts may only be tagged from
     * this list — no free-text tags — which keeps categories meaningful and
     * prevents junk tags. Slugs are stable ASCII identifiers (never change a
     * slug once posts are attached to it).
     */
    public static function categories(): array
    {
        return [
            'history' => 'تاريخ المملكة',
            'founding-unification' => 'تأسيس المملكة وتوحيدها',
            'kings' => 'ملوك المملكة',
            'historical-figures' => 'الشخصيات التاريخية',
            'historical-events' => 'الأحداث التاريخية',
            'modern-saudi' => 'السعودية الحديثة',
            'vision-2030' => 'رؤية السعودية 2030',
            'national-development' => 'التنمية والتحول الوطني',
            'hajj-umrah' => 'الحج والعمرة',
            'makkah-madinah' => 'مكة المكرمة والمدينة المنورة',
            'regions-cities' => 'المناطق والمدن السعودية',
            'landmarks' => 'المعالم والمواقع التاريخية',
            'heritage' => 'الآثار والتراث',
            'culture' => 'الثقافة السعودية',
            'customs-traditions' => 'العادات والتقاليد',
            'literature-poetry' => 'الأدب والشعر',
            'arts' => 'الفنون السعودية',
            'education' => 'التعليم',
            'economy' => 'الاقتصاد',
            'society' => 'المجتمع السعودي',
            'life-in-saudi' => 'الحياة في السعودية',
            'international-relations' => 'العلاقات الدولية',
            'historical-sources' => 'الوثائق والمصادر التاريخية',
            'timeline' => 'الخط الزمني',
            'facts' => 'معلومات وحقائق',
        ];
    }

    public function posts()
    {
        return $this->belongsToMany(Post::class);
    }
}
