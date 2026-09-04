# Liquid / Sauce Factory — Packaging & Filling Feature Specification
# مصنع السوائل والصلصات — مواصفات ميزة التعبئة والتغليف

> **Scope:** This spec covers **all pourable / fillable products** — tomato sauce, **chocolate sauce**, **caramel**, syrup, honey, etc. — not one SKU only. Terminology uses “sauce/liquid/bulk” generically.

---

## English

### 1. Business goal

Extend the current **Manufacturing** module to support a **liquid / sauce factory** workflow with two output paths after cooking/mixing:

| Path | Description | Stock added as |
|------|-------------|----------------|
| **A — Bulk production** | Liquid/sauce produced in kg (or liter) and sold as bulk | Bulk product variation (e.g. `Chocolate Sauce — Bulk 1 kg`, `Caramel — Bulk 5 kg`) |
| **B — Pack & fill** | Liquid filled into bottles/bags, grouped into cartons | Carton product variation (e.g. `Caramel — Carton 12×500ml`) |

Path B must:

- Consume **bulk sauce** produced in path A (or existing bulk stock)
- Consume **packaging materials** (bottle, cap, sticker, bag, carton, etc.)
- Let the user enter **number of bottles/bags**
- Auto-calculate **number of cartons** from a predefined **units per carton** rule
- Add **carton quantity** to sellable product stock
- Show correct stock in **POS** when salesman searches the carton SKU

---

### 2. Current module limitation (as-is)

Today, Manufacturing supports only **one step**:

```
Ingredients  →  Production  →  Finished product stock (single unit)
```

There is no concept of:

- Bulk vs packaged sellable units
- Packaging BOM (bottle, cap, sticker, carton)
- Carton capacity conversion
- A second “packaging production” transaction linked to bulk output

**Conclusion:** The sauce factory workflow needs a **second production stage** and **product classification**, not just recipe tweaks.

---

### 3. Recommended product model

#### 3.1 Product types / flags

Add classification on `products` (or `variations`) so the system knows how each item is used.

**Recommended column on `products`:**

```sql
product_usage_type ENUM(
  'raw_ingredient',      -- spices, oil, tomato paste (recipe inputs only)
  'bulk_finished',       -- sauce in kg/L (output of cooking, input to packaging)
  'packaging_material',  -- bottle, cap, sticker, bag, empty carton
  'packaged_finished'    -- sellable carton / retail unit in POS
) NULL DEFAULT NULL;
```

| Type | Examples | Used in |
|------|----------|---------|
| `raw_ingredient` | Salt, oil, paste | Recipe (cooking) |
| `bulk_finished` | Tomato sauce bulk (kg) | Cooking output + packaging input |
| `packaging_material` | Bottle 500ml, cap, sticker, carton | Packaging BOM only |
| `packaged_finished` | Carton 12×500ml bottles | POS sales |

> **Nullable:** `product_usage_type` should be **nullable** so existing products keep working. UI can default unset products to normal stock items.

**Optional helper flags (nullable booleans):**

```sql
is_packaging_material TINYINT(1) NULL  -- quick filter in product list
is_bulk_product       TINYINT(1) NULL  -- bulk sauce
is_sellable_retail    TINYINT(1) NULL  -- appears in POS as finished good
```

Prefer **one enum** to avoid conflicting flags; use booleans only if you need backward-compatible filters without migrating all products.

#### 3.2 Product structure example (liquid factory)

| Product | Usage type | Unit | SKU role |
|---------|------------|------|----------|
| Chocolate Sauce — Bulk | `bulk_finished` | kg | Internal + optional bulk sales |
| Caramel — Bulk | `bulk_finished` | kg | Internal + optional bulk sales |
| Bottle 500ml (empty) | `packaging_material` | pcs | Packaging BOM |
| Bottle cap | `packaging_material` | pcs | Packaging BOM |
| Label / sticker | `packaging_material` | pcs | Packaging BOM |
| Carton (12 bottles) | `packaging_material` | pcs | Packaging BOM (outer) |
| Caramel — Carton 12×500ml | `packaged_finished` | carton | **POS sellable** |

