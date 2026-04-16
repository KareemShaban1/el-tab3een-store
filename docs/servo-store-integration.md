# Servo ERP ↔ Storefront integration blueprint

This document describes how to connect **Servo** (ERP / back office) with this **storefront project** so that:

1. Only selected **categories** (and their products) appear in the store.
2. A dedicated **customer account** in Servo represents this store/channel; **sales orders** created from the store are booked against that account so Servo can **ship to the store (or its hub)** while the store handles **last‑mile delivery to the end customer**.

Use this as a specification when you implement Servo changes, APIs, and store-side sync/checkout logic.

---

## Step-by-step integration roadmap

Complete the steps **in order**. Cross-references point to the sections below for design detail.

### Phase A — Decisions and Servo access

1. **Confirm the business model** — Agree who invoices the end customer (store vs Servo), where Servo ships (hub / warehouse), and how the store does last-mile delivery (see §1).
2. **Fill in §10 open decisions** — Category inheritance, order granularity (per order vs batch), pricing source (retail vs wholesale), and API vs file-based integration.
3. **Obtain Servo integration capability** — API docs, test environment, credentials, rate limits, or (if no API) export formats and import templates.

### Phase B — Servo: category visibility

4. **Implement the visibility mechanism in Servo** — Add a category boolean, channel link table, or equivalent (see §2.2).
5. **Define and document rules** — Subcategory inheritance, discontinued products, empty categories (see §2.3).
6. **Expose visibility in read paths** — Ensure the API or export only returns categories (and products) allowed for this storefront; test with Postman or sample export.

### Phase C — Servo: channel customer

7. **Create the dedicated customer** — Legal name, currency, price list, payment terms (see §3.1).
8. **Add bill-to and default ship-to** — Hub address where Servo delivers for this channel (see §3.2).
9. **Record Servo identifiers** — Customer id, ship-to id (if applicable); store them for config (see §6).

### Phase D — Product and line identity

10. **Assign stable SKU / product ids** — Every sellable item on the store must map to a Servo product or variation id (see §4.3).
11. **Decide pricing rules** — Whether store display prices come from Servo, a margin on wholesale, or a separate list (see §3.3).

### Phase E — Store project: plumbing

12. **Add configuration** — `.env` / config keys: base URL, auth, channel customer id, ship-to id, kill switch (see §6).
13. **Implement a Servo client module** — HTTP client or file reader, logging, retries, and idempotency keys for writes (see §5.1, §5.3).
14. **Add persistence for integration** — e.g. `servo_product_id`, `servo_order_id`, last sync timestamps; migrations as needed (align with §7).

### Phase F — Catalog and stock sync

15. **Build catalog sync job** — Pull allowed categories and products from Servo; upsert locally or set `active_in_app` / equivalents from Servo flags (see §2.4, §7).
16. **Build stock (and price) sync job** — Schedule and throttle per Servo limits; handle errors and partial failures (see §7).
17. **Wire the storefront** — Category and product listings use synced data or scopes so hidden Servo categories never appear (see §11).

### Phase G — Orders

18. **Implement order push** — On payment success (or your chosen trigger), create a Servo sales order: channel customer, hub ship-to, lines, store order reference (see §4.2, §4.3).
19. **Handle failures** — Timeouts, duplicate prevention, admin retry or alert if Servo order creation fails after payment (see §4.4, §5.3).
20. **Optional: fulfilment status** — Webhooks or polling to update local order state when Servo ships (see §5.1, §7).

### Phase H — Security, testing, go-live

21. **Lock down secrets** — Server-side only; rotate keys; restrict Servo users/roles (see §8).
22. **Run §9 test checklist** — Visibility, order mapping, idempotency, stock edge cases.
23. **Pilot with real SKUs** — Small category set in production; monitor sync and orders before full rollout.
24. **Document runbooks** — How to disable sync, re-push an order, and reconcile mismatches.

---

## 1. Goals and business model

