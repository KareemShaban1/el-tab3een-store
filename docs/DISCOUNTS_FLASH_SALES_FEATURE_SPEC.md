# Discounts, Flash Sales & Promo Codes — Feature Specification

> **Purpose:** Implementation blueprint for enhancing **discounts**, adding real **flash sales**, and adding **promo codes (coupons)**, scoped to this codebase (UltimatePOS / Tab3een store).  
> **Status:** Design doc only — no code changes yet.  
> **Related:** Existing ERP discounts (`discounts`, `discount_variations`), POS sell flow, ecommerce storefront (`StorefrontController`, `StoreCheckoutController`), customer groups, selling price groups.  
> **Note:** MyFatoorah `coupon_code` in Superadmin subscription billing is **out of scope** — this spec covers retail/POS/ecommerce store promo codes only.

---

## 1. Business goals

Support promotional pricing that can be:

| Scope | Behavior |
|-------|----------|
| **Category** | Discount applies to all products under that category (`products.category_id`, optionally sub-category) |
| **Product — all variations** | Discount applies to every variation of a product |
| **Product — selected variations** | Discount applies only to chosen variation IDs (one or many) |
| **Customer groups** | Discount can be restricted to (or excluded from) specific customer groups, or apply only when customer has a group |
| **Flash sale** | Time-boxed, high-visibility campaign (storefront + POS), with optional stock/qty caps, sold counters, and channel flags |
| **Promo code** | Customer/cashier enters a code to unlock a discount (cart-wide or scoped to eligible lines), with usage limits and audit trail |

All applied discounts and promo codes must be **persisted on sales** so reports, returns, and accounting remain auditable.

---

## 2. As-is (current codebase)

### 2.1 What already exists

UltimatePOS already has a **timed promotional discount** module used mainly by **POS / direct sell**:

| Piece | Location |
|-------|----------|
| Table `discounts` | `database/migrations/2019_02_19_103118_create_discounts_table.php` (+ SPG column migration) |
| Pivot `discount_variations` | `database/migrations/2020_09_22_121639_create_discount_variations_table.php` |
| Model | `app/Discount.php` — `variations()` belongsToMany |
| Admin CRUD | `app/Http/Controllers/DiscountController.php` + `resources/views/discount/*` |
| Match logic | `ProductUtil::getProductDiscount()` |
| Apply on POS row | `ProductUtil::getSellLineRow()` → `sale_pos/product_row.blade.php` |
| Persist on sell line | `transaction_sell_lines.discount_id`, `line_discount_type`, `line_discount_amount`, `unit_price_before_discount` |
| Invoice discount | `transactions.discount_type`, `transactions.discount_amount` via `ProductUtil::calculateInvoiceTotal()` |
| Permission | `discount.access` |

### 2.2 Current `discounts` targeting

Admin can set **either**:

1. **Brand and/or category** (`brand_id`, `category_id`), **or**
2. **Specific variations** (`variation_ids[]` → `discount_variations`)

When variations are selected, brand/category are cleared on save (`DiscountController@store` / `update`).

There is **no** first-class “product_id = all variations” target — product-wide discount today means selecting every variation manually.

### 2.3 Current match rules (`getProductDiscount`)

File: `app/Utils/ProductUtil.php` (~1540)

A discount matches when **all** of the following hold:

1. `business_id` + `location_id` match  
2. `is_active = 1`  
3. `starts_at <= now <= ends_at`  
4. Target matches:
   - brand **and** category, or  
   - brand only (`category_id` null), or  
   - category only (`brand_id` null), or  
   - variation via `discount_variations`
5. Highest `priority`, then latest record wins (**single discount**, no stacking of promos)
6. If customer has a customer group (`$is_cg = true`) → require `applicable_in_cg = 1`  
7. If a selling price group is active → `spg` is null **or** equals that group id; else require `spg` null  

**Important semantics of `applicable_in_cg` today:**

- It is a **boolean gate**: when the contact belongs to any customer group, only discounts with `applicable_in_cg = 1` are eligible.
- It does **not** store *which* customer groups. There is no `discount_customer_groups` pivot yet.

### 2.4 Pricing layers already in the system (order of application on POS)

```
1) Base variation price  (variations.default_sell_price / sell_price_inc_tax)
2) Customer group % markup/markdown  (if price_calculation_type = percentage)
   OR selling price group price     (if CG uses SPG / POS price_group selected)
3) Automatic promotional / flash    (getProductDiscount → line_discount_* + discount_id)
   — excludes discounts that require a promo code
4) Manual line discount edits       (cashier may change if permitted)
5) Promo code (if entered):
   — eligible_lines → extra/override line discount on matching lines (see §5.6)
   — cart_wide → invoice-level (transactions.discount_*)
6) Manual invoice discount          (only if no cart_wide promo code; or cashier override with permission)
7) Order tax on (subtotal − invoice discount)
```

Relevant code:

- CG % + SPG + promo: `ProductUtil::getSellLineRow()` (~2551–2610)  
- Line math (JS): `public/js/pos.js` — `calculate_discounted_unit_price`, `pos_discount`  
- Persist: `TransactionUtil::createOrUpdateSellLines()` / `editSellLine()`  
- Invoice totals: `ProductUtil::calculateInvoiceTotal()`  
- Sale create: `SellPosController@store` / update  

### 2.5 Related tables (pricing & identity)

| Table | Role |
|-------|------|
| `products` | `category_id`, `sub_category_id`, `brand_id` |
| `product_variations` | Attribute group (e.g. Color) |
| `variations` | Sellable SKU + prices |
| `categories` | Tree via `parent_id` |
| `customer_groups` | `amount`, `price_calculation_type` (`percentage` \| `selling_price_group`), `selling_price_group_id` |
| `contacts` | `customer_group_id` |
| `selling_price_groups` | Named price lists |
| `variation_group_prices` | Per-variation price for a SPG |
| `transactions` | Sale header: `discount_*`, `customer_group_id`, `selling_price_group_id`, `source`, ecommerce fields |
| `transaction_sell_lines` | Line prices + `discount_id` + line discount fields |
| `business` | `default_sales_discount` |
| `users` | `max_sale_discount` (cashier cap for invoice discount) |