Link bulk ↔ packaged via a **packaging profile** (see §4), not only by name.

---

### 4. Packaging profile (BOM for filling)

A **packaging profile** defines how one bulk product becomes one retail/carton product.

**New table: `mfg_packaging_profiles`**

| Column | Type | Nullable | Description |
|--------|------|----------|-------------|
| `id` | int PK | no | |
| `business_id` | int | no | |
| `name` | string | no | e.g. "500ml bottle × 12 carton" |
| `bulk_variation_id` | int | no | Source sauce (kg) |
| `output_variation_id` | int | no | Carton product added to stock |
| `container_type` | enum | no | `bottle`, `bag` |
| `container_volume` | decimal | yes | e.g. 0.5 (kg or L per bottle/bag) |
| `units_per_carton` | int | no | e.g. 12 bottles per carton |
| `bulk_qty_per_container` | decimal | no | sauce used per bottle/bag (e.g. 0.48 kg) |
| `waste_percent` | decimal | yes | filling loss % |
| `is_active` | bool | no | default 1 |
| `instructions` | text | yes | |
| `created_at` / `updated_at` | timestamps | | |

**New table: `mfg_packaging_materials`** (like recipe ingredients)

| Column | Type | Nullable | Description |
|--------|------|----------|-------------|
| `id` | int PK | no | |
| `packaging_profile_id` | int FK | no | |
| `variation_id` | int | no | bottle, cap, sticker, carton… |
| `quantity_per_container` | decimal | no | e.g. 1 cap per bottle |
| `quantity_per_carton` | decimal | yes | e.g. 1 carton per 12 bottles (if not per-container) |
| `material_role` | enum | yes | `container`, `closure`, `label`, `outer_carton`, `other` |
| `sub_unit_id` | int | yes | |

**Calculation rules:**

```
containers_entered     = user input (bottles or bags)
full_cartons           = floor(containers_entered / units_per_carton)
leftover_containers    = containers_entered % units_per_carton   -- optional: partial carton policy

bulk_consumed (kg)     = containers_entered × bulk_qty_per_container × (1 + waste_percent/100)

For each packaging material:
  if quantity_per_container is set:
    qty = containers_entered × quantity_per_container
  if quantity_per_carton is set:
    qty = full_cartons × quantity_per_carton   -- e.g. 1 carton box per full carton
```

**Example**

- User enters **120 bottles**
- `units_per_carton = 12` → **10 cartons**
- Bulk per bottle = 0.48 kg → bulk used = 120 × 0.48 = **57.6 kg**
- Cap: 120 pcs, Sticker: 120 pcs, Carton: 10 pcs

---

### 5. Two-stage production workflow

```
┌─────────────────┐     ┌──────────────────────┐     ┌─────────────────────┐
│ Stage 1: COOK   │     │ Stage 2: PACK/FILL   │     │ POS                 │
│ (existing mfg)  │ ──► │ (new)                │ ──► │ Sell cartons        │
│ Recipe → bulk   │     │ Bulk + materials     │     │ (packaged_finished) │
└─────────────────┘     └──────────────────────┘     └─────────────────────┘
        │                          │
        ▼                          ▼
   + bulk stock              + carton stock
   - ingredients             - bulk sauce
                            - bottles, caps, stickers, cartons
```

#### Stage 1 — Bulk production (existing, small changes)

- **Input:** recipe ingredients (raw)
- **Output:** `bulk_finished` variation stock (kg)
- **Transaction:** existing `production_purchase` + `production_sell` (ingredients)
- **UI:** add production mode selector at top: `bulk_only` | `bulk_for_packaging` (optional label)

No major change to current flow except validating that the recipe output variation has `product_usage_type = bulk_finished`.

#### Stage 2 — Packaging & filling (new)

- **Input:**
  - Bulk sauce (from stock or linked to stage 1 batch)
  - Packaging materials per profile
- **Output:** `packaged_finished` variation (cartons)
- **User enters:** number of **bottles** or **bags** (not cartons)
- **System shows:** computed cartons, material breakdown, bulk kg consumed
- **Transaction type (new):** `packaging_production` (or extend `production_purchase` with `mfg_stage` column)

