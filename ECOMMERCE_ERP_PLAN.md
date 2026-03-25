# E-commerce + ERP Integration Plan

## Goal
Build an online storefront for authenticated customers to browse products and place orders, while managing the full lifecycle inside the existing ERP (customers, orders, refunds, fulfillment, and reporting).

This document is a **plan only** (no implementation).

## Current Codebase Snapshot (What Already Exists)
- `routes/web.php` already contains core ERP flows for contacts, products, sells, payments, shipping updates, and sell returns.
- `resources/views/welcome.blade.php` is currently a static marketing/storefront page with static cart JS data; it is not connected to ERP data yet.
- `config/auth.php` already defines a `customer` guard using the `contacts` provider.
- `app/Contact.php` is `Authenticatable`, so contact records can be used for customer authentication flows.
- `app/Transaction.php` already supports sales + return linkage and useful e-commerce fields (`source`, `sub_status`, `shipping_status`, `order_addresses`, `return_parent_id`, etc.).
- `app/Http/Controllers/ProductController.php` has `getProductsApi()`.
- `app/Http/Controllers/ContactController.php` has `postCustomersApi()`.
- `app/Http/Controllers/SellPosController.php` has `placeOrdersApi()` (stock check + sell creation pattern).
- `app/Http/Controllers/BusinessController.php` has `getEcomSettings()`.
- `app/Http/Middleware/EcomApi.php` and commented e-commerce API routes in `routes/web.php` indicate a partially prepared e-commerce integration path.
- `Modules/Ecommerce` is not present in this repository, while other modules are installed (`Crm`, `Essentials`, etc.).

## High-Level Strategy
Use existing ERP entities as source of truth:
- **Customers**: `contacts`
- **Orders**: `transactions` (`type = sell`)
- **Order Lines**: `transaction_sell_lines`
- **Payments**: `transaction_payments`
- **Refunds**: `transactions` (`type = sell_return`, linked by `return_parent_id`)

Avoid creating duplicate order/customer systems. Extend only where gaps exist for storefront UX and customer portal behavior.

## Delivery Phases

## Phase 0 - Discovery and Decisions (Implementation Baseline)

### Objective
Lock all business and technical decisions so development can start without rework.

### Implementation Tasks
- Finalize e-commerce path:
  - **Decision:** Use built-in storefront + existing APIs in this repo.
  - **Reason:** `Modules/Ecommerce` is not installed in this codebase.
- Finalize customer identity model:
  - **Decision:** Use `contacts` as the canonical e-commerce customer record.
  - **Auth model:** `customer` guard (`config/auth.php`) with `contacts` provider.
  - **Rule:** No duplicate customer identity in a second table unless required later.
- Finalize online order lifecycle map:
  - `transactions.type = sell`
  - `transactions.status = final|draft` (as currently supported)
  - `transactions.sub_status = ecommerce_new|ecommerce_confirmed|packed|shipped|delivered|cancelled`
  - `transactions.shipping_status = pending|packed|shipped|delivered|returned`
  - `transactions.payment_status = due|partial|paid`
  - `transactions.source = ecommerce`
- Finalize inventory and payment policy:
  - Stock is validated at order submit.
  - Order is created regardless of immediate payment; payment status tracks settlement.
  - If payment callback fails, order remains unpaid and visible in ERP queue.
- Finalize refund policy for phase alignment:
  - Refund execution remains via `sell_return` linked by `return_parent_id`.
  - Approval role required before refund is processed.

### Deliverables
- Decision record section in this file approved by business owner.
- Status dictionary approved by operations/finance.
- Scope lock for Phase 1-4 signed off.

### Acceptance Criteria
- No open architectural question blocks Phase 1 coding.
- Every e-commerce order state maps to existing ERP fields.

## Phase 1 - Storefront Foundation (Build the Public Shop)

### Objective
Convert the current static storefront into a dynamic catalog backed by ERP data.

### Backend Implementation Tasks
- Add storefront route group (public routes):
  - `/store` (home/catalog)
  - `/store/products`
  - `/store/products/{slug_or_id}`
  - `/store/categories/{id}`
  - `/store/brands/{id}`
  - `/store/search`
- Add storefront controllers (new) that read from existing models:
  - `Product`, `Variation`, `Category`, `Brands`, `VariationLocationDetails`
- Implement product query rules:
  - Active only (`products.is_inactive = 0`)
  - Sellable only (`not_for_selling = 0`)
  - Location-aware stock and price
  - Optional category/brand/price filters