| Actor | Role |
|--------|------|
| **Servo** | Master catalog, inventory, purchasing, and **wholesale / B2B sale** to the store entity. |
| **Store project** | Public catalog subset, cart, payments, and **B2C fulfilment** to shoppers. |
| **End customer** | Buys on the store; may never be a Servo “customer” record if you keep a single B2B customer for the channel. |

**Typical flow**

1. Shopper places an order on the store.
2. Store creates (or aggregates into) a **Servo sales order** for customer *“Store channel – &lt;project name&gt;”* with **ship-to** = store warehouse / hub / 3PL address (or a defined default).
3. Servo picks, packs, and delivers to that address (or hands off to logistics agreed with the store).
4. Store delivers to the end customer using its own logistics or pickup points.

Adjust addresses and legal invoicing (who invoices the shopper vs who invoices the store) to match your accounting rules.

---

## 2. Category visibility in Servo

### 2.1 Requirement

Servo must be able to mark categories as **eligible for this storefront** so sync jobs and APIs only expose those categories and their products.

### 2.2 Implementation options (pick one in Servo)

1. **Boolean on category**  
   - Example fields: `visible_on_storefront`, `sync_to_channel`, `active_for_project_tab3een` (name after your real project).  
   - **Pros:** Simple, explicit. **Cons:** One flag per channel; add more flags or a pivot if you have many storefronts.

2. **Channel / sales channel entity**  
   - Many-to-many: `categories` ↔ `sales_channels` with “enabled” and optional sort order.  
   - **Pros:** Scales to multiple stores/marketplaces. **Cons:** More UI and migration work.

3. **Product-level override (optional)**  
   - Even if a category is enabled, allow `exclude_from_channel` on a product for exceptions.

### 2.3 Rules to define in Servo

- Do **subcategories** inherit the parent flag, or must each node be set explicitly?
- Are **inactive** or **discontinued** products still hidden regardless of category?
- Should **empty** categories (no sellable SKUs) be omitted from API responses?

### 2.4 Storefront parity (this project)

This codebase already uses a similar idea locally: `Category` includes `active_in_app` and `scopeActiveInApp()` (`app/Category.php`). For a Servo-driven catalog you will either:

- map Servo’s flag into that field during sync, or  
- filter in the integration layer using Servo’s API response only.

---

## 3. Dedicated customer account in Servo

### 3.1 Create a customer record

In Servo, create a **customer** (and contact) that represents the storefront operator, for example:

- **Name:** `Tab3een Store` (or your legal entity name for this channel).
- **Customer type:** B2B / wholesale buyer (if Servo supports types).
- **Default currency / price list:** Align with what the store uses for purchase-from-Servo pricing (may differ from retail prices shown to shoppers).
- **Payment terms:** As agreed (prepaid, credit limit, etc.).

### 3.2 Addresses

Maintain at least:

| Address role | Purpose |
|--------------|---------|
| **Bill-to** | Invoicing for the store entity. |
| **Ship-to (default)** | Where Servo delivers aggregated or per-order goods for the channel. |

If Servo supports **multiple ship-to** locations, you can still use one default and encode the end customer only on the store side.

### 3.3 Pricing

Decide explicitly:

- Does the store show **retail** prices while Servo charges **wholesale** per line? If yes, maintain **two price sources** or margin rules in the store.
- Or does the store use **the same** price list as the Servo order lines? Then sync prices from Servo and avoid drift.

---

## 4. Order handling: Servo order vs store order

### 4.1 Two order concepts

| Order | System | Customer of record | Contains |
|-------|--------|--------------------|----------|
| **Store order** | This project | End customer | Lines, shipping address, payment, status for the shopper. |
| **Servo order** | Servo | Channel customer (§3) | Lines Servo must fulfil; ship-to = hub; optional reference to store order id. |

### 4.2 Strategies to create Servo orders

1. **Per checkout (real-time)**  
   When payment succeeds, call Servo API (or queue a job) to create a **sales order** with lines mapped from cart, customer = channel account, `external_reference` = store order id.

2. **Batched / periodic**  
   Aggregate store orders into one or more Servo orders by cut-off time (e.g. daily). **Pros:** Fewer Servo transactions. **Cons:** Harder traceability per shopper unless line comments reference store line ids.