**Link to stage 1 (nullable):**

```sql
-- on transactions table
mfg_stage ENUM('cooking', 'packaging') NULL
mfg_parent_production_purchase_id  -- already exists, reuse for packaging → cooking link
mfg_packaging_profile_id INT NULL
mfg_containers_count INT NULL      -- bottles/bags entered
mfg_cartons_count INT NULL         -- computed full cartons
mfg_container_type ENUM('bottle','bag') NULL
```

All new `mfg_*` fields should be **nullable** so existing production rows are unaffected.

---

### 6. Production screen — two options (UI)

On **Add Production** or a new **Add Packaging** screen:

#### Option A — Bulk only (sell by kg)

| Field | Description |
|-------|-------------|
| Location | Business location |
| Recipe | Cooking recipe |
| Quantity | Output in kg (or recipe unit) |
| Finalize | Adds bulk stock |

**Stock:** `+X kg` on bulk variation.

#### Option B — Pack & fill (sell by carton)

| Field | Description |
|-------|-------------|
| Location | |
| Packaging profile | Select bottle/bag + carton setup |
| Bulk source | Auto: bulk variation from profile (show available stock) |
| Containers count | User enters bottles/bags |
| **Computed:** Cartons | Read-only: `floor(containers / units_per_carton)` |
| **Computed:** Bulk used | Read-only kg |
| **Computed:** Materials | Table: item, required qty, available stock |
| Partial carton policy | Setting: block / allow / track as loose containers |
| Finalize | Deduct bulk + materials; add cartons |

**Stock:**

- `- bulk kg`
- `- bottles, caps, stickers, cartons (materials)`
- `+ N cartons` on `packaged_finished` variation

---

### 7. Partial cartons & rounding

Define business rules (configurable in Manufacturing settings):

| Policy | Behavior |
|--------|----------|
| **Strict** | Only full cartons; user must enter containers multiple of `units_per_carton` |
| **Allow partial** | Output cartons = floor(n); leftover bottles stay as `loose_containers` (optional WIP product) |
| **Round up** | Always round up carton count (use extra empty cartons from stock) |

Recommend **Strict** for v1 to avoid POS complexity.

---

### 8. Stock & costing

#### Stock movements on finalize (packaging)

| Item | Movement |
|------|----------|
| Bulk sauce | Decrease by `bulk_consumed` |
| Bottle, cap, sticker | Decrease per container |
| Empty carton | Decrease per full carton |
| Packaged product (carton SKU) | Increase by `mfg_cartons_count` |

#### Unit cost of one carton (for reporting)

```
carton_cost = (
  bulk_consumed × bulk_avg_cost
  + Σ(material_qty × material_avg_cost)
) / cartons_produced
```

Optional: update `packaged_finished` purchase price on finalize (same setting as current manufacturing).

---

### 9. POS integration

Salesman searches **carton product** (e.g. `Tomato Sauce Carton 12×500ml`):

- Stock shown = **number of cartons** in location
- Selling 1 unit = 1 carton (12 bottles inside — informational on label/invoice, not 12 separate deductions)

**Optional invoice display:**

- Product name: `Tomato Sauce (Carton 12×500ml)`
- Custom field: `Contains: 12 bottles × 500ml`

**Bulk sales (Option A):**

- If bulk variation is also sellable, POS can sell by kg separately (second variation).

**Do not** sell packaging materials (bottle, cap) from POS unless explicitly enabled — filter `product_usage_type != packaging_material` in POS product search (configurable).

---

### 10. Database changes summary (nullable-friendly)

#### `products`

```sql
ALTER TABLE products ADD COLUMN product_usage_type VARCHAR(30) NULL AFTER type;
-- or ENUM as above; NULL = legacy / unspecified
```

#### `transactions` (extend existing production)

