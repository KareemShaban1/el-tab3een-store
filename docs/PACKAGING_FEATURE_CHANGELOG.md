# Packaging Feature — Implementation Changelog

This document lists **every code and database change** for the liquid/sauce packaging workflow (chocolate, caramel, tomato sauce, syrup, etc.) and explains how to **show or hide** the feature.

---

## Quick enable / disable

| Level | File / location | Setting | Effect |
|-------|-----------------|---------|--------|
| **Global (code)** | `Modules/Manufacturing/Config/packaging_feature.php` | `'enabled' => true/false` | Master switch. When `false`, all packaging UI, routes, product field, and POS filter are hidden. DB columns remain (nullable). |
| **Environment** | `.env` | `MFG_PACKAGING_FEATURE_ENABLED=true/false` | Overrides global config without editing PHP file. |
| **Per business** | Manufacturing → Settings | **Enable packaging & filling workflow** | Stored in `business.manufacturing_settings` JSON key `enable_packaging_workflow`. **Defaults to ON** when global is enabled; uncheck to hide for that business. |
| **POS filter** | `packaging_feature.php` | `hide_packaging_materials_in_pos` | When true, products with `product_usage_type = packaging_material` are excluded from POS search. |

### Recommended rollout

1. Deploy code and run migrations (columns are nullable — safe on existing data).
2. Set `MFG_PACKAGING_FEATURE_ENABLED=false` until products and profiles are configured.
3. Enable per business in Manufacturing Settings when ready.
4. Assign permissions: `manufacturing.access_packaging`, `manufacturing.manage_packaging_profiles`.

---

## Database changes

### `products` table

| Column | Type | Nullable | Purpose |
|--------|------|----------|---------|
| `product_usage_type` | ENUM | **Yes** | `raw_ingredient`, `bulk_finished`, `packaging_material`, `packaged_finished` |

**Migration:** `2026_09_02_100000_add_product_usage_type_to_products_table.php`

### `transactions` table

| Column | Type | Nullable | Purpose |
|--------|------|----------|---------|
| `mfg_stage` | ENUM(`cooking`,`packaging`) | **Yes** | Distinguishes bulk cooking vs pack/fill |
| `mfg_packaging_profile_id` | INT | **Yes** | FK to profile used |
| `mfg_containers_count` | INT | **Yes** | Bottles/bags entered |
| `mfg_cartons_count` | INT | **Yes** | Computed full cartons |
| `mfg_container_type` | ENUM(`bottle`,`bag`) | **Yes** | Container type |

**Migration:** `2026_09_02_100003_add_packaging_columns_to_transactions_table.php`

### New tables

| Table | Purpose |
|-------|---------|
| `mfg_packaging_profiles` | Bulk → carton conversion rules |
| `mfg_packaging_materials` | BOM lines (bottle, cap, label, carton) |

**Migrations:** `2026_09_02_100001_*`, `2026_09_02_100002_*`

### Permissions

| Permission | Purpose |
|------------|---------|
| `manufacturing.access_packaging` | Packaging production screens |
| `manufacturing.manage_packaging_profiles` | CRUD packaging profiles |

**Migration:** `2026_09_02_100004_add_packaging_permissions.php`

---

## New files

### Config & feature flag

| File | Role |
|------|------|
| `Config/packaging_feature.php` | Master config + product usage type labels |
| `Support/PackagingFeature.php` | `isGloballyEnabled()`, `isEnabledForBusiness()`, POS filter check |

### Entities

| File | Model |
|------|-------|
| `Entities/MfgPackagingProfile.php` | Packaging profile |
| `Entities/MfgPackagingMaterial.php` | Profile material line |

### Utils

| File | Role |
|------|------|
| `Utils/PackagingUtil.php` | Carton/bulk/material calculations, stock checks |

### Controllers

| File | Routes prefix |
|------|---------------|
| `Http/Controllers/PackagingProfileController.php` | `/manufacturing/packaging-profile` |
| `Http/Controllers/PackagingProductionController.php` | `/manufacturing/packaging-production` |

### Views

| Path | Screen |
|------|--------|
| `Resources/views/packaging_profile/*` | Profile CRUD |
| `Resources/views/packaging_production/*` | Packaging production list/create/show |
| `Resources/views/product/partials/product_usage_type.blade.php` | Product form field |