### 2.6 Ecommerce gaps (critical)

| Area | Current behavior |
|------|------------------|
| Catalog prices | Uses raw `variations.sell_price_inc_tax` — ignores promos / CG |
| `GET` flash deals | `StorefrontController::flashDeals()` — **stub**: invents `old_price = price * 1.12`, fake `sold_pct` |
| Checkout | `StoreCheckoutController` builds sell lines with **no** line discount, `discount_amount => 0`, no `discount_id`, no `getProductDiscount()` |
| Coupons / promo codes | **Missing** for retail & ecommerce. Only MyFatoorah Superadmin subscription billing accepts a `coupon_code` (unrelated) |

So: **ERP discounts work in POS; storefront/flash deals and promo codes do not.**

---

## 3. Target feature requirements

### 3.1 Discount scopes (must support)

1. **Category discount**  
   - Target: `category_id` (and optionally `include_sub_categories` / explicit `sub_category_id`)  
   - Applies to every product whose `category_id` / `sub_category_id` matches  

2. **Product discount — all variations**  
   - Target: `product_id` with “all variations” mode  
   - Applies to any variation of that product without listing each variation  

3. **Product discount — one or many variations**  
   - Keep existing `discount_variations` pivot  

4. **Brand** (keep existing; optional with category)

5. **Customer group relation** (enhance)  
   - Modes (recommended):
     - `cg_mode = any` — ignore CG (applies to everyone)  
     - `cg_mode = with_group_only` — only if contact has a group (today’s `applicable_in_cg = 1`)  
     - `cg_mode = specific_groups` — only listed groups via new pivot  
     - Optional: `cg_mode = exclude_groups`  

6. **Flash sale**  
   - Same pricing engine as discount, plus campaign metadata for storefront/POS banners  

7. **Promo code (coupon)** — **required for feature completion**  
   - Unique code per business (e.g. `RAMADAN10`, `VIP50`)  
   - Linked to a `discounts` rule (amount/type, targets, CG, dates, channels)  
   - **Not auto-applied** — only when cashier/customer submits the code  
   - Application mode:
     - `cart_wide` → invoice-level (`transactions.discount_*`)  
     - `eligible_lines` → line-level on matching category/product/variations  
   - Usage limits: total uses, per-customer uses, optional single-use codes  
   - Optional: min cart subtotal, first-order only, max discount cap (for %)  

### 3.2 Channels

Discount / flash / promo code should declare where it applies:

- `pos`  
- `ecommerce` / app  
- `both` (default)

Today everything is POS-oriented; ecommerce must call the same resolver + promo-code validator.

### 3.3 Non-goals (v1 unless product asks)

- Buy-X-get-Y / BOGO cart-rule engine  
- Multi-category polymorphic targeting via unused `categorizables` table (unless product explicitly needs multi-category products first)  
- Free-shipping-only coupons as a separate type (can be phase 5: set `shipping_charges = 0` when code type is `free_shipping`)  
- Sharing MyFatoorah Superadmin subscription coupons with store sales

---

## 4. Recommended data model

### 4.1 Strategy: extend `discounts` (preferred)

Reuse the existing promo foundation and sell-line `discount_id` FK. Add columns + pivots; introduce a thin “flash sale” view or flag rather than a totally separate pricing engine.

#### 4.1.1 Alter `discounts`

```sql
-- Targeting
ALTER TABLE discounts
  ADD COLUMN product_id INT NULL AFTER category_id,
  ADD COLUMN apply_on_all_variations TINYINT(1) NOT NULL DEFAULT 0 AFTER product_id,
  ADD COLUMN include_sub_categories TINYINT(1) NOT NULL DEFAULT 0 AFTER category_id,
  ADD COLUMN sub_category_id INT NULL AFTER category_id;

-- Kind / channel
ALTER TABLE discounts
  ADD COLUMN discount_kind VARCHAR(20) NOT NULL DEFAULT 'standard'
    COMMENT 'standard|flash_sale',
  ADD COLUMN apply_in_pos TINYINT(1) NOT NULL DEFAULT 1,
  ADD COLUMN apply_in_ecommerce TINYINT(1) NOT NULL DEFAULT 1;

-- Customer group policy (replace boolean-only model over time)
ALTER TABLE discounts
  ADD COLUMN cg_mode VARCHAR(30) NOT NULL DEFAULT 'any'
    COMMENT 'any|with_group_only|specific_groups|exclude_groups';
  -- keep applicable_in_cg for backward compatibility; sync with cg_mode in code

-- Flash-sale extras
ALTER TABLE discounts
  ADD COLUMN max_redemptions_total INT NULL,
  ADD COLUMN max_redemptions_per_customer INT NULL,
  ADD COLUMN max_qty_per_order DECIMAL(22,4) NULL,
  ADD COLUMN flash_quota_qty DECIMAL(22,4) NULL COMMENT 'global units available for this flash',
  ADD COLUMN flash_sold_qty DECIMAL(22,4) NOT NULL DEFAULT 0,
  ADD COLUMN banner_title VARCHAR(191) NULL,
  ADD COLUMN banner_image VARCHAR(255) NULL,
  ADD COLUMN sort_order INT NULL DEFAULT 0;

-- Promo-code gate (automatic resolver skips these)
ALTER TABLE discounts
  ADD COLUMN requires_promo_code TINYINT(1) NOT NULL DEFAULT 0,
  ADD COLUMN promo_application VARCHAR(20) NOT NULL DEFAULT 'eligible_lines'
    COMMENT 'cart_wide|eligible_lines',
  ADD COLUMN min_cart_subtotal DECIMAL(22,4) NULL,
  ADD COLUMN max_discount_amount DECIMAL(22,4) NULL
    COMMENT 'cap for percentage codes, absolute money',
  ADD COLUMN first_order_only TINYINT(1) NOT NULL DEFAULT 0;
```

