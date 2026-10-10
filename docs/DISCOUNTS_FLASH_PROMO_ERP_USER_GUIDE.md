# Discounts, Flash Sales & Promo Codes — ERP User Guide

Guide for store staff and admins using the Tab3een ERP back office.

> Arabic version: [`docs/DISCOUNTS_FLASH_PROMO_ERP_USER_GUIDE_AR.md`](DISCOUNTS_FLASH_PROMO_ERP_USER_GUIDE_AR.md)

---

## Where to find it

| Menu | Path |
|------|------|
| **Discounts** | Sell → Discounts |
| **Promo Codes** | Sell → Promo Codes |

Permission required: `discount.access`.

After deploying, run migrations once so new columns/tables exist.

---

## 1. Automatic discounts (no code)

Use **Sell → Discounts → Add**.

### Target (what gets the discount)

Choose one approach:

| Target | How |
|--------|-----|
| **Category** | Select Category (optionally Brand). Enable **Include sub-categories** if needed. |
| **Product — all variations** | Select **Product (all variations)** and keep **Apply on all variations** checked. |
| **Specific variations** | Use the Products multi-select. Brand/category fields hide when variations are selected. |

### Amount & schedule

- **Discount type:** Fixed or Percentage  
- **Discount amount:** Value or %  
- **Starts at / Ends at:** Campaign window  
- **Priority:** Higher number wins when two discounts overlap  
- **Location:** Branch where it applies  
- **Selling price group:** Optional filter (or All)

### Channels

- **Apply in POS** — cashiers see it when adding products  
- **Apply in online store** — catalog/checkout can use it  

### Customer groups

| Rule | Meaning |
|------|---------|
| All customers | Everyone |
| Only customers with a group | Contact must have a customer group |
| Specific groups only | Pick groups in the multi-select |
| Exclude selected groups | Everyone except those groups |

### Discount kind

- **Standard** — normal timed promo  
- **Flash sale** — shown in store Flash Deals; optional **Flash sale quota**, **Max qty per order**, **Banner title**, **Sort order**

### Important

Leave **Requires promo code** **unchecked** for automatic discounts.  
If checked, the discount will **not** auto-apply on POS or the store.

---

## 2. Flash sales

1. Create a discount with **Discount kind = Flash sale**.  
2. Enable **Apply in online store**.  
3. Set dates, amount, and product/category/variation targets.  
4. Optionally set **Flash sale quota** (total units) and **Banner title**.  
5. The storefront **Flash deals** section loads these campaigns (real old/new prices).  

POS can also use flash discounts if **Apply in POS** is on.

---

## 3. Promo codes

Promo codes need **two** steps:

### Step A — Discount rule (code-gated)

1. **Sell → Discounts → Add**  
2. Configure amount, dates, targets (or leave empty for cart-wide)  
3. Enable **Requires promo code**  
4. Set **Promo application**:
   - **Entire cart** — invoice-level discount  
   - **Eligible products only** — only matching lines  
5. Optional: min cart subtotal, max discount cap, first order only, customer group rules, channels  
6. Save  

### Step B — Code string

1. **Sell → Promo Codes → Add**  
2. Enter **Code** (stored uppercase, e.g. `SAVE10`)  
3. Link to the discount from Step A  
4. Set usage limits:
   - Max total uses  
   - Max uses per customer  
   - Single use  
   - Restore use on return (recommended on)  
5. Optional start/end override on the code itself  
6. Save  

One discount rule can have **many** codes (e.g. influencer codes).

---

## 4. Using discounts on POS

1. Open POS and add products — **automatic** discounts appear as line discounts when eligible.  
2. To apply a **promo code**:
   - Open the **Edit discount** modal (pencil next to Discount)  
   - Enter the code → **Apply**  
   - Confirm Update  
3. Finalize the sale — the system re-validates the code and stores it on the invoice.  

---

## 5. What is saved on each sale

| Place | Data |
|-------|------|
| Sale lines | Unit price before discount, line discount, `discount_id`, snapshot name |
| Sale header | Invoice discount; `promo_code` id & text; promo savings total |
| Redemptions | Audit of automatic / code uses; flash sold qty increases |

Do **not** change product default sell prices for temporary campaigns — use discounts instead.

---

## 6. Quick recipes

### Category 20% this week (POS + store)

Discount: Category X, 20%, dates, channels both, Requires promo code = off.

### Weekend flash on two sizes

Discount kind = Flash sale, select two variations, fixed amount off, ecommerce on, set quota.

### Code `SAVE10` = 10% off cart (min 100)

1. Discount: Requires promo code, application = Entire cart, 10%, min cart 100, optional max cap.  
2. Promo code: `SAVE10` linked to that discount.

### Code `OIL15` = 15% on oil category only

1. Discount: Category Oil, Requires promo code, application = Eligible products only, 15%.  
2. Promo code: `OIL15`.

---

## 7. Troubleshooting

| Issue | Check |
|-------|--------|
| Discount not on POS | Active? Dates? Location? Apply in POS? Requires promo code off? Customer group rule? |
| Flash deals empty | Kind = Flash sale? Ecommerce on? In date window? Stock > 0? Quota not exhausted? |
| Promo code rejected | Linked discount requires code? Active? Limits? Min cart? Channel? Customer group? |
| Code not in Promo Codes dropdown | Discount must have **Requires promo code** enabled |

---

## 8. Related docs

- Technical spec: `docs/DISCOUNTS_FLASH_SALES_FEATURE_SPEC.md`  
- Customer guide: `docs/DISCOUNTS_FLASH_PROMO_CUSTOMER_GUIDE.md`
