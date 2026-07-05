<?php

namespace Database\Seeders;

use App\StorePage;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class StorePagesSeeder extends Seeder
{
    /** @var array<int>|null */
    public ?array $businessIds = null;

    public bool $force = false;

    public function run(): void
    {
        if (! Schema::hasTable('store_pages')) {
            $this->command?->error('store_pages table does not exist. Run migrations first.');

            return;
        }

        $businessIds = $this->businessIds ?? DB::table('business')->pluck('id')->map(fn ($id) => (int) $id)->all();

        if ($businessIds === []) {
            $this->command?->warn('No businesses found to seed.');

            return;
        }

        $now = Carbon::now();

        foreach ($businessIds as $businessId) {
            $this->seedBusiness($businessId, $now);
        }
    }

    protected function seedBusiness(int $businessId, Carbon $now): void
    {
        $existing = StorePage::forBusiness($businessId)->exists();

        if ($existing && ! $this->force) {
            $this->command?->info("Skipping business {$businessId}: store pages already seeded (use --force to replace).");

            return;
        }

        if ($existing) {
            StorePage::forBusiness($businessId)->delete();
        }

        foreach ($this->defaultPages() as $page) {
            StorePage::create(array_merge($page, [
                'business_id' => $businessId,
                'is_active' => true,
                'show_in_footer' => true,
                'show_in_header' => false,
                'created_at' => $now,
                'updated_at' => $now,
            ]));
        }

        $this->command?->info('Business '.$businessId.': seeded '.count($this->defaultPages()).' store pages.');
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function defaultPages(): array
    {
        return [
            [
                'title' => 'سياسة الخصوصية',
                'slug' => 'privacy-policy',
                'page_type' => StorePage::PAGE_TYPE_PRIVACY,
                'footer_group' => StorePage::FOOTER_GROUP_LEGAL,
                'sort_order' => 10,
                'excerpt' => 'كيف نجمع ونستخدم ونحمي بياناتك الشخصية.',
                'meta_title' => 'سياسة الخصوصية | التابعين للإلكترونيات',
                'content' => '<p>نلتزم في التابعين للإلكترونيات بحماية خصوصية عملائنا. توضح هذه الصفحة أنواع البيانات التي قد نجمعها عند استخدام الموقع أو إتمام الطلبات، وكيفية استخدامها لتحسين الخدمة ومعالجة الطلبات.</p><p>لن نشارك بياناتك مع أطراف ثالثة إلا عند الضرورة لتنفيذ الطلب أو وفقاً للقانون.</p>',
            ],
            [
                'title' => 'الشروط والأحكام',
                'slug' => 'terms',
                'page_type' => StorePage::PAGE_TYPE_TERMS,
                'footer_group' => StorePage::FOOTER_GROUP_LEGAL,
                'sort_order' => 20,
                'excerpt' => 'الشروط العامة لاستخدام المتجر وإتمام الطلبات.',
                'meta_title' => 'الشروط والأحكام | التابعين للإلكترونيات',
                'content' => '<p>باستخدامك لموقع التابعين للإلكترونيات فإنك توافق على الشروط والأحكام المنشورة هنا. يشمل ذلك سياسات التسعير، والدفع، والتوصيل، واستخدام الحساب.</p><p>نحتفظ بحق تحديث هذه الشروط في أي وقت، وسيتم نشر أي تعديل على هذه الصفحة.</p>',
            ],
            [
                'title' => 'سياسة الإرجاع',
                'slug' => 'return-policy',
                'page_type' => StorePage::PAGE_TYPE_RETURNS,
                'footer_group' => StorePage::FOOTER_GROUP_CUSTOMER_SERVICE,
                'sort_order' => 30,
                'excerpt' => 'شروط إرجاع المنتجات واستبدالها.',
                'content' => '<p>يمكنك طلب الإرجاع أو الاستبدال وفقاً لسياسة الإرجاع المعتمدة لدينا، بشرط أن يكون المنتج في حالته الأصلية ومع جميع الملحقات وفي المدة المحددة من تاريخ الاستلام.</p><p>للاستفسار عن حالة الإرجاع، يرجى التواصل مع خدمة العملاء.</p>',
            ],
            [
                'title' => 'الضمان والصيانة',
                'slug' => 'warranty',
                'page_type' => StorePage::PAGE_TYPE_WARRANTY,
                'footer_group' => StorePage::FOOTER_GROUP_CUSTOMER_SERVICE,
                'sort_order' => 40,
                'excerpt' => 'معلومات عن ضمان المنتجات وخدمات ما بعد البيع.',
                'content' => '<p>نوفر ضماناً أصلياً على المنتجات المؤهلة وفقاً لشروط الشركة المصنعة. تختلف مدة الضمان حسب نوع المنتج والماركة.</p><p>للحصول على خدمة الصيانة أو تفعيل الضمان، يرجى الاحتفاظ بفاتورة الشراء والتواصل معنا.</p>',
            ],
            [
                'title' => 'الأسئلة الشائعة',
                'slug' => 'faq',
                'page_type' => StorePage::PAGE_TYPE_FAQ,
                'footer_group' => StorePage::FOOTER_GROUP_CUSTOMER_SERVICE,
                'sort_order' => 50,
                'excerpt' => 'إجابات على أكثر الأسئلة شيوعاً.',
                'content' => '<h3>كيف أتابع طلبي؟</h3><p>يمكنك متابعة طلبك من خلال حسابك في قسم طلباتي بعد تسجيل الدخول.</p><h3>ما طرق الدفع المتاحة؟</h3><p>تتوفر عدة طرق دفع عند إتمام الطلب حسب الخيارات المعروضة في صفحة الدفع.</p><h3>كم مدة التوصيل؟</h3><p>تختلف مدة التوصيل حسب المحافظة والمدينة والمنطقة المختارة عند الطلب.</p>',
            ],
            [
                'title' => 'من نحن',
                'slug' => 'about-us',
                'page_type' => StorePage::PAGE_TYPE_CUSTOM,
                'footer_group' => StorePage::FOOTER_GROUP_QUICK_LINKS,
                'sort_order' => 60,
                'excerpt' => 'تعرف على التابعين للإلكترونيات.',
                'content' => '<p>التابعين للإلكترونيات وجهتك الأولى للإلكترونيات في مصر. نوفر أحدث الأجهزة بأفضل الأسعار مع ضمان رسمي وخدمة متميزة ما بعد البيع.</p>',
            ],
        ];
    }
}