- Reuse existing utility methods where possible (`ProductController` and product utilities).
- Add storefront config resolver from `business.ecom_settings`:
  - Hero slides
  - Featured categories
  - Contact/footer fields

### Frontend Implementation Tasks
- Replace static product cards in `welcome.blade.php` with server-driven or API-driven rendering.
- Remove hardcoded in-page cart bootstrap data and move to dynamic flow.
- Add pagination + filter UI:
  - Category
  - Brand
  - Price range
  - In-stock only

### Data/Schema Tasks
- Prefer no schema change in this phase.
- If product slug needed, add a migration for `products.slug` with unique index per business.

### Deliverables
- Dynamic storefront pages live with real products and stock visibility.
- Filters and search functional with production-like catalog data.

### Acceptance Criteria
- Public user can browse products without ERP login.
- Catalog reflects ERP product availability and prices by selected location.

## Phase 2 - Customer Account and Authentication

### Objective
Enable authenticated online customers to manage profile and view their orders.

### Backend Implementation Tasks
- Implement customer auth routes and controllers (separate from ERP staff auth):
  - Register, login, logout, forgot/reset password
  - Use `Auth::guard('customer')`
- Ensure `contacts` supports auth-required fields:
  - email uniqueness per business
  - secure password storage
  - remember token/reset flow support
- Create customer account route group with `auth:customer` middleware:
  - `/store/account/profile`
  - `/store/account/addresses`
  - `/store/account/orders`
  - `/store/account/orders/{id}`
- Build order-history queries from `transactions`:
  - `type = sell`
  - `source = ecommerce`
  - `contact_id = current_customer_id`

### Frontend Implementation Tasks
- Add customer auth pages:
  - register
  - login
  - forgot/reset password
- Add account pages:
  - profile form
  - shipping/billing addresses
  - order list + order details timeline

### Data/Schema Tasks
- If needed, add migration for customer auth fields on `contacts` (only missing ones):
  - `password`
  - `remember_token`
- Add `password_resets_contacts` migration if not present.

### Deliverables
- End-to-end customer registration and login.
- Customer can maintain profile and view own e-commerce orders.

### Acceptance Criteria
- Unauthenticated user cannot access checkout/account pages.
- Authenticated customer only sees their own orders.

## Phase 3 - Cart, Checkout, and Order Placement

### Objective
Create a robust checkout pipeline that writes valid ERP sales transactions.

### Backend Implementation Tasks
- Build cart endpoints/services:
  - add/update/remove item
  - recalculate totals server-side
  - validate each variation against stock/location
- Build checkout endpoint:
  - validate customer and addresses
  - compute subtotal/tax/discount/shipping
  - create sale transaction + lines + payment state in a DB transaction
- Reuse existing ERP sale creation patterns (from `SellPosController`/transaction utils):
  - `transactions.type = sell`
  - `transactions.source = ecommerce`
  - `transactions.order_addresses` JSON
  - `transaction_sell_lines` for line items
  - `transaction_payments` for captured payments or pending records
- Add idempotency key support on order submit to prevent duplicates.
- Add confirmation endpoint/page with invoice/order reference.

### Payment Tasks
- Implement initial payment mode set:
  - Cash on delivery (immediate)
  - Online gateway callback-ready flow (if enabled)
- Keep payment mismatch handling:
  - order exists with `payment_status = due` until confirmed

### Frontend Implementation Tasks
- Build cart drawer/page using real API data.
- Build checkout UI:
  - address selection/input
  - shipping method
  - payment method
  - order review summary
- Build order confirmation screen and email/SMS trigger.

### Deliverables
- Customer can complete checkout and generate ERP sell transaction.
- ERP users can see new online orders in existing sales views.

### Acceptance Criteria
- No order can be placed with invalid variation or insufficient stock.
- Created order totals and line totals match ERP calculations.

## Phase 4 - ERP Backoffice for E-commerce Ops

### Objective
Enable ERP operations team to manage e-commerce orders without leaving existing workflows.

### Backend Implementation Tasks
- Extend existing sell/order listing with e-commerce filters:
  - `source = ecommerce`
  - `payment_status`
  - `shipping_status`
  - `sub_status`
  - date/location/customer filters
- Add operational actions on order details:
  - confirm payment
  - mark packed
  - mark shipped + tracking number
  - mark delivered
  - cancel order (if allowed by policy)
- Add controlled transition rules:
  - Prevent invalid jumps (e.g., delivered -> packed)
  - Log all status changes with actor/timestamp
- Add fulfillment note fields and staff assignment if needed.

