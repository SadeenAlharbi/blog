<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use App\Models\Post;


/**
 * A content category.
 *
 * There is ONE category system in this project: these rows. `categories()`
 * below is the DEFAULT list the platform ships with — it is no longer a closed
 * set: an administrator may add, rename and remove categories from the admin
 * area, and `options()` is what the rest of the app reads.
 */
class Tag extends Model
{
    /**
     * SoftDeletes: a category a moderator removes is kept (with its article
     * links) so it can be restored from the admin area.
     */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
    ];

    /**
     * The default categories the platform ships with (slug => Arabic name).
     * Used to seed the `tags` table and by the admin "sync defaults" action.
     * Editing this array never removes anything an administrator added.
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

    /**
     * The categories actually available right now (slug => name).
     *
     * Reads the database — which is what an administrator manages — and falls
     * back to the shipped defaults on a database that has not been seeded yet,
     * so the post form is never empty on a fresh install.
     */
    public static function options(): array
    {
        $fromDb = static::query()->orderBy('name')->pluck('name', 'slug')->all();

        return $fromDb !== [] ? $fromDb : static::categories();
    }

    /**
     * Build a stable ASCII slug for a category name.
     *
     * Str::slug() strips Arabic entirely and would return an empty string, so
     * an Arabic-only name falls back to a short token. Uniqueness is enforced
     * against existing rows.
     */
    public static function makeSlug(string $name, ?int $ignoreId = null): string
    {
        $base = Str::slug($name);

        if ($base === '') {
            $base = 'category-'.Str::lower(Str::random(6));
        }

        $slug = $base;
        $i = 1;

        while (
            static::withTrashed()
                ->where('slug', $slug)
                ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
                ->exists()
        ) {
            $slug = "{$base}-{$i}";
            $i++;
        }

        return $slug;
    }

    /**
     * The comparable form of a category name.
     *
     * Trims, collapses runs of whitespace, drops Arabic tatweel and diacritics
     * and unifies the alef/ya/ta-marbuta spellings, then lowercases — so
     * " تقنية "، "تقنيه" and "تقنية" are recognised as the SAME category and a
     * duplicate cannot be created by a stray space or spelling variant.
     */
    public static function normalizeName(string $name): string
    {
        $name = trim($name);
        $name = preg_replace('/\s+/u', ' ', $name);
        $name = preg_replace('/[\x{0640}\x{064B}-\x{0652}]/u', '', $name); // tatweel + harakat
        $name = str_replace(
            ['أ', 'إ', 'آ', 'ٱ', 'ة', 'ى'],
            ['ا', 'ا', 'ا', 'ا', 'ه', 'ي'],
            $name
        );

        return Str::lower($name);
    }

    /**
     * Find an existing category whose name matches after normalization.
     * Pass $withTrashed to look in the removed ones too.
     */
    public static function findByName(string $name, bool $withTrashed = false): ?self
    {
        $needle = static::normalizeName($name);

        $query = $withTrashed ? static::withTrashed() : static::query();

        return $query->get()->first(fn (self $tag) => static::normalizeName($tag->name) === $needle);
    }

    public function posts()
    {
        return $this->belongsToMany(Post::class);
    }
}