### Documentation

| File | Content |
|------|---------|
| `SAUCE_FACTORY_PACKAGING_FEATURE_SPEC.md` | Full bilingual spec (updated for all liquids) |
| `PACKAGING_FEATURE_CHANGELOG.md` | This file |

---

## Modified files

| File | Change |
|------|--------|
| `Http/routes.php` | Packaging profile + production routes |
| `Http/Controllers/DataController.php` | Menu items, permissions, `product_form_part`, `after_product_saved`, `modify_product_search_query` |
| `Http/Controllers/SettingsController.php` | Save `enable_packaging_workflow` |
| `Providers/ManufacturingServiceProvider.php` | Merge `packaging_feature` config |
| `Resources/views/layouts/nav.blade.php` | Packaging nav links (when enabled) |
| `Resources/views/settings/index.blade.php` | Packaging workflow checkbox |
| `Resources/lang/en/lang.php` | English strings |
| `Resources/lang/ar/lang.php` | Arabic strings |
| `app/Utils/ProductUtil.php` | Module hook `modify_product_search_query` in `filterProduct()` |

---

## User workflow (after enabled)

1. **Products** — Set usage types: bulk sauce, packaging materials, packaged carton SKU.
2. **Recipe** — Stage 1 cooking (existing production) → bulk stock.
3. **Packaging profile** — Link bulk variation → carton variation + materials BOM.
4. **Packaging production** — Enter bottles/bags → system computes cartons → finalize stock moves.
5. **POS** — Sell `packaged_finished` carton SKU; packaging materials hidden from search by default.

---

## Full example A → Z (Caramel Sauce batch)
## مثال كامل من الألف إلى الياء (دفعة صوص كراميل)

---

### English

This walkthrough produces **caramel sauce in bulk**, then fills it into **500 ml bottles**, packs them into **cartons of 12**, and sells cartons in **POS**.

#### Scenario numbers

| Item | Value |
|------|-------|
| Goal of this batch | Fill **120 bottles** → **10 cartons** |
| Bulk needed | 120 × 0.48 kg = **57.6 kg** caramel |
| Carton rule | **12 bottles per carton** |
| Filling loss | **0%** (set 2% in profile if needed) |

#### Step 0 — Enable the feature

1. In `.env`: `MFG_PACKAGING_FEATURE_ENABLED=true`
2. Run: `php artisan config:clear`
3. Open **Manufacturing → Settings** and keep **Enable packaging & filling workflow** checked
4. Role must have Manufacturing access (`access_production` is enough; optional: `access_packaging`, `manage_packaging_profiles`)

You should see menu items: **Packaging Profiles** and **Packaging Production**.

#### Step 1 — Create products (with usage type)

Go to **Products → Add** for each row. Set **Manufacturing Usage Type** as shown.

**A) Raw ingredients (recipe inputs)**

| Product name | Unit | Usage type | Notes |
|--------------|------|------------|-------|
| Sugar | kg | `Raw ingredient` | Buy / opening stock |
| Cream | L or kg | `Raw ingredient` | |
| Butter | kg | `Raw ingredient` | |

Give each product stock (Purchase or Opening Stock), e.g. Sugar 200 kg, Cream 50, Butter 30.

**B) Bulk finished (cooking output)**

| Product name | Unit | Usage type | SKU example |
|--------------|------|------------|-------------|
| Caramel Sauce — Bulk | kg | `Bulk finished` | `CRM-BULK` |

This is **not** what POS sells by carton. It is stock after cooking and input to packaging.

**C) Packaging materials**

| Product name | Unit | Usage type | Opening stock example |
|--------------|------|------------|------------------------|
| Bottle 500ml (empty) | pcs | `Packaging material` | 500 |
| Bottle Cap | pcs | `Packaging material` | 500 |
| Caramel Label / Sticker | pcs | `Packaging material` | 500 |
| Empty Carton (12×500ml) | pcs | `Packaging material` | 50 |

These are hidden from POS search by default.

**D) Packaged finished (POS sellable)**

| Product name | Unit | Usage type | Selling price |
|--------------|------|------------|---------------|
| Caramel Sauce — Carton 12×500ml | carton | `Packaged finished` | e.g. 180 |