3. **Manual**  
   For early phases, export CSV from store and import into Servo. Low automation; good for pilot.

### 4.3 Line item mapping

For each store line you need in Servo:

- **Servo product / SKU id** (stable identifier, not only name).
- **Quantity**, **unit**, **unit price** (per your pricing policy).
- **Optional:** shopper name, phone, store order number in a **memo / note / custom field** for support (avoid putting PII in Servo if policy forbids it).

### 4.4 Stock and reservations

Define behavior when Servo shows **insufficient stock** after the shopper already paid:

- Refund / cancel workflow on the store.
- Or **oversell** rules and backorder flags in Servo, mirrored in the store UI.

---

## 5. Technical integration surface

Implement according to what Servo actually provides (adjust section titles to match Servo docs).

### 5.1 If Servo exposes an API

- **Authentication:** API keys or OAuth; scoped to the channel customer or company.
- **Read:** categories (filtered by flag), products, stock levels, prices.
- **Write:** create/update sales orders; optional: delivery notes.
- **Webhooks (if available):** shipment dispatched, invoice created, stock changed — to update store order status.

### 5.2 If Servo has no public API

- Scheduled **export/import** (CSV, XML) from Servo + file drop or SFTP.
- Or **database views** / reporting exports (only if security and Servo vendor allow it).

### 5.3 Idempotency and failures

- Use a unique **store order id** (and optionally line ids) when creating Servo orders so retries do not duplicate orders.
- Log API responses; support **reconciliation** jobs (list open store orders vs Servo).

---

## 6. Configuration checklist (store project)

Centralize in `.env` or config tables:

| Setting | Example purpose |
|---------|-----------------|
| `SERVO_API_BASE_URL` | Endpoint |
| `SERVO_API_KEY` / client credentials | Auth |
| `SERVO_CHANNEL_CUSTOMER_ID` | Servo’s internal id for the dedicated customer |
| `SERVO_DEFAULT_SHIP_TO_ID` | If Servo uses address ids |
| `SERVO_SYNC_ENABLED` | Kill switch |

Optional: map **business_id** or store instance to different Servo customers if you run multiple storefronts.

---

## 7. Data sync jobs (suggested)

1. **Catalog sync** (e.g. hourly): pull categories where `visible_on_storefront = true`, then products; upsert into local `categories` / `products` or a dedicated `servo_*` mirror schema.
2. **Stock / price sync** (e.g. every 15–60 min): update sellable qty and prices used for display or validation.
3. **Order push** (event-driven on payment): create Servo order; store `servo_order_id` on the local order.
4. **Status pull** (if webhooks missing): poll Servo for fulfilment state and map to store “shipped / delivered”.

---

## 8. Security and compliance

- Restrict API credentials to **server-side** only; never expose in the browser.
- Minimize **personal data** sent to Servo if not required for the B2B leg.
- Audit who can edit the **category visibility** flag and the **channel customer** in Servo.

---

## 9. Testing before go-live

- [ ] Category toggled off in Servo disappears from store after sync.
- [ ] Product under hidden category does not appear (including direct deep links if you block them).
- [ ] Test order creates exactly one Servo order with correct customer, ship-to, lines, and reference.
- [ ] Retry / timeout on order push does not duplicate Servo orders.
- [ ] Out-of-stock and price mismatch scenarios have defined UX and accounting outcome.

---

## 10. Open decisions (fill in when you implement)

Document your final choices here:

1. **Inheritance** of category visibility for child categories: yes / no / rules: _______________
2. **Servo order granularity:** per checkout / batched / manual: _______________
3. **Who invoices the end customer:** store only / Servo also: _______________
4. **Pricing:** retail vs wholesale source of truth: _______________
5. **Servo API vs file-based integration:** _______________

---

## 11. Related code in this repository (reference)

- Local category “app visibility” pattern: `app/Category.php` — fields `active_in_app`, scope `scopeActiveInApp`.

When Servo is the source of truth, either sync into those fields or replace storefront queries with data from your integration tables.

---

*This file is a planning artifact; update it as Servo capabilities and business rules are confirmed.*