```sql
ALTER TABLE transactions ADD COLUMN mfg_stage VARCHAR(20) NULL;           -- cooking | packaging
ALTER TABLE transactions ADD COLUMN mfg_packaging_profile_id INT UNSIGNED NULL;
ALTER TABLE transactions ADD COLUMN mfg_containers_count DECIMAL(22,4) NULL;
ALTER TABLE transactions ADD COLUMN mfg_cartons_count DECIMAL(22,4) NULL;
ALTER TABLE transactions ADD COLUMN mfg_container_type VARCHAR(20) NULL;  -- bottle | bag
```

(`mfg_parent_production_purchase_id`, `mfg_is_final`, `mfg_wasted_units` already exist.)

#### New tables

- `mfg_packaging_profiles`
- `mfg_packaging_materials`
- Optional: `mfg_packaging_profile_steps` if you need multi-step filling lines later

#### Permissions (new)

| Permission | Purpose |
|------------|---------|
| `manufacturing.access_packaging` | View/create packaging production |
| `manufacturing.manage_packaging_profiles` | CRUD packaging profiles & materials |

---

### 11. Suggested UI screens

| Screen | Route (proposed) | Purpose |
|--------|------------------|---------|
| Packaging profiles list | `/manufacturing/packaging-profiles` | Manage bottle/bag + carton setups |
| Add/edit packaging profile | `/manufacturing/packaging-profiles/create` | Bulk product, output carton SKU, materials |
| Packaging production list | `/manufacturing/packaging` | History |
| Add packaging production | `/manufacturing/packaging/create` | Option B workflow |
| Bulk production | `/manufacturing/production/create` | Option A (existing, relabeled) |

**Sidebar (under Manufacturing):**

- Recipe
- Production (bulk)
- **Packaging & Filling** *(new)*
- **Packaging Profiles** *(new)*
- Settings
- Report

---

### 12. Implementation phases

| Phase | Scope | Outcome |
|-------|--------|---------|
| **Phase 1** | `product_usage_type`, packaging profile CRUD, carton math | Master data ready |
| **Phase 2** | Packaging production transaction + stock moves | Option B works end-to-end |
| **Phase 3** | POS filter + carton stock display | Sales team can sell cartons |
| **Phase 4** | Reports (bulk vs packaged, material usage) | Operations visibility |
| **Phase 5** | Link stage 2 → stage 1 batch, lot/expiry | Full traceability |

---

### 13. Example end-to-end (sauce factory)

1. **Setup**
   - Create bulk product: `Tomato Sauce Bulk` (kg), type `bulk_finished`
   - Create materials: bottle, cap, sticker, carton — type `packaging_material`
   - Create sellable: `Tomato Sauce Carton 12×500ml` — type `packaged_finished`
   - Recipe: ingredients → bulk sauce (100 kg per batch)
   - Packaging profile: 0.48 kg/bottle, 12 bottles/carton, materials per bottle + 1 carton per 12 bottles

2. **Cook (Stage 1)**
   - Production 100 kg bulk → stock +100 kg bulk

3. **Pack (Stage 2)**
   - User enters **240 bottles**
   - System: **20 cartons**, bulk used 115.2 kg (with waste), 240 caps, 240 stickers, 20 cartons
   - Finalize → bulk −115.2 kg, materials −, packaged +20 cartons

4. **POS**
   - Search `Tomato Sauce Carton` → stock **20**
   - Sell 3 → stock **17** cartons

---

### 14. What stays unchanged

- Existing recipes and bulk production for non-sauce businesses
- All new columns **nullable** — no breaking change for current data
- Current `production_purchase` / `production_sell` flow remains valid for `mfg_stage = cooking` or `NULL`

---

---

## العربية

### 1. الهدف

توسيع **وحدة التصنيع** لدعم مصنع صلصة بمسارين بعد الطبخ/الخلط:

| المسار | الوصف | المخزون المُضاف |
|--------|--------|-----------------|
| **أ — إنتاج بالجملة** | صلصة بالكيلو (أو اللتر) وتُباع بالجملة | منتج تباين بالجملة (مثلاً: صلصة طماطم — كجم) |
| **ب — تعبئة وتغليف** | تعبئة في زجاجات/أكياس ثم كراتين | منتج تباين كرتون (مثلاً: صلصة — كرتون 12×500مل) |