This is the SKU the salesman searches in POS.

#### Step 2 — Create cooking recipe (Stage 1)

1. Go to **Manufacturing → Recipe → Add**
2. Choose finished product: **Caramel Sauce — Bulk**
3. Add ingredients, for example for **10 kg** recipe output:

| Ingredient | Qty |
|------------|-----|
| Sugar | 6 kg |
| Cream | 3 L |
| Butter | 1 kg |

4. Save recipe (set production cost / waste if you use them)

> Tip: Any liquid works the same way — Chocolate Sauce, Tomato Sauce, Syrup — only product names and recipe change.

#### Step 3 — Run bulk production (cook)

1. Go to **Manufacturing → Add Production** (normal production screen)
2. Location: your factory location
3. Product / recipe: **Caramel Sauce — Bulk**
4. Quantity: produce enough for packaging — e.g. **60 kg** (covers 57.6 kg for 120 bottles)
5. Check **Finalize** and save

**Stock after finalize (example):**

| Product | Change |
|---------|--------|
| Sugar / Cream / Butter | ↓ deducted by recipe |
| Caramel Sauce — Bulk | ↑ **+60 kg** |

#### Step 4 — Create packaging profile (BOM)

1. Go to **Manufacturing → Packaging Profiles → Add**
2. Fill:

| Field | Value |
|-------|-------|
| Profile name | `Caramel 500ml × 12 carton` |
| Bulk product | Caramel Sauce — Bulk |
| Output product | Caramel Sauce — Carton 12×500ml |
| Container type | Bottle |
| Container volume | `0.5` (optional) |
| Units per carton | `12` |
| Bulk qty per container | `0.48` (kg sauce per bottle) |
| Waste percent | `0` (or `2`) |
| Active | checked |

3. Add materials:

| Material | Role | Qty per container | Qty per carton |
|----------|------|-------------------|----------------|
| Bottle 500ml | Container | **1** | — |
| Bottle Cap | Closure | **1** | — |
| Caramel Label | Label | **1** | — |
| Empty Carton | Outer carton | — | **1** |

4. Save

**Math the profile encodes:**

```
cartons        = floor(bottles / 12)
bulk_consumed  = bottles × 0.48 × (1 + waste%/100)
caps/bottles/labels = bottles × 1
empty cartons  = full_cartons × 1
```

#### Step 5 — Run packaging production (fill & pack)

1. Go to **Manufacturing → Packaging Production → Add**
2. Fill:

| Field | Value |
|-------|-------|
| Date | today |
| Location | same factory location |
| Packaging profile | `Caramel 500ml × 12 carton` |
| Containers count | **120** (bottles, not cartons) |
| Total (cost) | enter production total if needed |
| Finalize | **checked** |

3. Screen preview should show approximately:

| Computed | Value |
|----------|-------|
| Cartons | **10** |
| Bulk consumed | **57.6 kg** |
| Bottles / Caps / Labels | **120** each |
| Empty cartons | **10** |

4. Save (finalize)

> Strict policy: containers must be a multiple of 12 (e.g. 120, 132). 125 bottles will be rejected.

**Stock after finalize:**

| Product | Change |
|---------|--------|
| Caramel Sauce — Bulk | ↓ −57.6 kg (≈ 2.4 kg left from 60) |
| Bottle 500ml | ↓ −120 |
| Cap | ↓ −120 |
| Label | ↓ −120 |
| Empty Carton | ↓ −10 |
| **Caramel Sauce — Carton 12×500ml** | ↑ **+10 cartons** |

#### Step 6 — Sell in POS

1. Open **POS** at that location
2. Search: `Caramel Sauce — Carton` (or SKU)
3. Stock shown = **10 cartons**
4. Sell **3 cartons** → remaining stock **7 cartons**
5. Bottles inside the carton are informational only — selling 1 unit deducts **1 carton**, not 12 bottles

Packaging materials (empty bottle, cap) should **not** appear in POS search.

#### End-to-end stock picture

```
Ingredients ──(Recipe / Production)──► Bulk Caramel (+60 kg)
                                              │
                                              │ Packaging Production
                                              │ 120 bottles → 10 cartons
                                              ▼
                         Carton SKU in warehouse (+10)
                                              │
                                              │ POS sale (3)
                                              ▼
                         Carton stock left = 7
```