`requires_promo_code = 1` means the discount is **never** returned by the automatic catalog/POS line resolver; it is only applied after a successful promo-code validation.

#### 4.1.2 New pivot: `discount_customer_groups`

```sql
CREATE TABLE discount_customer_groups (
  discount_id INT NOT NULL,
  customer_group_id INT NOT NULL,
  PRIMARY KEY (discount_id, customer_group_id),
  INDEX (customer_group_id)
);
```

#### 4.1.3 Optional audit: `discount_redemptions` (recommended for flash + limits)

```sql
CREATE TABLE discount_redemptions (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  discount_id INT NOT NULL,
  transaction_id INT NOT NULL,
  transaction_sell_line_id INT NULL,
  contact_id INT NULL,
  variation_id INT NULL,
  qty DECIMAL(22,4) NOT NULL DEFAULT 0,
  discount_amount DECIMAL(22,4) NOT NULL DEFAULT 0,
  business_id INT NOT NULL,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,
  INDEX (discount_id),
  INDEX (transaction_id),
  INDEX (contact_id)
);
```

Use this to:

- Increment `flash_sold_qty`  
- Enforce per-customer / total redemption caps (auto discounts + promo codes)  
- Report flash / promo performance  
- Store `promo_code_id` when redemption came from a code (see column below)

Extend redemptions for promo codes:

```sql
ALTER TABLE discount_redemptions
  ADD COLUMN promo_code_id INT NULL AFTER discount_id,
  ADD COLUMN redemption_source VARCHAR(20) NOT NULL DEFAULT 'automatic'
    COMMENT 'automatic|promo_code|manual',
  ADD INDEX (promo_code_id);
```

#### 4.1.4 Keep `discount_variations`

Unchanged for “selected variations” targeting.

When `product_id` + `apply_on_all_variations = 1`, do **not** require pivot rows.

#### 4.1.5 New table: `promo_codes`

Promo codes are the **entry key**; the linked `discounts` row holds the pricing rule.

```sql
CREATE TABLE promo_codes (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  business_id INT NOT NULL,
  discount_id INT NOT NULL COMMENT 'FK discounts.id — rule to apply',
  code VARCHAR(50) NOT NULL COMMENT 'stored UPPERCASE trimmed',
  description VARCHAR(255) NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  starts_at DATETIME NULL COMMENT 'optional override; else use discount.starts_at',
  ends_at DATETIME NULL COMMENT 'optional override; else use discount.ends_at',
  max_uses_total INT NULL COMMENT 'null = unlimited',
  max_uses_per_customer INT NULL COMMENT 'null = unlimited',
  used_count INT NOT NULL DEFAULT 0,
  is_single_use TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'shortcut: max_uses_total = 1',
  created_by INT NULL,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,
  deleted_at TIMESTAMP NULL,
  UNIQUE KEY promo_codes_business_code_unique (business_id, code),
  INDEX (discount_id),
  INDEX (business_id, is_active)
);
```

**Rules:**

- One `discounts` row may have **many** codes (e.g. unique influencer codes sharing the same 10% rule).  
- Code uniqueness is per `business_id`, case-insensitive (`SAVE10` = `save10`).  
- Soft-delete so historical redemptions still resolve the code name.  
- Effective window = intersection of `promo_codes.starts_at/ends_at` (if set) and `discounts.starts_at/ends_at`.  

Optional bulk single-use codes (phase later): generate N random codes for the same `discount_id` with `is_single_use = 1`.

### 4.2 Sell / transaction records

#### Header — `transactions`

| Column | Use |
|--------|-----|
| `discount_type` / `discount_amount` | Invoice-level: manual **or** `cart_wide` promo code |
| `customer_group_id` | Snapshot of buyer’s group at sale time |
| `selling_price_group_id` | Snapshot of price list used |
| `source` | e.g. `ecommerce` vs POS — use with `apply_in_*` |

**Required additions for promo codes:**

```sql
ALTER TABLE transactions
  ADD COLUMN promo_code_id INT NULL AFTER discount_amount,
  ADD COLUMN promo_code_text VARCHAR(50) NULL AFTER promo_code_id
    COMMENT 'snapshot of code string at sale time',
  ADD COLUMN promotional_discount_total DECIMAL(22,4) NOT NULL DEFAULT 0
    COMMENT 'sum of automatic line promo savings',
  ADD COLUMN promo_code_discount_total DECIMAL(22,4) NOT NULL DEFAULT 0
    COMMENT 'money saved by promo code (line or invoice)';
```

- `promo_code_id` / `promo_code_text`: set whenever a code was successfully applied (cart-wide **or** eligible-lines).  
- Keep writing `discount_type` / `discount_amount` for cart-wide codes so `calculateInvoiceTotal()` stays unchanged.  
- For eligible-lines codes, invoice `discount_amount` may stay 0; savings live on sell lines + `promo_code_discount_total`.

#### Line — `transaction_sell_lines` (already correct pattern)

| Column | Use |
|--------|-----|
| `unit_price_before_discount` | Price after CG/SPG, **before** promo/manual line discount |
| `line_discount_type` | `fixed` \| `percentage` (from promo or cashier) |
| `line_discount_amount` | Amount/% applied |
| `unit_price` | After line discount (ex tax) |
| `unit_price_inc_tax` | After line discount (inc tax) |
| `discount_id` | FK to promotional / flash discount applied |
| `item_tax` / `tax_id` | Tax after discounted unit price |