### Customer Management Tasks
- Reuse `contacts` and `customer-group` screens.
- Add online behavior filters (computed/report level):
  - new customer
  - repeat customer
  - high-value customer
  - inactive since N days

### Reporting Tasks
- Add e-commerce reporting views (or report filters):
  - daily online sales
  - top online products/categories
  - order status funnel
  - payment pending aging
  - refund ratio

### Deliverables
- Operations can process online order lifecycle inside ERP.
- Finance/sales can track online sales KPIs and pending actions.

### Acceptance Criteria
- Every e-commerce order can be tracked from creation to delivery in ERP UI.
- Status, payment, and shipment transitions are auditable and role-protected.

## Phase 5 - Refunds, Returns, and Cancellations
- Use existing sell return model for refunds:
  - Create `sell_return` transactions linked to original sell (`return_parent_id`)
  - Support full and partial return lines
- Define refund workflow states:
  - Requested -> Approved -> Refunded/Rejected
- Keep stock/accounting consistency:
  - Returned quantities back to stock per business rule
  - Reverse payment entries where needed
  - Ensure ledger visibility for the customer and accounting team.

## Phase 6 - Payments and Security Hardening
- Payment integration plan (based on configured gateways in project dependencies).
- Add webhook handling for asynchronous payment confirmation and retry safety.
- Add idempotency to order placement to prevent duplicate orders on retries.
- Security controls:
  - CSRF/session hardening
  - Rate limiting on auth and checkout
  - Strict validation for addresses, pricing, and stock reconciliation
  - Role/permission checks for all ERP actions.

## Phase 7 - Testing, Launch, and Monitoring
- Test matrix:
  - Guest browse, authenticated checkout, failed payment, stock race, partial refund, full refund
  - Multi-location stock edge cases
  - Tax/discount/shipping correctness
- UAT with business team on realistic order scenarios.
- Soft launch:
  - Limited products/customers first
  - Monitor errors, payment callbacks, and order sync behavior
- Production monitoring:
  - Failed checkout rate
  - Payment mismatch alerts
  - Oversell detection.

## Data and Schema Guidance
Prefer reusing existing schema first. Add new tables/columns only when needed for:
- Cart persistence (if required across devices)
- Customer sessions/tokens for storefront-specific auth behavior
- Refund request metadata (if workflow states are not sufficient in current structures)
- Shipment tracking identifiers (if not represented already).

Any schema changes should be additive and backward compatible with current ERP operations.

## API and Routing Plan (Suggested)
- Storefront Web:
  - Public: home, catalog, product details, search
  - Auth customer: cart, checkout, account, orders
- Internal JSON API (same app):
  - Product listing/detail for storefront frontend
  - Cart/checkout endpoints
  - Order submission endpoint (maps to ERP transaction creation)
  - Order history for authenticated customers
- ERP Admin:
  - Reuse current sell/sell-return/contact/report controllers where possible
  - Add e-commerce filters and statuses, not duplicate modules.

## Permissions and Roles
- Add/verify permissions for:
  - E-commerce order view/update
  - Refund approval
  - Refund execution
  - Customer data management
  - Shipping update actions
- Keep separation of duties:
  - Sales/fulfillment can update shipping
  - Finance/admin approves refunds.

## Recommended Implementation Order (Practical Sequence)
1. Finalize decisions in Phase 0 (especially auth model + status model).
2. Dynamic catalog (Phase 1) with no checkout yet.
3. Customer auth + account (Phase 2).
4. Checkout -> order creation in ERP (Phase 3).
5. Backoffice order operations + filters (Phase 4).
6. Refund workflows (Phase 5).
7. Security hardening, tests, and launch (Phases 6-7).

## Risks and Mitigations
- **Risk:** Mixed customer identity model (`contacts` vs `users user_customer`).
  - **Mitigation:** Decide one canonical login model early and keep explicit mapping.
- **Risk:** Overselling under concurrency.
  - **Mitigation:** Transactional stock checks + locking/idempotent order submit.
- **Risk:** Status sprawl between ERP and storefront.
  - **Mitigation:** Define one status dictionary and central mapping layer.
- **Risk:** Refund/accounting mismatch.
  - **Mitigation:** Force refunds through `sell_return` linked transactions and audited approvals.

## Definition of Done (Program Level)
- Customers can register/login, browse real products, place orders, and track order status.
- ERP team can manage online customers, process/fulfill orders, and issue full/partial refunds.
- Online orders and refunds are fully reflected in ERP reporting, stock, and payment records.
- Critical flows are covered by automated and UAT test scenarios.