#### Checklist (copy for training)

- [ ] Feature enabled (`.env` + Settings)
- [ ] Raw ingredients have stock
- [ ] Bulk product = `bulk_finished`
- [ ] Materials = `packaging_material` with stock
- [ ] Carton product = `packaged_finished` with sell price
- [ ] Recipe exists for bulk product
- [ ] Bulk production finalized (≥ bulk needed)
- [ ] Packaging profile saved (12 / carton, 0.48 kg / bottle, materials)
- [ ] Packaging production finalized with bottle count multiple of 12
- [ ] POS shows carton stock and sells correctly

#### Same flow for Chocolate / Tomato / Syrup

Repeat Steps 1–6; only change names:

| Liquid | Bulk product | Carton product | Profile name |
|--------|--------------|----------------|--------------|
| Chocolate | Chocolate Sauce — Bulk | Chocolate — Carton 12×500ml | Chocolate 500ml × 12 |
| Tomato | Tomato Sauce — Bulk | Tomato — Carton 12×500ml | Tomato 500ml × 12 |
| Syrup | Syrup — Bulk | Syrup — Carton 24×250ml | Syrup 250ml × 24 |

Adjust `units_per_carton` and `bulk_qty_per_container` to match your real fill volume.

---

### العربية

هذا الدليل ينتج **صوص كراميل بالجملة**، ثم يعبّئها في **زجاجات 500 مل**، ويضعها في **كراتين من 12 زجاجة**، ثم يبيع الكراتين من **نقطة البيع (POS)**.

#### أرقام السيناريو

| البند | القيمة |
|------|--------|
| هدف هذه الدفعة | تعبئة **120 زجاجة** → **10 كراتين** |
| الجملة المطلوبة | 120 × 0.48 كجم = **57.6 كجم** كراميل |
| قاعدة الكرتون | **12 زجاجة لكل كرتون** |
| نسبة الفاقد | **0%** (يمكن وضع 2% في ملف التعبئة) |

#### الخطوة 0 — تفعيل الميزة

1. في ملف `.env`: `MFG_PACKAGING_FEATURE_ENABLED=true`
2. نفّذ: `php artisan config:clear`
3. افتح **التصنيع ← الإعدادات** واترك خيار **تفعيل سير عمل التعبئة والتغليف** مفعّلاً
4. يجب أن يكون للدور صلاحية التصنيع (`access_production` كافية؛ واختياريًا: `access_packaging` و `manage_packaging_profiles`)

يجب أن تظهر عناصر القائمة: **ملفات التعبئة** و **إنتاج التعبئة**.

#### الخطوة 1 — إنشاء المنتجات (مع نوع الاستخدام)

اذهب إلى **المنتجات ← إضافة** لكل صف. اضبط **نوع الاستخدام في التصنيع** كما في الجدول.

**أ) المواد الخام (مدخلات الوصفة)**

| اسم المنتج | الوحدة | نوع الاستخدام | ملاحظات |
|------------|--------|---------------|---------|
| سكر | كجم | `مادة خام` / Raw ingredient | شراء / مخزون افتتاحي |
| قشطة / كريمة | لتر أو كجم | `مادة خام` | |
| زبدة | كجم | `مادة خام` | |

أضف مخزونًا لكل منتج (شراء أو مخزون افتتاحي)، مثال: سكر 200 كجم، قشطة 50، زبدة 30.

**ب) منتج الجملة (مخرجات الطبخ)**

| اسم المنتج | الوحدة | نوع الاستخدام | مثال SKU |
|------------|--------|---------------|----------|
| صوص كراميل — جملة | كجم | `منتج جملة` / Bulk finished | `CRM-BULK` |

هذا المنتج **ليس** ما يُباع بالكرتون في POS. هو مخزون بعد الطبخ ومدخل لمرحلة التعبئة.

**ج) مواد التعبئة**

| اسم المنتج | الوحدة | نوع الاستخدام | مخزون افتتاحي مقترح |
|------------|--------|---------------|---------------------|
| زجاجة 500 مل (فارغة) | قطعة | `مادة تعبئة` / Packaging material | 500 |
| غطاء زجاجة | قطعة | `مادة تعبئة` | 500 |
| ملصق / ستيكر كراميل | قطعة | `مادة تعبئة` | 500 |
| كرتون فارغ (12×500مل) | قطعة | `مادة تعبئة` | 50 |