**Do not change historical lines when a discount campaign ends** — they remain a snapshot.

Optional enhancements:

```sql
ALTER TABLE transaction_sell_lines
  ADD COLUMN discount_snapshot_name VARCHAR(191) NULL,
  ADD COLUMN discount_snapshot_amount DECIMAL(22,4) NULL,
  ADD COLUMN discount_snapshot_type VARCHAR(20) NULL;
```

Useful if admin renames/deletes a discount later.

### 4.3 Model relations to add

`app/Discount.php`:

```php
// existing
variations() // belongsToMany Variation via discount_variations

// add
product()            // belongsTo Product
category()           // belongsTo Category
brand()              // belongsTo Brands
location()           // belongsTo BusinessLocation
customerGroups()     // belongsToMany CustomerGroup via discount_customer_groups
redemptions()        // hasMany DiscountRedemption
promoCodes()         // hasMany PromoCode
```

`app/PromoCode.php` (new):

```php
discount()      // belongsTo Discount
business()      // belongsTo Business
redemptions()   // hasMany DiscountRedemption
transactions()  // hasMany Transaction
```

`app/Transaction.php`:

```php
promoCode() // belongsTo PromoCode
```

`app/TransactionSellLine.php`:

```php
discount() // belongsTo Discount
```

`app/Contact.php` (missing today):

```php
customerGroup() // belongsTo CustomerGroup
```

### 4.4 Alternative: separate `flash_sales` table

Only if flash campaigns need many products with **different** % each under one banner.

```
flash_sales (id, name, starts_at, ends_at, banner_*, channel flags, ...)
flash_sale_items (flash_sale_id, discount_id)  -- or embed price rules here
```

For Tab3een, **flag + metadata on `discounts`** is enough for v1 and reuses sell-line `discount_id`.

---

## 5. Resolution engine (how to pick the price)

### 5.1 Extract a dedicated service (recommended)

Today logic lives in `ProductUtil`. For storefront + POS + checkout, extract:

```
app/Services/Pricing/DiscountResolverService.php
```

Public API sketch:

```php
resolveForVariation(array $context): ?ResolvedDiscount
// $context:
//  business_id, location_id, variation_id, product_id,
//  category_id, sub_category_id, brand_id,
//  contact_id / customer_group_id,
//  selling_price_group_id,
//  channel: 'pos'|'ecommerce',
//  quantity (for max_qty_per_order / quota)

getCatalogPrice(Variation $v, ...): array
// returns:
//  base_price_inc_tax, final_price_inc_tax,
//  discount_id, discount_type, discount_amount,
//  old_price (for strikethrough), ends_at, is_flash
```

`ProductUtil::getProductDiscount()` should become a thin wrapper (or be replaced) calling this service so POS and ecommerce stay consistent.

### 5.2 Eligibility algorithm (v1)

```
candidates = active discounts for business + location + now in [starts_at, ends_at]
            + channel flag matches
            + requires_promo_code = 0          # code-gated rules never auto-apply
            + not exhausted (flash_sold_qty < flash_quota_qty if set)
            + customer group policy matches
            + SPG rule matches (existing spg column)

filter by target:
  - variation in discount_variations
  OR (product_id match AND apply_on_all_variations)
  OR (category_id match [+ subcategories if include_sub_categories]
      AND brand rule if brand_id set)
  OR brand-only (existing)

sort by priority DESC, starts_at DESC (or id DESC)
pick FIRST (single winner) — same as today
```

### 5.3 Customer group policy

| `cg_mode` | Rule |
|-----------|------|
| `any` | Always eligible (ignore contact group) |
| `with_group_only` | Contact must have `customer_group_id` (maps from old `applicable_in_cg = 1`) |
| `specific_groups` | Contact’s group ∈ `discount_customer_groups` |
| `exclude_groups` | Contact’s group ∉ pivot (or no group) |

**Migration of old data:**

- `applicable_in_cg = 0` → `cg_mode = any`  
- `applicable_in_cg = 1` → `cg_mode = with_group_only`  

### 5.4 Interaction with customer group **pricing** vs **promo**

Keep current sequence:

1. Apply CG % **or** SPG price → this becomes `unit_price_before_discount`  
2. Apply promotional/flash line discount on top  
3. Optionally still allow invoice discount  

Document business rule clearly in UI:  
*“Customer group price is the base; promotional discount is an additional line discount unless policy says otherwise.”*

If product later wants “promo replaces CG price”, add a setting — do not change silently.

### 5.5 Stacking policy (v1)

| Layer | Stack? |
|-------|--------|
| Multiple automatic promotional discounts on same line | **No** — highest priority wins |
| Auto line promo + manual line edit | Cashier may override amount if permission allows; keep `discount_id` if still same campaign or clear if fully overridden |
| Auto line promo + **cart_wide** promo code | **Yes** — code uses invoice `discount_*` on top of line-discounted subtotal |
| Auto line promo + **eligible_lines** promo code on same line | **No on same line** — higher priority between auto and code-linked discount wins for that line (default: promo code wins when explicitly entered) |
| Flash + standard (automatic) | Same pool; priority decides |
| Two promo codes on one sale | **No** — one code per transaction |
| Cart-wide promo code + manual invoice discount | **No** — code owns invoice discount fields; cashier override only with permission and clears `promo_code_id` |

### 5.6 Promo code validation & application

New service: `app/Services/Pricing/PromoCodeService.php`

#### 5.6.1 Validate API

```php
validate(string $code, array $context): PromoCodeValidationResult
// context: business_id, location_id, contact_id, customer_group_id,
//          channel, cart_lines[{variation_id, product_id, category_id, qty, unit_price_inc_tax}],
//          cart_subtotal, is_first_order
```

Checks (fail fast with clear error keys for UI):

