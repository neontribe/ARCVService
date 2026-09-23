# Manual test: social-prescribing evaluator fixes (audit F5–F7)

A hands-on script for checking the three social-prescribing (SP) entitlement fixes described in
[`docs/VOUCHER_EVALUATOR_AUDIT.md`](../VOUCHER_EVALUATOR_AUDIT.md) §F5, §F6 and §F7, and for seeing the
same households **broken** on the unpatched code at `b0abd058`. Everything you need is created by one
seeder; the only thing that changes between "fixed" and "broken" is which commit is checked out.

> **Read this first: only social-prescribing weekly totals move, and the interesting ones belong to
> households that have *left*.** All three fixes are `credits` rules that exist only in SP rule sets
> (`HouseholdExists`, `HouseholdMember`, `DeductFromCarer`). A standard or Scottish family shows no
> difference at all, and there are **no Reminder-box / notice changes** in this kit. Departed households
> are hidden from the normal registration list, so you will read them from the **centre CSV export**
> (the "Export … Registrations" tile on the dashboard) or by ticking "Show households who have left" and
> editing a URL. If you only look at the active-family screens you will see exactly one change (row A).

What the fix changed, in one line each:

| Finding | Before (`b0abd058`) | After (this branch) |
|---|---|---|
| **F7** | `DeductFromCarer` tested `$candidate->has('children')`, which is a query builder and therefore always truthy – the carer deduction (−7) fired on every household, even with **no member records** | `children->isNotEmpty()` – the deduction only fires when there is somebody to deduct for |
| **F6** | `Valuation::getEntitlement()` returned the raw `array_sum` – a departed household with no members was told to collect **−7** vouchers per week | `max(0, $total)` – the total is clamped at 0 |
| **F5** | `HouseholdMember` read `leaving_on` / `rejoin_on` off the **Child** (always null there), so every member record kept earning +7 after the household had left | Uses `$candidate->family->status()` – departed members earn nothing until the household rejoins |
| – | `HouseholdExists` had the same predicate inline | Uses the shared `Family::status()` helper (refactor, no visible change) |

Time budget: about 15 minutes.

---

## 0. Before you start

* A local install with its database up (Docker via `./script/server`, Homestead, Valet, …).
* This branch checked out: `git branch --show-current` should say
  `1.20.1/hotfix-22216-evaluation-social-prescribing-problems` (or wherever this fix has landed).
* Know your Store URL (e.g. `http://arcv-store.test:8080`).
* Nothing here depends on the calendar or on any `.env` value – no school-month lever, no date to note.
  Seed on any day.
* **Keep this document open somewhere else** (another checkout, the GitHub web view, a print-out). When
  you check out `b0abd058` in step 4 this file and the seeder disappear from the working tree; the data
  they created does not.
* If you have run the Scottish (`SCOT`), notices (`NOTE`) or pregnancy (`PREG`) kits before, those
  families are still there on both sides of the switch. Ignore them; search for `SPHH` only.

> **Docker users:** prefix every `php artisan …` below with `./script/artisan …` (or run them from
> `./script/console`). `git checkout` runs on the host as usual.

## 1. Seed the scenarios

```bash
php artisan db:seed --class="Database\\Seeders\\SocialPrescribingScenarioSeeder"
```

This creates, self-contained and idempotent (re-running first removes what it made last time):

* Sponsor **`SP Evaluator Test Sponsor`** (shortcode `SPHH`, `programme = 1`) carrying the default SP
  rule set from `SponsorsSeeder::socialPrescribingOverrides()`: `HouseholdExists +10`,
  `HouseholdMember +7` per member record, `DeductFromCarer −7`.
* Centre **`SP Evaluator Test Centre`** (RVID prefix `SPHH`, individual printing).
* Store login **`arc+sphh@neontribe.co.uk` / `store_pass`** (role `centre_user`, download rights, home
  centre = the centre above).