هذه المواد مخفية من بحث POS افتراضيًا.

**د) المنتج المعبأ (قابل للبيع في POS)**

| اسم المنتج | الوحدة | نوع الاستخدام | سعر البيع |
|------------|--------|---------------|-----------|
| صوص كراميل — كرتون 12×500مل | كرتون | `منتج معبأ` / Packaged finished | مثل 180 |

هذا هو الـ SKU الذي يبحث عنه البائع في نقطة البيع.

#### الخطوة 2 — إنشاء وصفة الطبخ (المرحلة 1)

1. اذهب إلى **التصنيع ← الوصفة ← إضافة**
2. اختر المنتج النهائي: **صوص كراميل — جملة**
3. أضف المكونات، مثالًا لمخرجات وصفة **10 كجم**:

| المكوّن | الكمية |
|---------|--------|
| سكر | 6 كجم |
| قشطة | 3 لتر |
| زبدة | 1 كجم |

4. احفظ الوصفة (اضبط تكلفة الإنتاج / الفاقد إن لزم)

> ملاحظة: أي سائل يعمل بنفس الطريقة — صوص شوكولاتة، صوص طماطم، شراب — يتغيّر فقط اسم المنتج والوصفة.

#### الخطوة 3 — تشغيل إنتاج الجملة (الطبخ)

1. اذهب إلى **التصنيع ← إضافة إنتاج** (شاشة الإنتاج العادية)
2. الموقع: موقع المصنع
3. المنتج / الوصفة: **صوص كراميل — جملة**
4. الكمية: أنتج ما يكفي للتعبئة — مثال **60 كجم** (يغطي 57.6 كجم لـ 120 زجاجة)
5. فعّل **إنهاء / Finalize** ثم احفظ

**المخزون بعد الإنهاء (مثال):**

| المنتج | التغيير |
|--------|---------|
| سكر / قشطة / زبدة | ↓ تُخصم حسب الوصفة |
| صوص كراميل — جملة | ↑ **+60 كجم** |

#### الخطوة 4 — إنشاء ملف التعبئة (BOM)

1. اذهب إلى **التصنيع ← ملفات التعبئة ← إضافة**
2. املأ الحقول:

| الحقل | القيمة |
|-------|--------|
| اسم الملف | `كراميل 500مل × 12 كرتون` |
| منتج الجملة | صوص كراميل — جملة |
| منتج المخرجات | صوص كراميل — كرتون 12×500مل |
| نوع الحاوية | زجاجة |
| حجم الحاوية | `0.5` (اختياري) |
| وحدات لكل كرتون | `12` |
| كمية الجملة لكل حاوية | `0.48` (كجم لكل زجاجة) |
| نسبة الفاقد | `0` (أو `2`) |
| نشط | مفعّل |

3. أضف المواد:

| المادة | الدور | الكمية لكل حاوية | الكمية لكل كرتون |
|--------|-------|------------------|------------------|
| زجاجة 500 مل | حاوية | **1** | — |
| غطاء | غطاء / Closure | **1** | — |
| ملصق كراميل | ملصق | **1** | — |
| كرتون فارغ | كرتون خارجي | — | **1** |

4. احفظ

**الحساب الذي يطبّقه الملف:**

```
الكراتين           = floor(الزجاجات / 12)
استهلاك_الجملة     = الزجاجات × 0.48 × (1 + نسبة_الفاقد/100)
أغطية/زجاجات/ملصقات = الزجاجات × 1
الكراتين_الفارغة   = الكراتين_الكاملة × 1
```

#### الخطوة 5 — تشغيل إنتاج التعبئة (تعبئة وتغليف)

1. اذهب إلى **التصنيع ← إنتاج التعبئة ← إضافة**
2. املأ:

| الحقل | القيمة |
|-------|--------|
| التاريخ | اليوم |
| الموقع | نفس موقع المصنع |
| ملف التعبئة | `كراميل 500مل × 12 كرتون` |
| عدد الحاويات | **120** (زجاجات، وليس كراتين) |
| الإجمالي (التكلفة) | أدخل إجمالي الإنتاج إن لزم |
| إنهاء | **مفعّل** |