1. Code exists for `business_id`, not soft-deleted, `is_active`  
2. Linked `discounts` row active, channel flags, location (null = all or match)  
3. Now inside effective date window (code ∩ discount)  
4. CG policy on linked discount  
5. `used_count < max_uses_total` (and `is_single_use`)  
6. Per-customer uses from `discount_redemptions` where `promo_code_id` + `contact_id`  
7. `min_cart_subtotal` if set  
8. `first_order_only` → contact has no prior `transactions.type=sell` + `status=final`  
9. If `promo_application = eligible_lines`: at least one cart line matches discount targets  
10. Re-check flash-style quotas on linked discount if any  

#### 5.6.2 Apply

**A) `cart_wide`**

```
invoice_discount_type   = discount.discount_type
invoice_discount_amount = discount.discount_amount
# if percentage and max_discount_amount set:
#   computed = % of cart_subtotal; clamp to max_discount_amount;
#   store as fixed amount on transaction OR keep % and clamp in calculateInvoiceTotal extension
transactions.promo_code_id / promo_code_text = code
transactions.promo_code_discount_total = computed savings
```

Prefer extending `calculateInvoiceTotal()` to accept optional `max_discount_amount` clamp when applying percentage codes.

**B) `eligible_lines`**

For each matching cart line, set/override:

```
line_discount_type / line_discount_amount from discount
discount_id = discount.id
```

Non-matching lines keep automatic promo (if any). Matching lines: **promo code wins** over automatic.

Still set header `promo_code_id` / `promo_code_text` for reporting even though invoice discount may be 0.

#### 5.6.3 Consume on finalize

Inside the same DB transaction as sale create:

1. Lock `promo_codes` row (`lockForUpdate`)  
2. Re-validate limits  
3. `used_count += 1`  
4. Insert `discount_redemptions` with `promo_code_id`, `redemption_source = promo_code`  
5. If sale fails / rolls back → no increment  

On full sell-return of a sale that used a code: decrement `used_count` (min 0) and insert reversing redemption (or mark original redeemed as reversed) — **default: restore one use** so the customer can reuse if policy allows; single-use codes become reusable only if return is approved (document in admin: “restore on return” checkbox, default **on**).

---

## 6. Handling in sales & related records

### 6.1 POS / direct sell (existing path — extend)

1. Load product row (`getSellLineRow` / `getPosProductRow`)  
2. Resolve CG + SPG prices  
3. Call `DiscountResolverService` (channel=`pos`) — **skip** `requires_promo_code = 1`  
4. Blade fills:
   - `products[n][discount_id]`  
   - `products[n][line_discount_type]`  
   - `products[n][line_discount_amount]`  
5. `pos.js` recalculates unit price / tax / totals  
6. **Promo code UI** (new): input + Apply on POS payment / discount modal  
   - AJAX `POST /promo-codes/validate` → returns application preview  
   - If `cart_wide`: fill `#discount_type` / `#discount_amount` (same as invoice discount modal) + hidden `promo_code_id`  
   - If `eligible_lines`: update matching rows’ line discount fields + `discount_id`  
7. On submit (`SellPosController`):
   - Re-validate code server-side (never trust client)  
   - `calculateInvoiceTotal($products, $tax_id, $invoiceDiscount)`  
   - `createSellTransaction(...)` — header discount, CG, SPG, **`promo_code_id`, `promo_code_text`**  
   - `createOrUpdateSellLines(...)` — writes line discount + `discount_id`  
8. After sell lines: write `discount_redemptions` + bump `flash_sold_qty` / `promo_codes.used_count`  

### 6.2 Ecommerce checkout (must be wired)

File: `app/Http/Controllers/Frontend/StoreCheckoutController.php`

Today (~267–317) sets:

```php
'unit_price_before_discount' => $unit_price, // raw sell_price_inc_tax
'unit_price' => $unit_price,
'unit_price_inc_tax' => $unit_price,
'discount_type' => 'fixed',
'discount_amount' => 0,
```

**Required changes:**

1. Resolve customer group from `$customer_contact->customer_group_id`  
2. Optionally resolve SPG from CG (`price_calculation_type = selling_price_group`)  
3. For each variation, call resolver (channel=`ecommerce`)  
4. Build sell line:

```php
[
  'product_id' => ...,
  'variation_id' => ...,
  'quantity' => ...,
  'unit_price_before_discount' => $base,      // after CG/SPG
  'line_discount_type' => $promo->discount_type ?? null,
  'line_discount_amount' => $promo->discount_amount ?? 0,
  'discount_id' => $promo->id ?? null,
  'unit_price' => $after_line_discount_ex_tax,
  'unit_price_inc_tax' => $after_line_discount_inc_tax,
  'item_tax' => ...,
  'tax_id' => ...,
]
```

5. Prefer `ProductUtil::calculateInvoiceTotal()` instead of hand-rolled `$final_total` so tax/discount math matches POS  
6. Accept `promo_code` (or `coupon_code`) in checkout payload → `PromoCodeService::validate` + apply  
7. Persist redemptions / flash sold qty / `promo_codes.used_count` after successful order  
8. Re-validate automatic promo **and** promo code still active at checkout time (catalog price may be stale)

### 6.3 Storefront catalog & flash deals

| Endpoint / method | Change |
|-------------------|--------|
| Product list / detail APIs in `StorefrontController` | Return `price`, `old_price`, `discount_id`, `discount_label`, `ends_at`, `is_flash` from resolver (**never** apply code-gated discounts here) |
| `flashDeals()` | Query `discounts` where `discount_kind = flash_sale` AND active window AND `apply_in_ecommerce` — **remove fake 1.12 markup** |
| Cart preview (if any) | Same resolver as checkout |
| **New** `POST /store/promo-codes/validate` (or under existing store API prefix) | Validate code against current cart; return savings preview + application mode |
| Checkout UI (`theme_layout` / cart) | Promo code field + apply/remove; send code on place-order |

