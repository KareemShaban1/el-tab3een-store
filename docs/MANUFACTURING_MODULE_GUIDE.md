# Manufacturing Module Guide | دليل وحدة التصنيع

---

## English

### Overview

The **Manufacturing** module lets you produce finished goods from raw materials inside Ultimate POS. You define **recipes** (bill of materials), run **production** batches, and the system updates stock automatically when production is finalized.

Typical use cases:

- Food & beverage (e.g. prepare a dish from ingredients)
- Assembly (combine parts into a finished product)
- Any business that converts raw stock into sellable products

---

### Requirements

Before using the module, make sure:

| Requirement | Description |
|-------------|-------------|
| Module installed | Manufacturing module must be installed and enabled |
| Subscription / package | `manufacturing_module` must be included in the business package (Superadmin) |
| User permissions | Role must have the correct manufacturing permissions (see below) |
| Products | Finished products and ingredient products must already exist in **Products** |

---

### Sidebar navigation

After the module is enabled and permissions are assigned, the left sidebar shows:

**Manufacturing** (industry icon)

| Menu item | URL | Permission |
|-----------|-----|------------|
| Recipe | `/manufacturing/recipe` | `manufacturing.access_recipe` |
| Production | `/manufacturing/production` | `manufacturing.access_production` |
| Add Production | `/manufacturing/production/create` | `manufacturing.access_production` |
| Settings | `/manufacturing/settings` | `manufacturing.access_production` |
| Manufacturing Report | `/manufacturing/report` | `manufacturing.access_production` |

---

### User permissions

Configure these under **User Management → Roles**:

| Permission | What it allows |
|------------|----------------|
| `manufacturing.access_recipe` | View recipe list and recipe details |
| `manufacturing.add_recipe` | Create new recipes |
| `manufacturing.edit_recipe` | Edit and delete recipes |
| `manufacturing.access_production` | View/create/edit production, settings, and reports |

---

### Workflow

#### 1. Create a recipe

A **recipe** links a finished product (output) to the **ingredients** (inputs) needed to make it.

1. Go to **Manufacturing → Recipe**
2. Click **Add**
3. Choose the **finished product** (variation)
4. Add **ingredients** with quantities
5. Optionally configure:
   - **Total output quantity** — how much the recipe produces
   - **Wastage %** — expected loss during production
   - **Extra / production cost** — fixed, percentage, or per-unit overhead
   - **Production steps** (ingredient groups) — optional multi-step instructions
   - **Recipe instructions** — notes for staff

The recipe **total cost** is calculated from ingredient purchase prices plus any production cost.

#### 2. Run production

A **production** record represents one manufacturing batch.

1. Go to **Manufacturing → Add Production**
2. Select **business location**, **recipe**, and **manufacturing date**
3. Enter the quantity to produce
4. Review ingredient lines (quantities scale with output)
5. Adjust wasted quantities if needed
6. Save as **draft** or **Finalize**

**Finalize** behavior:

- Ingredient stock is **deducted** from the selected location
- Finished product stock is **increased**
- The production record can **no longer be edited**
- If enabled in settings, the finished product **purchase price** may be updated from production cost

#### 3. Settings

Go to **Manufacturing → Settings**:

| Setting | Purpose |
|---------|---------|
| Production ref no. prefix | Prefix for auto-generated production reference numbers |
| Disable editing ingredient qty in production | Lock ingredient quantities during production entry |
| Update product purchase price on finalize | Set finished product cost from production total |

#### 4. Manufacturing report

**Manufacturing → Manufacturing Report** shows production totals and costs over a selected date range and location. Production costs can also appear in the **Profit & Loss** report.

---

### Stock impact summary

| Action | Ingredient stock | Finished product stock |
|--------|------------------|------------------------|
| Recipe created | No change | No change |
| Production saved (draft) | No change | No change |
| Production finalized | Decreased | Increased |

---

### Important notes

- A product used as an ingredient in a recipe cannot be deleted without removing it from the recipe first.
- Recipe unit cost updates when ingredient purchase prices change.
- You can bulk **update product prices** from recipes on the recipe list page.
- Production uses transaction type `production_purchase` internally.

---

### Main routes

| Route | Purpose |
|-------|---------|
| `GET /manufacturing/recipe` | Recipe list |
| `GET /manufacturing/add-ingredient` | Add/edit recipe ingredients |
| `GET /manufacturing/production` | Production list |
| `GET /manufacturing/production/create` | New production |
| `GET /manufacturing/settings` | Module settings |
| `GET /manufacturing/report` | Manufacturing report |

---

---

## العربية

### نظرة عامة

**وحدة التصنيع** تتيح لك إنتاج منتجات نهائية من مواد خام داخل نظام Ultimate POS. تقوم بتعريف **الوصفات** (قائمة المكونات)، ثم تنفّذ **عمليات الإنتاج**، ويحدّث النظام المخزون تلقائياً عند **إنهاء** الإنتاج.

أمثلة للاستخدام:

- المطاعم والمشروبات (تحضير طبق من مكونات)
- التجميع (دمج قطع في منتج نهائي)
- أي نشاط يحوّل مواد خام إلى منتجات قابلة للبيع

---

### المتطلبات

قبل استخدام الوحدة، تأكد من:

| المتطلب | الوصف |
|---------|-------|
| تثبيت الوحدة | يجب تثبيت وتفعيل وحدة التصنيع |
| الاشتراك / الباقة | يجب أن تتضمن باقة النشاط `manufacturing_module` (Superadmin) |
| صلاحيات المستخدم | يجب منح الدور الصلاحيات المناسبة (انظر أدناه) |
| المنتجات | يجب إنشاء المنتج النهائي ومكوناته مسبقاً من **المنتجات** |

---

### التنقل من القائمة الجانبية

بعد تفعيل الوحدة ومنح الصلاحيات، تظهر في القائمة الجانبية:

**تصنيع** (أيقونة المصنع)

| عنصر القائمة | الرابط | الصلاحية |
|--------------|--------|----------|
| وصفة | `/manufacturing/recipe` | `manufacturing.access_recipe` |
| إنتاج | `/manufacturing/production` | `manufacturing.access_production` |
| أضف الإنتاج | `/manufacturing/production/create` | `manufacturing.access_production` |
| الإعدادات | `/manufacturing/settings` | `manufacturing.access_production` |
| تقرير التصنيع | `/manufacturing/report` | `manufacturing.access_production` |

---

### صلاحيات المستخدم

اضبطها من **إدارة المستخدمين → الأدوار**:

| الصلاحية | ماذا تسمح |
|----------|-----------|
| `manufacturing.access_recipe` | عرض قائمة الوصفات وتفاصيلها |
| `manufacturing.add_recipe` | إنشاء وصفات جديدة |
| `manufacturing.edit_recipe` | تعديل وحذف الوصفات |
| `manufacturing.access_production` | عرض/إنشاء/تعديل الإنتاج والإعدادات والتقارير |

---

### سير العمل

#### 1. إنشاء وصفة

**الوصفة** تربط المنتج النهائي (المخرجات) بـ **المكونات** (المدخلات) اللازمة لصنعه.

1. اذهب إلى **تصنيع → وصفة**
2. اضغط **إضافة**
3. اختر **المنتج النهائي** (التباين)
4. أضف **المكونات** مع الكميات
5. يمكنك ضبط:
   - **الكمية الإجمالية للمخرجات** — كم ينتج هذا المزيج
   - **نسبة الهدر** — الفاقد المتوقع أثناء الإنتاج
   - **تكلفة إضافية / تكلفة الإنتاج** — ثابتة أو نسبة أو لكل وحدة
   - **خطوات الإنتاج** (مجموعات المكونات) — خطوات اختيارية
   - **تعليمات الوصفة** — ملاحظات للموظفين

**التكلفة الإجمالية** للوصفة تُحسب من أسعار شراء المكونات + تكلفة الإنتاج.

#### 2. تنفيذ الإنتاج

**سجل الإنتاج** يمثل دفعة تصنيع واحدة.

1. اذهب إلى **تصنيع → أضف الإنتاج**
2. اختر **الفرع** و**الوصفة** و**تاريخ التصنيع**
3. أدخل الكمية المطلوب إنتاجها
4. راجع أسطر المكونات (الكميات تتغير حسب كمية الإنتاج)
5. عدّل كميات الهدر إن لزم
6. احفظ كـ **مسودة** أو **أنهِ الإنتاج**

عند **الإنهاء**:

- يُخصم مخزون **المكونات** من الفرع المحدد
- يزيد مخزون **المنتج النهائي**
- لا يمكن **تعديل** سجل الإنتاج بعد ذلك
- إن كان مفعّلاً في الإعدادات، قد يُحدَّث **سعر شراء** المنتج النهائي حسب تكلفة الإنتاج

#### 3. الإعدادات

من **تصنيع → الإعدادات**:

| الإعداد | الغرض |
|---------|-------|
| بادئة رقم مرجع الإنتاج | بادئة لأرقام الإنتاج التلقائية |
| تعطيل تعديل كمية المكونات في الإنتاج | قفل كميات المكونات عند الإدخال |
| تحديث سعر الشراء عند الإنهاء | ضبط تكلفة المنتج النهائي من إجمالي الإنتاج |

#### 4. تقرير التصنيع

**تصنيع → تقرير التصنيع** يعرض إجماليات الإنتاج والتكاليف حسب الفترة والفرع. تكاليف الإنتاج قد تظهر أيضاً في **تقرير الأرباح والخسائر**.

---

### ملخص تأثير المخزون

| الإجراء | مخزون المكونات | مخزون المنتج النهائي |
|---------|----------------|----------------------|
| إنشاء وصفة | بدون تغيير | بدون تغيير |
| حفظ إنتاج (مسودة) | بدون تغيير | بدون تغيير |
| إنهاء الإنتاج | ينقص | يزيد |

---

### ملاحظات مهمة

- لا يمكن حذف منتج مستخدم كمكوّن في وصفة قبل إزالته من الوصفة.
- تكلفة الوحدة في الوصفة تتحدث عند تغيّر أسعار شراء المكونات.
- يمكن **تحديث أسعار المنتجات** جماعياً من صفحة الوصفات.
- الإنتاج يستخدم نوع معاملة `production_purchase` داخلياً.

---

### الروابط الرئيسية

| الرابط | الغرض |
|--------|-------|
| `GET /manufacturing/recipe` | قائمة الوصفات |
| `GET /manufacturing/add-ingredient` | إضافة/تعديل مكونات الوصفة |
| `GET /manufacturing/production` | قائمة الإنتاج |
| `GET /manufacturing/production/create` | إنتاج جديد |
| `GET /manufacturing/settings` | إعدادات الوحدة |
| `GET /manufacturing/report` | تقرير التصنيع |

---

*Last updated: September 2026*
