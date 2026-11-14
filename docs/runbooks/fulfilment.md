# Runbook: collection and delivery

Covers roadmap step 1.4. Rules come from Melinda's own published copy, not assumptions:

| Rule | Source |
|---|---|
| 5 days lead time | Shop page: "We require 5 days lead time" |
| Tuesday to Saturday, closed Mondays, Sundays and public holidays | Shop page |
| Not more than 9 months ahead | FAQ: "We don't take orders for more than 9 months in advance" |
| Collection from the studio in Faerie Glen | FAQ |
| Wedding cakes are always delivered | FAQ: "All wedding cakes are delivered by Melinda's Kitchen" |
| Phone number required | Every order gets a confirmation call (existing mk-core rule) |

## What is built

**In `mk-core` 1.1 (survives any theme or the headless rebuild):**

- `Fulfilment_Rules`: pure PHP date logic. Lead time, open weekdays, SA public holidays (including Easter and Sunday-to-Monday moves) and a "fully booked" list. Unit tested: `php wordpress/plugins/mk-core/tests/test-fulfilment-rules.php`.
- A required **collection or delivery date** at checkout. It is registered through the Additional Checkout Fields API for the checkout block and the Store API, and through `woocommerce_checkout_fields` for the classic checkout. Both store it in the same order meta key, `_wc_other/mk-core/fulfilment-date`.
- **Delivery only** checkbox on every product (Product data > Shipping). When one of these is in the cart, collection is removed from the shipping options.
- **Settings Melinda edits herself:** WooCommerce > Settings > Shipping > Collection & delivery. Lead time, how far ahead, and fully booked dates (one per line).
- Phone required on Store API checkouts (classic was already enforced).
- HPOS and Cart/Checkout blocks compatibility declared.

**Pickup itself** is WooCommerce's native Local Pickup, not custom code. It works with the checkout block and the Store API, so it carries straight into the headless build.

## Setup on staging

```bash
bash scripts/deploy.sh staging                        # from the repo, on the tailnet
ssh root@100.66.152.34
su - melindaskitchen-staging
cd htdocs/staging.melindaskitchen.co.za
wp eval-file ~/melinda-ops/scripts/setup-fulfilment.php
wp eval-file ~/melinda-ops/scripts/setup-fulfilment.php apply address="<studio street address>"
```

## The block checkout test (gate for the headless build)

Roadmap Phase 1, readiness rule 2. On **staging only**:

1. Edit the Checkout page. Keep a copy of the current content (the editor keeps a revision). If it uses the `[melindas_kitchen_checkout]` shortcode from Commit 1, note which features that shortcode provides first.
2. Replace the content with the WooCommerce **Checkout** block. Do the same for Cart with the **Cart** block.
3. Run these and record the result:

| Test | Expected |
|---|---|
| Cupcakes, choose collection | Studio location shown, date list starts 5 days out, no Mon/Sun/holidays |
| Simplistic Wedding Cake | Collection not offered |
| Product with add-ons (Simplistic Wedding Cake, Flavour) | Add-on lines and percentage prices appear in cart and order |
| Empty phone | Blocked with "Please add a mobile number" |
| Delicate Courier | Courier rate appears for a Pretoria address |
| Order in admin | Date shows under order details and in the confirmation email |

If any row fails under blocks, it also fails headless. Fix it here first.

## Still open

- **Commit 1 checkout plugin source.** The old site's checkout page uses `[melindas_kitchen_checkout]`. Its source is not in this repo. It holds the "standard collection times" the shop page refers to, plus the Delicate Courier integration. Add it to `wordpress/plugins/` so collection time slots can be moved into `mk-core` next to the date rules.
- **Delicate Courier.** Install and configure on staging from the Commit 1 build, then run the block checkout test above.
- **Questions for Melinda:**
  - Is the Macaron Tower delivery only? It is in Wedding Cakes, so the script flags it.
  - Which street address should be shown for collection, or should it only be sent in the confirmation?
  - What are the collection time slots?