### 6.4 Returns / edit sale

- Sell return should reverse quantity against stock; promotional `discount_id` stays on original line for audit  
- If editing an open sale, re-run resolver only when lines are re-added; do not silently change finalized historical discounts  
- On return of flash-sold units: optionally decrement `flash_sold_qty` / insert negative redemption (product decision — document default: **decrement on return of final sale**)

### 6.5 Reports & accounting touchpoints

Ensure these keep using sell-line snapshots (they already mostly do via `unit_price*`):

| Area | Notes |
|------|-------|
| Profit / sales reports | Use `unit_price` / `unit_price_inc_tax` (already discounted); invoice discount already in `final_total` |
| Discount reports | Sum by `discount_id`, join `discounts`, filter `discount_kind` |
| **Promo code reports** | Filter/group by `transactions.promo_code_id` / `promo_code_text`; uses from `promo_codes.used_count` + redemptions |
| Customer group reports | Filter via `transactions.customer_group_id` |
| Tax | Remains on post–line-discount prices; invoice tax on (subtotal − invoice discount) |
| Payments | Unchanged — based on `final_total` |
| Stock | Unchanged — qty decrease independent of discount |
| Purchase mapping (`mapPurchaseSell`) | Uses sell qty, not discount |

### 6.6 Records checklist (what to write on each sale)

| Record | Fields related to this feature |
|--------|--------------------------------|
| `transactions` | `customer_group_id`, `selling_price_group_id`, `discount_type`, `discount_amount`, **`promo_code_id`**, **`promo_code_text`**, `promotional_discount_total`, `promo_code_discount_total`, `final_total`, `total_before_tax`, `tax_amount`, `source` |
| `transaction_sell_lines` | `unit_price_before_discount`, `line_discount_*`, `discount_id`, `unit_price`, `unit_price_inc_tax`, `item_tax`, qty |
| `discount_redemptions` (new) | Link discount ↔ transaction/line/contact/qty/amount; **`promo_code_id`**, `redemption_source` |
| `promo_codes.used_count` | Increment when code applied |
| `discounts.flash_sold_qty` | Increment for flash |
| `transaction_payments` | No schema change |
| `transaction_sell_lines_purchase_lines` | No schema change |

---

## 7. Admin UX (ERP)

Extend existing Discount screens (`resources/views/discount/create.blade.php`, `edit.blade.php`):

### 7.1 Target type selector

```
[ ] Category
[ ] Brand
[ ] Product (all variations)
[ ] Variations (one or many)   ← existing multi-select
```

Mutually exclusive groups as needed (same as today: variations clear brand/category).

### 7.2 Customer groups

- CG mode dropdown  
- Multi-select customer groups when mode is specific/exclude  
- Keep checkbox label for “applicable in CG” only if still syncing legacy field  

### 7.3 Flash sale section

- Toggle `discount_kind = flash_sale`  
- Banner title/image, sort order  
- Quota / max per order / max per customer  
- Channel checkboxes: POS / Ecommerce  

### 7.4 Promo code section (on discount form **or** dedicated Promo Codes screen)

**Recommended UX:** dedicated `Promo Codes` menu under Sales / Products (same permission family), each row links to a discount rule.

Fields:

| Field | Notes |
|-------|--------|
| Code | Required, unique per business; auto-uppercase |
| Linked discount | Select existing discount (or inline-create) |
| Active | Toggle |
| Starts / ends | Optional overrides |
| Max uses total / per customer | Optional |
| Single-use | Checkbox |
| Restore on return | Checkbox (default on) |

On the **discount** form, add:

- `requires_promo_code`  
- `promo_application` (`cart_wide` \| `eligible_lines`)  
- `min_cart_subtotal`, `max_discount_amount`, `first_order_only`  

Show a warning: *“When Requires promo code is on, this discount will not auto-apply on POS/catalog.”*

### 7.5 Permissions

Reuse `discount.access`. Optional finer permissions:

- `discount.flash_sale`  
- `discount.manage_cg_rules`  
- `promo_code.access` (manage codes)  
- `promo_code.apply_in_pos` (cashier may enter codes)  

---

## 8. Files to touch (implementation map)

### Schema / models

- New migration(s) under `database/migrations/`  
- `app/Discount.php`  
- New `app/PromoCode.php`  
- New `app/DiscountRedemption.php`  
- `app/Transaction.php` — `promoCode()` + new columns  
- `app/TransactionSellLine.php` — `discount()` relation  
- `app/Contact.php` — `customerGroup()` relation  
- `app/CustomerGroup.php` — optional `discounts()`  

### Domain logic

- **New** `app/Services/Pricing/DiscountResolverService.php`  
- **New** `app/Services/Pricing/PromoCodeService.php`  
- `app/Utils/ProductUtil.php` — `getProductDiscount`, `getSellLineRow`, `calculateInvoiceTotal` (max cap for %), catalog helpers  
- `app/Utils/TransactionUtil.php` — persist `promo_code_*`; after sell lines, hook redemptions / `used_count`  
- `app/Utils/ContactUtil.php` — already exposes CG for POS; helper `isFirstOrder($contact_id)`  

### Controllers / UI

- `app/Http/Controllers/DiscountController.php`  
- **New** `app/Http/Controllers/PromoCodeController.php` (CRUD + validate endpoint for POS)  
- `resources/views/discount/*`  
- **New** `resources/views/promo_code/*`  
- Lang files (`lang/en/lang_v1.php`, etc.)  
- `routes/web.php` — promo code resource + validate  
- `public/js/pos.js` — promo code apply/remove + recalc invoice/lines  
- `resources/views/sale_pos/*` — promo code input near discount modal  
- `resources/views/sale_pos/product_row.blade.php` — flash/promo badge text if needed  