المسار ب يجب أن:

- يستهلك **الصلصة بالجملة** من المخزون (أو من دفعة الإنتاج الأولى)
- يستهلك **مواد التعبئة** (زجاجة، غطاء، ستيكر، كيس، كرتون…)
- يسمح للمستخدم بإدخال **عدد الزجاجات/الأكياس**
- يحسب تلقائياً **عدد الكراتين** حسب قاعدة **عدد الوحدات لكل كرتون**
- يضيف **كمية الكراتين** لمخزون المنتج القابل للبيع
- يظهر المخزون الصحيح في **نقطة البيع** عند البحث عن SKU الكرتون

---

### 2. قيد الوحدة الحالية

اليوم، التصنيع **خطوة واحدة فقط**:

```
مكونات  →  إنتاج  →  مخزون منتج نهائي (وحدة واحدة)
```

لا يوجد: جملة مقابل معبأ، BOM للتعبئة، تحويل الكرتون، مرحلة تعبئة ثانية.

**الخلاصة:** مصنع الصلصة يحتاج **مرحلة إنتاج ثانية** و**تصنيف المنتجات**.

---

### 3. نموذج المنتجات المقترح

#### 3.1 أنواع / علامات المنتج

عمود مقترح على `products`:

```sql
product_usage_type ENUM(
  'raw_ingredient',      -- مكونات الوصفة
  'bulk_finished',       -- صلصة بالجملة (كجم)
  'packaging_material',  -- زجاجة، غطاء، ستيكر، كرتون فارغ
  'packaged_finished'    -- كرتون جاهز للبيع في POS
) NULL DEFAULT NULL;
```

| النوع | أمثلة | الاستخدام |
|-------|--------|-----------|
| `raw_ingredient` | ملح، زيت، معجون | وصفة الطبخ |
| `bulk_finished` | صلصة بالجملة | مخرجات الطبخ + مدخلات التعبئة |
| `packaging_material` | زجاجة، غطاء، ستيكر | BOM التعبئة فقط |
| `packaged_finished` | كرتون 12×500مل | **البيع في POS** |

> **`product_usage_type` يجب أن يكون nullable** حتى تبقى المنتجات الحالية تعمل بدون تعديل.

---

### 4. ملف التعبئة (BOM للتغليف)

**جدول جديد: `mfg_packaging_profiles`**

| العمود | Nullable | الوصف |
|--------|----------|--------|
| `bulk_variation_id` | لا | الصلصة المصدر (كجم) |
| `output_variation_id` | لا | منتج الكرتون المُضاف للمخزون |
| `container_type` | لا | `bottle` أو `bag` |
| `units_per_carton` | لا | مثلاً 12 زجاجة لكل كرتون |
| `bulk_qty_per_container` | لا | كجم صلصة لكل زجاجة/كيس |
| `waste_percent` | نعم | نسبة فاقد التعبئة |

**جدول: `mfg_packaging_materials`**

| العمود | الوصف |
|--------|--------|
| `variation_id` | زجاجة، غطاء، ستيكر، كرتون |
| `quantity_per_container` | لكل زجاجة (مثلاً 1 غطاء) |
| `quantity_per_carton` | لكل كرتون كامل (مثلاً 1 كرتون خارجي) |

**قواعد الحساب:**

```
الزجاجات_المُدخلة     = إدخال المستخدم
الكراتين_الكاملة      = floor(الزجاجات / وحدات_لكل_كرتون)
استهلاك_الجملة (كجم)  = الزجاجات × كجم_لكل_وحدة × (1 + نسبة_الهدر/100)

لكل مادة تعبئة:
  الكمية = الزجاجات × quantity_per_container
  أو     = الكراتين × quantity_per_carton
```

**مثال:** 120 زجاجة، 12 لكل كرتون → **10 كراتين**، 120 غطاء، 120 ستيكر، 10 كراتين خارجية.

---

### 5. سير العمل بمرحلتين