3. يجب أن تظهر المعاينة تقريبًا:

| المحسوب | القيمة |
|---------|--------|
| الكراتين | **10** |
| الجملة المستهلكة | **57.6 كجم** |
| زجاجات / أغطية / ملصقات | **120** لكل منها |
| كراتين فارغة | **10** |

4. احفظ (إنهاء)

> السياسة الصارمة: عدد الحاويات يجب أن يكون من مضاعفات 12 (مثل 120، 132). إدخال 125 زجاجة يُرفض.

**المخزون بعد الإنهاء:**

| المنتج | التغيير |
|--------|---------|
| صوص كراميل — جملة | ↓ −57.6 كجم (يتبقى ≈ 2.4 كجم من 60) |
| زجاجة 500 مل | ↓ −120 |
| غطاء | ↓ −120 |
| ملصق | ↓ −120 |
| كرتون فارغ | ↓ −10 |
| **صوص كراميل — كرتون 12×500مل** | ↑ **+10 كراتين** |

#### الخطوة 6 — البيع من نقطة البيع (POS)

1. افتح **POS** في نفس الموقع
2. ابحث عن: `صوص كراميل — كرتون` (أو الـ SKU)
3. المخزون الظاهر = **10 كراتين**
4. بِع **3 كراتين** → المتبقي **7 كراتين**
5. الزجاجات داخل الكرتون معلوماتية فقط — بيع وحدة واحدة يخصم **كرتونًا واحدًا** وليس 12 زجاجة

مواد التعبئة (زجاجة فارغة، غطاء) **يجب ألا تظهر** في بحث POS.

#### صورة المخزون من البداية للنهاية

```
المكونات ──(وصفة / إنتاج)──► كراميل جملة (+60 كجم)
                                      │
                                      │ إنتاج التعبئة
                                      │ 120 زجاجة → 10 كراتين
                                      ▼
                         منتج الكرتون في المخزن (+10)
                                      │
                                      │ بيع POS (3)
                                      ▼
                         مخزون الكراتين المتبقي = 7
```

#### قائمة تحقق (للتدريب)

- [ ] الميزة مفعّلة (`.env` + الإعدادات)
- [ ] المواد الخام لديها مخزون
- [ ] منتج الجملة = `bulk_finished`
- [ ] مواد التعبئة = `packaging_material` ولديها مخزون
- [ ] منتج الكرتون = `packaged_finished` وله سعر بيع
- [ ] توجد وصفة لمنتج الجملة
- [ ] تم إنهاء إنتاج الجملة (≥ الكمية المطلوبة)
- [ ] تم حفظ ملف التعبئة (12 / كرتون، 0.48 كجم / زجاجة، والمواد)
- [ ] تم إنهاء إنتاج التعبئة بعدد زجاجات من مضاعفات 12
- [ ] POS يعرض مخزون الكراتين ويبيع بشكل صحيح

#### نفس التدفق للشوكولاتة / الطماطم / الشراب

كرّر الخطوات 1–6 مع تغيير الأسماء فقط:

| السائل | منتج الجملة | منتج الكرتون | اسم الملف |
|--------|-------------|--------------|-----------|
| شوكولاتة | صوص شوكولاتة — جملة | شوكولاتة — كرتون 12×500مل | شوكولاتة 500مل × 12 |
| طماطم | صوص طماطم — جملة | طماطم — كرتون 12×500مل | طماطم 500مل × 12 |
| شراب | شراب — جملة | شراب — كرتون 24×250مل | شراب 250مل × 24 |

عدّل `units_per_carton` و `bulk_qty_per_container` حسب حجم التعبئة الفعلي لديك.

---

## Run migrations

```bash
php artisan module:migrate Manufacturing --force
php artisan config:clear
php artisan cache:clear
```

---

## Rollback notes

- Set `enabled => false` in config to hide UI without dropping tables.
- Migrations `down()` methods drop columns/tables if full rollback is required.
- Existing production transactions are unaffected (`mfg_stage` NULL = legacy cooking).

---

## Version

Implemented: 2026-09-02 · Manufacturing module packaging feature v1