### Ecommerce

- `app/Http/Controllers/Frontend/StorefrontController.php` — catalog + **replace** `flashDeals()`  
- `app/Http/Controllers/Frontend/StoreCheckoutController.php` — resolver + promo code on sell  
- **New** storefront validate-promo endpoint (controller method or dedicated)  
- `resources/views/frontend/store/theme_layout.blade.php` (or cart partial) — code field  
- Any FE consuming flash-deals / checkout API — send `promo_code`  

### Docs / plan sync

- `docs/ECOMMERCE_ERP_PLAN.md` — Phase 3 discount computation + promo codes covered by this spec  

---

## 9. Suggested implementation phases

### Phase 1 — Data model & resolver (ERP core)

1. Migrations for new discount columns + `discount_customer_groups` + `discount_redemptions`  
2. Implement `DiscountResolverService` with parity to current `getProductDiscount` + new targets; **exclude** `requires_promo_code`  
3. Wire POS through the service (behavior unchanged for old discounts)  
4. Admin UI: product-all-variations + CG specific groups  

**Acceptance:** Creating category / product-all / variation discounts still applies correctly on POS; CG-specific works.

### Phase 2 — Sales persistence hardening

1. Snapshot fields (optional) on sell lines  
2. Redemption rows + flash qty updates on finalize  
3. Return handling for flash qty  
4. Simple admin report: sales by `discount_id`  

**Acceptance:** Closed sales show correct `discount_id` and redemption totals.

### Phase 3 — Ecommerce + real flash sales

1. Checkout uses resolver  
2. Catalog endpoints return discounted prices  
3. Replace stub `flashDeals()` with `discount_kind = flash_sale`  
4. Channel flags enforced  

**Acceptance:** App shows real old/new price and countdown; checkout totals match catalog; POS flash optional via `apply_in_pos`.

### Phase 4 — Promo codes (required for feature completion)

1. Migrations: `promo_codes` + `transactions.promo_code_*` + redemption `promo_code_id` + discount promo fields (`requires_promo_code`, `promo_application`, min cart, max cap, first order)  
2. `PromoCode` model + `PromoCodeController` CRUD  
3. `PromoCodeService::validate` / apply / consume  
4. POS: apply/remove code UI + server re-validation on `SellPosController` store/update  
5. Ecommerce: validate endpoint + checkout payload + cart UI  
6. Reports: sales by promo code; used_count correctness  
7. Return path: restore use when configured  

**Acceptance:**

- Code does not auto-appear on catalog/POS lines  
- Valid code reduces total correctly (`cart_wide` or `eligible_lines`)  
- Invalid / expired / exhausted / wrong CG / below min cart return clear errors  
- One code per sale; `promo_code_id` + snapshot text stored  
- Concurrent double-use of single-use code is blocked (row lock)  

### Phase 5 (optional) — Advanced

- Bulk generate single-use codes  
- Free-shipping code type  
- Buy-X-get-Y rules  

---

## 10. Concrete examples

### Example A — Category 20% for VIP group (ecommerce + POS)

```
discounts:
  name: "Beverages VIP 20%"
  category_id: 12
  include_sub_categories: 1
  discount_type: percentage
  discount_amount: 20
  starts_at / ends_at: campaign window
  cg_mode: specific_groups
  priority: 10
  apply_in_pos: 1
  apply_in_ecommerce: 1

discount_customer_groups:
  (discount_id, vip_group_id)
```

Sale line for a product in category 12 bought by VIP:

- Base after SPG/CG: 100  
- Line: `discount_id`, `line_discount_type=percentage`, `line_discount_amount=20`  
- `unit_price_before_discount=100`, `unit_price=80` (ex tax assumptions aside)

### Example B — Flash sale on two sizes of one product

```
discounts:
  name: "Weekend Flash - Sauce 500ml/1L"
  discount_kind: flash_sale
  product_id: 55
  apply_on_all_variations: 0
  discount_type: fixed
  discount_amount: 5
  flash_quota_qty: 100
  max_qty_per_order: 2
  apply_in_ecommerce: 1
  apply_in_pos: 0

discount_variations:
  (discount_id, variation_500ml)
  (discount_id, variation_1L)
```

Storefront `flashDeals` returns these with real `old_price` / `price` / `ends_at` / `qty_left` from quota.

### Example C — All variations of a product 15%

```
discounts:
  product_id: 88
  apply_on_all_variations: 1
  discount_type: percentage
  discount_amount: 15
  cg_mode: any
```

No rows required in `discount_variations`.

### Example D — Cart-wide promo code `SAVE10`

```
discounts:
  name: "Save 10% with code"
  requires_promo_code: 1
  promo_application: cart_wide
  discount_type: percentage
  discount_amount: 10
  max_discount_amount: 50          # cap savings at 50 currency units
  min_cart_subtotal: 100
  cg_mode: any
  apply_in_pos: 1
  apply_in_ecommerce: 1
  # no category/product target needed for cart_wide

promo_codes:
  code: SAVE10
  discount_id: <above>
  max_uses_total: 1000
  max_uses_per_customer: 1
```

Checkout cart subtotal 200 → invoice discount computed 20 → stored on `transactions.discount_type=percentage` (or fixed 20 after clamp logic), `promo_code_id` set, `promo_code_discount_total=20`.

Automatic flash/category line discounts still apply on lines first; then invoice code reduces the cart.

### Example E — Line-scoped promo code `OIL15` (category only)

```
discounts:
  name: "15% off cooking oil with code"
  requires_promo_code: 1
  promo_application: eligible_lines
  category_id: 5
  discount_type: percentage
  discount_amount: 15
  first_order_only: 0

promo_codes:
  code: OIL15
  discount_id: <above>
  max_uses_per_customer: 3
```

