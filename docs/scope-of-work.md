# Scope of Work

Two versions exist. The original scope was issued before the technical audit; the
update was issued on 4 September 2026 after Commit 1 was paid and the health check
was complete. **The update is the operative document.**

Source PDFs are attached to the claude.ai project: `scopeofwork.pdf` and
`Melindas_Kitchen_Scope_Update.pdf`.

---

## Scope update, 4 September 2026 (operative)

**Melinda's Kitchen: Custom Build**

A staged build to take the site from currently functional to secure, fast, and fully
under the client's control, keeping the existing content, products, and design. Work
is delivered in stages so progress is visible at each step.

### Commit 1, R2,500, paid and underway

- Checkout compatibility fixes
- Private staging environment
- New checkout page
- Delivery at checkout (Delicate Courier)

### Remaining scope

**Stage 0: Migration and hosting control.** Move the site to hosting under Operandi's
direct control and take over the domain and DNS settings currently held by a previous
developer, then route traffic through Cloudflare for security and performance.

**Stage 1: Security and updates.** Upgrade the core software the site runs on (PHP and
WordPress), update outdated plugins, remove unused plugins and templates, and switch on
the security tools currently installed but disabled.

**Stage 2: Performance.** Add page caching and a persistent object cache, correct the
delayed background task behind late order-confirmation emails, trim the heavy settings
load, and enable proper image processing.

**Stage 3: Content and structure.** The remaining items from the client brief: merge
Home and About into one page, remove the Gallery page, move the FAQ block to Contact,
and add an occasion-based product dropdown.

### Payment schedule

| | |
|---|---|
| Commit 1 | R2,500 (paid) |
| Month 2 | R2,500 |
| Month 3 | R2,500 |
| **Total** | **R7,500** |

The remaining R5,000 covers the stages above and includes maintenance and monitoring
for the duration of the build: plugins, integrations, and performance kept in check
while work is underway.

### What is needed from the client

- Access to the domain and DNS so control can be moved from the previous developer
- Approval and billing for the Google Maps key that powers checkout address autocomplete
- Remaining content for the site updates as it is ready
- A short check-in at each stage for direction and sign-off

### Notes

- Recurring costs are separate from the build fee and carried by the client: hosting,
  payment gateway fees, Google Maps usage for address autocomplete, and Cloudflare if
  upgraded beyond the free tier.
- Maintenance covers the build period. Ongoing maintenance after handover can be
  arranged separately.
- Domain DNS is managed via Operandi's Cloudflare account for this engagement.

---

## Original scope (superseded)

The first version described five stages and included two items the update dropped or
deferred. They remain part of the commercial understanding and should be confirmed with
the client rather than assumed closed:

**Stage 3: Checkout and payments.** Integrate an international-friendly payment gateway,
add a collect / local pickup option, and integrate delivery into checkout with address
autocomplete for accurate addresses and a smoother customer experience.

**Stage 4: Delivery and optimisation.** Integrate the Delicate Courier delivery option
and monitor plugins, integrations, and performance across the build, optimising where
needed.

The original intent statement: *a staged custom build to modernise and extend the
existing store, keeping what already works and rebuilding only what needs to change.*

---

## Technical health report, 3 September 2026

Issued alongside Commit 1. Findings, with corrections from the later database analysis
marked. See HANDOVER section 3 for full detail.

### Reported as critical

| Issue | Status after audit |
|---|---|
| Outdated core software (PHP 7.4.33) | Confirmed |
| Heavy background settings load (714 settings, 3MB) | **Corrected.** 790 rows, 2.76MB, of which 2.64MB is String Locator transients. One plugin, not architecture. |
| No caching, 841ms response against 600ms recommended | Confirmed |

### Reported as recommended improvements

| Issue | Status after audit |
|---|---|
| Core WordPress update available | **Corrected.** Running WordPress 6.8.8 and WooCommerce 10.0.4, both reasonably current. |
| 14 plugins overdue, 5 unused | Confirmed |
| 6 outdated templates, 5 unused | Confirmed |
| Missing Imagick | Confirmed, installed on the new build |
| Scheduled background task running late | Confirmed. Cause of delayed order confirmation emails. Fixed with real system cron. |
| No persistent object cache | Confirmed, Redis installed on the new build |

### Reported from manual review

| Issue | Status after audit |
|---|---|
| No active security software, two tools installed but off | Confirmed |
| Domain controlled by a previous developer | Confirmed, still outstanding |

### Not in the original report, found later

- **796 orders stuck at `wc-on-hold`** continuously since December 2024. Not yet raised
  with the client.
- **Theme is Total 5.1 renamed** to `broodenbotter`, which is why it cannot update.
- **Fonts return 404.** The site has been rendering in browser-default serif for years.
- **Tables are MyISAM**, not InnoDB.

### Recommended route, as stated to the client

None of the above requires rebuilding the website. The existing content, products, and
design remain fully usable as they are. What is required is a full technical clean-up:
moving to hosting under direct control, safely updating all software, switching on
active security, and optimising performance.

This would take the site from currently functional to genuinely enterprise-grade,
secure, fast, and reliable.