```
المرحلة 1: الطبخ     →  + مخزون جملة، − مكونات
المرحلة 2: تعبئة     →  + كراتين، − صلصة جملة − مواد تعبئة
POS                  →  بيع بالكرتون
```

#### المرحلة 1 — إنتاج الجملة (موجود حالياً)

- مخرجات: مخزون `bulk_finished` بالكجم
- معاملات: `production_purchase` + `production_sell` الحالية

#### المرحلة 2 — التعبئة والتغليف (جديد)

- مدخلات: صلصة جملة + مواد التعبئة
- مخرجات: `packaged_finished` (كراتين)
- المستخدم يُدخل: **عدد الزجاجات/الأكياس**
- النظام يعرض: الكراتين المحسوبة، المواد المطلوبة، كجم الجملة المستهلك

**حقول جديدة على `transactions` (كلها nullable):**

```sql
mfg_stage                  -- cooking | packaging
mfg_packaging_profile_id
mfg_containers_count       -- زجاجات/أكياس
mfg_cartons_count          -- كراتين محسوبة
mfg_container_type         -- bottle | bag
```

---

### 6. شاشة الإنتاج — خياران

#### الخيار أ — جملة فقط (بيع بالكجم)

- وصفة + كمية → يُضاف مخزون بالكجم

#### الخيار ب — تعبئة وتغليف (بيع بالكرتون)

- ملف تعبئة + عدد زجاجات/أكياس
- عرض تلقائي: الكراتين، الجملة المستهلكة، جدول المواد
- عند الإنهاء: خصم الجملة والمواد، إضافة الكراتين

---

### 7. سياسة الكراتين غير الكاملة

| السياسة | السلوك |
|---------|--------|
| **صارمة** | فقط كراتين كاملة (مضاعفات `units_per_carton`) |
| **جزئية** | كراتين = floor(n)؛ الباقي زجاجات مفككة (اختياري) |

يُفضّل **الصارمة** في الإصدار الأول.

---

### 8. نقطة البيع (POS)

- البحث عن **منتج الكرتون** → المخزون = **عدد الكراتين**
- بيع 1 = 1 كرتون (12 زجاجة معلومات على الفاتورة فقط)
- إخفاء مواد التعبئة من بحث POS افتراضياً (`packaging_material`)
- الجملة بالكجم قابلة للبيع إن كان المنتج `bulk_finished` مفعّل للبيع

---

### 9. ملخص تغييرات قاعدة البيانات

- `products.product_usage_type` — **nullable**
- `transactions`: `mfg_stage`, `mfg_packaging_profile_id`, `mfg_containers_count`, `mfg_cartons_count`, `mfg_container_type` — **nullable**
- جداول جديدة: `mfg_packaging_profiles`, `mfg_packaging_materials`
- صلاحيات: `manufacturing.access_packaging`, `manufacturing.manage_packaging_profiles`

---

### 10. مراحل التنفيذ

| المرحلة | المحتوى |
|---------|---------|
| **1** | تصنيف المنتجات + CRUD ملفات التعبئة |
| **2** | معاملة تعبئة + حركة مخزون |
| **3** | تكامل POS |
| **4** | تقارير |
| **5** | ربط الدفعات وتتبع الصلاحية |

---

### 11. مثال كامل (مصنع صلصة)

1. **إعداد:** منتج جملة، مواد تعبئة، منتج كرتون للبيع، وصفة طبخ، ملف تعبئة (12 زجاجة/كرتون، 0.48 كجم/زجاجة)
2. **طبخ:** إنتاج 100 كجم → مخزون جملة +100
3. **تعبئة:** إدخال 240 زجاجة → 20 كرتون، استهلاك ~115.2 كجم + مواد
4. **POS:** بحث عن الكرتون → مخزون 20، بيع 3 → يتبقى 17

---

### 12. ما يبقى دون تغيير

- وصفات وإنتاج الجملة الحالي لعملاء آخرين
- كل الحقول الجديدة **nullable** — لا كسر للبيانات الحالية

---

*Document version: 1.0 — September 2026*
*For implementation planning; not yet built in codebase.*