Cart has oil + rice:

- Oil lines get `line_discount_*` + `discount_id` from this rule (wins over any automatic on those lines)  
- Rice keeps automatic promo only  
- Header still stores `promo_code_id` / `promo_code_text=OIL15`  
- Invoice `discount_amount` stays 0 unless cashier adds a separate invoice discount  

### Example F — Single-use influencer codes sharing one rule

```
discounts:
  requires_promo_code: 1
  promo_application: cart_wide
  discount_type: fixed
  discount_amount: 25

promo_codes:  (many rows, same discount_id)
  AHMED25, SARA25, ... each is_single_use=1, max_uses_total=1
```

---

## 11. Edge cases & rules to decide before coding

| Topic | Recommendation |
|-------|------------------|
| Sub-category products when discount is on parent category | Add `include_sub_categories`; default **off** to preserve current behavior |
| Product has promo and category has promo | Priority decides (existing) |
| Discount deleted after sales exist | Soft-delete discounts **or** keep snapshot columns; never cascade-delete sell lines |
| Location null meaning “all locations” | Today `location_id` is required in UI; consider allowing null = all locations |
| Tax inclusive businesses | Continue using existing POS tax helpers (`__add_percent` / invoice calc) — do not invent a second tax path for ecommerce |
| Inactive products | Resolver should skip `is_inactive` / `not_for_selling` for catalog; POS already filters |
| Combo products | Confirm whether combo parent lines should inherit component promos (default: **discount on combo variation only**) |
| Concurrent flash oversell | Use DB transaction + check `flash_sold_qty + qty <= flash_quota_qty` at checkout |
| Promo code case / spaces | Normalize: `trim` + `strtoupper` before lookup and before storing `promo_code_text` |
| Guest checkout (no contact) | Allow codes with `max_uses_per_customer` / `first_order_only` only when contact is identified; otherwise reject with “login required” **or** count by phone if ecommerce always creates contact |
| Code removed after apply, before pay | Re-validate on finalize; clear totals if invalid |
| Partial payment / draft sale | Do not increment `used_count` until status becomes `final` |
| Cart-wide % + order tax | Keep current `calculateInvoiceTotal` order: tax on (subtotal − invoice discount) |
| Eligible-lines code with empty matching lines | Validation fails: “No eligible products in cart” |
| Stacking two codes | Reject second apply; must remove first |
| MyFatoorah subscription coupon | Ignore — different product surface |

---

## 12. Testing checklist

### Automatic discounts & flash

- [ ] Category discount applies to all products in category on POS  
- [ ] Sub-category include/exclude behaves as configured  
- [ ] Product-all-variations applies to every SKU of product  
- [ ] Selected-variation discount does not apply to other sizes  
- [ ] CG `specific_groups` blocks other groups  
- [ ] CG percentage price then promo % stacks in documented order  
- [ ] SPG + `spg` filter still works  
- [ ] Priority picks correct discount when two overlap  
- [ ] Sell line stores `discount_id` + correct unit prices  
- [ ] Invoice discount still stacks with **automatic** line promo  
- [ ] Ecommerce checkout totals match resolver  
- [ ] `flashDeals` returns only active flash rows (no fake 12%)  
- [ ] Flash quota blocks oversell  
- [ ] Return adjusts flash sold qty (if that rule is adopted)  
- [ ] Reports: filter sales by discount / flash  

### Promo codes

- [ ] Discount with `requires_promo_code=1` never auto-applies on POS row or catalog  
- [ ] Valid `cart_wide` code sets transaction `discount_*` + `promo_code_id` + `promo_code_text`  
- [ ] Percentage code respects `max_discount_amount` cap  
- [ ] `min_cart_subtotal` rejects undersized carts  
- [ ] `first_order_only` allows first final sale only  
- [ ] `eligible_lines` code discounts only matching lines  
- [ ] CG-restricted code rejected for wrong customer group  
- [ ] Exhausted `max_uses_total` / `max_uses_per_customer` / single-use blocked  
- [ ] Concurrent checkout cannot double-consume single-use code  
- [ ] `used_count` increments only on `final` sale  
- [ ] Return restores use when “restore on return” enabled  
- [ ] POS apply + remove recalculates totals  
- [ ] Ecommerce validate endpoint + checkout both re-validate server-side  
- [ ] Report: sales grouped by promo code  
- [ ] Code case-insensitive (`save10` = `SAVE10`)  

---

## 13. Summary for implementers

| Layer | Action |
|-------|--------|
| **Reuse** | `discounts`, `discount_variations`, sell-line discount columns, invoice `transactions.discount_*`, POS application path |
| **Extend** | Product-level target, CG pivot + modes, flash metadata, channel flags, redemptions, **`promo_codes` + code-gated discount fields** |
| **Extract** | `DiscountResolverService` (automatic) + `PromoCodeService` (explicit codes) for POS + storefront + checkout |
| **Fix** | Replace stub `flashDeals`; stop zeroing ecommerce discounts; stop treating coupons as Superadmin-only |
| **Persist** | Line snapshot (`unit_price_before_discount`, `line_discount_*`, `discount_id`); header `promo_code_id` / `promo_code_text` / discount totals |
| **Do not** | Auto-apply code-gated discounts; change live `variations.sell_price_inc_tax` for temporary promos; trust client-only code validation |

### Feature-complete definition

The feature is complete when Phases **1–4** are done:

1. Automatic category / product / variation discounts (+ CG rules)  
2. Persisted on sell lines & redemptions  
3. Real flash sales on ecommerce + optional POS  
4. **Promo codes** on POS and ecommerce with limits, audit, and reports  

This document is the source of truth for a future implementation PR series; implement Phase 1→4 in order unless a release needs ecommerce flash or promo codes first (still land the shared resolver/validator before wiring UI).
)