* Seven households `SPHH-A` … `SPHH-G` (RVIDs `SPHH0001` … `SPHH0007`). Member records are adults
  (DOB 1 Jan 2000) so no child-age rule can ever fire; "left" households have `leaving_on` three months
  ago; the rejoined one also has `rejoin_on` one month ago.

It prints a cheat-sheet: one row per household with `Members`, `Status`, `This branch` (evaluated live
by the real evaluator), `b0abd058 (unpatched)` (modelled from the old arithmetic) and `Differs?`. Under
each total the `+` lines are the credit reasons in the form `<count>x <entity>|<reason> (<vouchers>)`;
`Family|` with an empty reason is the carer deduction. Rows **A, D, F** differ; **B, C, E, G** are
controls that must be identical on both commits.

## 2. Log in and find the households

1. Log in to the Store as `arc+sphh@neontribe.co.uk` / `store_pass`. The dashboard uses the
   social-prescribing wording ("household", "participant").
2. **Primary check – the centre CSV export.** On the dashboard click **Export SP Evaluator Test Centre
   Registrations** (the export tile; it only appears because the seeded user has download rights).
   Open the CSV: it lists **all seven** households, including the departed ones, with an
   **`Entitlement`** column. That column is the live evaluator total and is what to compare across
   commits. (There is no `Active` column for a `centre_user`; use the carer name – it says `active`,
   `left` or `left then rejoined` – and the seeder's `Status` column instead.)
3. **Active households A, B, C, G** can also be checked on screen: **Search for a household** → type
   `SPHH` → open one → the yellow "Should collect **N** per week" box; or **Voucher manager** →
   "This household should collect: **N** vouchers per week".
4. **Departed households D, E, F** are hidden from the search list by default. To see one on screen:
   tick **Show households who have left**, click **View** on the row, then in the address bar replace
   `/view` with `/voucher-manager` (or `/print`). Both routes are only gated on your right to read the
   registration, so they work for departed households. `/view` itself does **not** print a total.

## 3. Check the fixed behaviour (this branch)

Expected `Entitlement` (CSV) / "should collect" (screen) on this branch:

| Household | Members | Status | What it demonstrates | This branch | `b0abd058` |
|---|---|---|---|---|---|
| **SPHH-A** `F7: active, no members` | 0 | active | No carer deduction when there is nobody to deduct for | **10** | 3 |
| SPHH-B `Control: active, 1 member` | 1 | active | `10 + 7 − 7` – must not change | 10 | 10 |
| SPHH-C `Control: active, 2 members` | 2 | active | `10 + 14 − 7` – must not change | 17 | 17 |
| **SPHH-D** `F6+F7: left, no members` | 0 | left | Clamp at zero; old code goes **negative** | **0** | **−7** |
| SPHH-E `Control: left, 1 member` | 1 | left | Old `7 − 7` happens to be 0 too – must not change | 0 | 0 |
| **SPHH-F** `F5: left, 2 members` | 2 | left | Departed members no longer credited | **0** | 7 |
| SPHH-G `Control: left then rejoined, 2 members` | 2 | rejoined | Rejoin path unchanged – must not change | 17 | 17 |

Reading the CSV top to bottom (it is sorted by RVID) the `Entitlement` column should read
**10, 10, 17, 0, 0, 0, 17**. Nothing is negative anywhere.

## 4. Switch to the unpatched code

```bash
git status                      # make sure you have nothing uncommitted you care about
git checkout b0abd058
php artisan optimize:clear      # drop compiled views / route / config caches
```

Nothing else is needed: `b0abd058` and this branch have identical migrations, `composer.lock` and
`config/`, the rules are resolved by class name from the `evaluations` rows the seeder wrote, and every
total is computed live from the database on each request. The seeder and this document are gone from
the tree while you are on `b0abd058`; the `SPHH` data and your `.env` are not.

## 5. See the broken behaviour

Reload the dashboard, download the centre CSV again (or refresh the voucher-manager tabs):

| Household | Fixed said | Unpatched shows | Why |
|---|---|---|---|
| **SPHH-A** | 10 | **3** | F7 – `−7` carer deduction applied to a household with no member records |
| **SPHH-D** | 0 | **−7** | F7 deducts for a non-existent carer, F6 lets the negative through – a **minus sign in the CSV** |
| **SPHH-F** | 0 | **7** | F5 – two departed members still earn `+14`, minus the carer `−7` |

The CSV `Entitlement` column should now read **3, 10, 17, −7, 0, 7, 17**.

Sanity check – these figures **must not** move: B (10), C (17), E (0), G (17). If everything changed,
or nothing did, something other than the checkout is wrong (stale cache, wrong database, wrong commit).
Row E is a genuine no-difference case even though its ingredients changed (the old code showed
`+7 member, −7 carer`; the new code shows only the `−7` carer line on `/voucher-manager`).

When you are done:

```bash
git checkout -
php artisan optimize:clear
```

and refresh – the totals return to 10 / 10 / 17 / 0 / 0 / 0 / 17.

## 6. Clean up (optional)

The kit owns its own sponsor, centre, user and households and touches nothing else. Re-running the
seeder replaces them; to remove them entirely, delete the sponsor `SPHH` and its centre/registrations
by hand, or re-run the seeder and then delete the `SPHH` sponsor. Vouchers a tester allocated to an
`SPHH` household are unlinked from their bundle, never deleted.

## What this kit cannot show

* **Why** the old code was wrong – the seeder's `b0abd058` column is an arithmetic model of the three
  changed rules, not the old code running. The authoritative proof is
  `tests/Unit/Services/VoucherEvaluator/EvaluatorAuditTest.php`
  (`testAuditF5DepartedHouseholdStillCreditsMembers`, `testAuditF5RejoinedHouseholdCreditsMembersAgain`,
  `testAuditF6EntitlementCanGoNegative`, `testAuditF7DeductFromCarerRequiresChildren`) together with
  `SPVoucherEvaluatorTest`; the kit itself is pinned by `tests/Unit/Seeders/SocialPrescribingScenarioSeederTest.php`.
* Anything on a **standard or Scottish** sponsor – these rules are not in those rule sets, so nothing
  changes there by design.
* The **`/view`** page for a departed household prints no total (`view_registration.blade.php`), which
  is why step 2 sends you to the CSV or to `/voucher-manager`.
* The CSV **`Active`** column – it is stripped for every role except `foodmatters_user`. Give the seeded
  user that role by hand if you want it (you then also get "Export Social Prescription Registrations",
  the all-SP export).
* Opening `/voucher-manager` on this branch stores the current entitlement on a `Bundle` row
  (`Registration::currentBundle()`). The page still displays the live value, so the comparison is
  unaffected; the seeder deletes those bundles when it re-runs.
* Notice/Reminder-box behaviour (F1/F2 – see `MANUAL_TEST_EVALUATOR_NOTICES.md`) and the Scottish rules
  (F8–F12 – see `MANUAL_TEST_SCOTTISH_EVALUATOR.md`) are untouched by this branch.

## Follow-up worth raising (not part of this fix)

* **Row A is a live behaviour change for real households, not just a bug demo.** Every *active* SP
  household with no member records (a participant registered on their own) moves from **3** to **10**
  vouchers per week on the default SP rule set (Lambeth: 6 → 8). The audit's §4.2 "entitlement-affecting
  – needs a sponsor decision" table records F5 (departed households, totals go **down**) but **not** this
  F7 effect on *active* households (totals go **up**). 10 is what the rule set has always said such a
  household should get, but sponsors will see the increase on their next collection sheet and should
  hear about it before release rather than from a centre – worth adding to §4.2 and to the sponsor note.
* The carer deduction has an **empty reason string** (`DeductFromCarer::$reason = ''`), so wherever
  credit reasons are itemised (the seeder cheat-sheet shows it as `Family|`, the family printable as
  "… because Family") there is nothing after the entity name. Cosmetic, pre-existing, out of scope here.
