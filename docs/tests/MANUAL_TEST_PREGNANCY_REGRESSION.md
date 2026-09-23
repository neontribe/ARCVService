# Manual test: pregnancy definition consolidation (audit F15) — no-regression kit

A hands-on script for checking that the pregnancy-rule refactor described in
[`docs/VOUCHER_EVALUATOR_AUDIT.md`](../VOUCHER_EVALUATOR_AUDIT.md) §F15 changed **nothing a Store user
can see**, by looking at the same pregnancy-centred families on this branch and on the unpatched code at
`be27ecd6`. Everything you need is created by one seeder; the only thing that changes between the two
runs is which commit is checked out.

> **Read this first: nothing changes — every row is a control.** Unlike the Scottish and notice kits,
> this branch fixes no visible bug. `be27ecd6` had two different code paths answering "is this
> household pregnant?" (audit F15); this branch makes them one. Both paths already gave the same answer
> for every family the Store can store, so every entitlement, every credit line, every Reminder box and
> the "including one pregnancy" wording must be **identical** on both commits. If you see a difference,
> that is the regression this kit exists to catch. The cheat-sheet's `Differs?` column must be blank on
> every row; a blank column is a **pass**, not a failed test.

What changed, in one line each:

| Finding | Before (`be27ecd6`) | After (this branch) |
|---|---|---|
| F15 (model) | `Family::expecting` looped over the children and kept the due date of the **last** unborn child it met; there was no `isPregnant()` | `Family::isPregnant()` = "any child with `born = false`"; `Family::expecting` = the **earliest** unborn due date |
| F15 (rule) | `FamilyIsPregnant` tested `$candidate->expecting` for truthiness | `FamilyIsPregnant` calls `$candidate->isPregnant()` |
| F15 (view) | `other_info.blade.php` printed "including one pregnancy" when `$family->expecting != null` | prints it when `$family->isPregnant()` |
| F3, F4 | open findings | reclassified **"by design"** in the audit document — no code change, nothing to see |

Because `children.dob` can never be null, the old truthy check and the new boolean agree whenever any
child is unborn. The only genuine behavioural difference — which due date `expecting` returns for a
multiple pregnancy with two different due dates — is not displayed anywhere in the Store or the exports.

Time budget: about 10 minutes.

---

## 0. Before you start

* A working local install with the database up (`./script/server`, or your usual setup) and the
  **hotfix branch** checked out (`git branch --show-current` should show the hotfix branch, not `be27ecd6`).
* Know the Store URL (locally `http://arcv-store.test` or whatever your hosts file maps to `localhost:8080`).
* `.env` needs nothing special: none of the seeded children is anywhere near their first birthday or
  school age, so `ARC_SCHOOL_MONTH` / `ARC_SCOTTISH_SCHOOL_MONTH` play no part.
* Note **today's date**. Due dates are seeded relative to it, and the seeder prints expectations for the
  day you run it, so seed and test on the same day.
* Keep this document open in your browser or a second checkout: it (and the seeder) do not exist on
  `be27ecd6`, so they vanish from the working tree while you are on the old commit.

> Running through Docker? Prefix every `php artisan …` below with `./script/artisan` instead
> (e.g. `./script/artisan db:seed --class=…`).

## 1. Seed the scenarios

```bash
php artisan db:seed --class="Database\\Seeders\\PregnancyRegressionScenarioSeeder"
```

This is **not** part of the normal `migrate --seed`; it only runs when you ask. It creates:

* Sponsor **Pregnancy Regression Test Sponsor** (`PREG`) with the **plain default rule set** – no
  sponsor overrides at all, so `FamilyIsPregnant` is worth 4 vouchers a week, the shape most
  England/Wales sponsors have in production
* Centre **Pregnancy Regression Test Centre** (RVIDs `PREG0001`…)
* Store login **`arc+preg@neontribe.co.uk` / `store_pass`**
* Five families, one per scenario, whose carer names start with `PREG-A`, `PREG-B`, … `PREG-E`

It then prints a **cheat-sheet table**. Keep that terminal visible — it is your expected-results sheet:

* **This branch** – evaluated live by the code you have checked out
* **be27ecd6 (unpatched)** – computed independently from the *old* `Family::expecting` loop, so the two
  columns are compared, not assumed equal
* **Due date (new / old)** – the internal `Family::expecting` value on each side; this is the one place
  the two commits genuinely differ (family C) and it is **not shown in the Store**
* **Differs?** – must be **blank on every row**

Lines starting `+` are credit reasons (as shown on the collection sheet), lines starting `!` would be
Reminder-box notices; no family here produces any.

Re-running the seeder is safe: it deletes and recreates its own sponsor, centre, user and families,
and reprints the sheet. Do that if the date has changed since you last seeded.

## 2. Log in and find the families

1. Open the Store and log in as `arc+preg@neontribe.co.uk` / `store_pass`.
2. Go to **Registrations** (the families list) and search for `PREG`. All five families appear,
   named `PREG-A …` to `PREG-E …` with RVIDs `PREG0001`–`PREG0005`.
3. For each family look at four things — these are exactly what the changed code feeds:
   * **"Should collect N per week"** – on the family's **Edit** page ("This family" panel) and in the
     **Voucher manager**
   * **"Has N children registered including one pregnancy"** – the second bullet of the "This family"
     panel on the **Edit** page. This line is rendered by the changed view.
   * **Credit reasons** – **Print a 4 week collection sheet for this family**: "4 vouchers because the
     family is pregnant" and, where there is a toddler, "4 vouchers because one child is between 1 and
     start of primary school age"
   * **Reminder box** – the yellow box in the voucher manager / edit page, and the **Reminder**
     paragraph on the collection sheet. It must be **empty** ("No reminders for this family") everywhere.

## 3. Check the behaviour on this branch

Tick each against the cheat-sheet. Whatever the date you should see:

| Family | Children | "Should collect" | "This family" panel | Collection-sheet credit lines |
|---|---|---|---|---|
| **PREG-A** | one unborn, due in 3 months | **4** per week | Has **1** child registered **including one pregnancy** | 4 vouchers because the family is pregnant |
| **PREG-B** | 2-year-old + unborn due in 3 months | **8** per week | Has **2** children registered **including one pregnancy** | 4 … one child is between 1 and start of primary school age; 4 … the family is pregnant |
| **PREG-C** | two unborn, due in 2 and 5 months | **4** per week | Has **2** children registered **including one pregnancy** | 4 vouchers because the family is pregnant (one line, not two) |
| **PREG-D** | one unborn whose due date was 2 months **ago** | **4** per week | Has **1** child registered **including one pregnancy** | 4 vouchers because the family is pregnant |
| PREG-E | 3-year-old only (control) | **4** per week | Has **1** child registered — **no** pregnancy wording | 4 … one child is between 1 and start of primary school age |

The Reminder box is empty for every family.

What each row is for:

* **A** – a pregnancy-only household is eligible and credited.
* **B** – the pregnancy credit stacks with a child credit.
* **C** – a multiple pregnancy still earns a single family credit. Internally `Family::expecting` now
  returns the +2-month date where the old code returned the +5-month date (see the `Due date` column on
  the cheat-sheet); nothing on screen reads that date.
* **D** – the credit survives the due date until someone marks the child as born. This is audit F3,
  which the branch reclassifies as *by design*; it behaves the same on both commits.
* **E** – proves the kit is working when there is no pregnancy at all.

## 4. Switch to the unpatched code

Leave the browser tab open. In the terminal:

```bash
git status                         # make sure you have nothing uncommitted you care about
git checkout be27ecd6              # the commit before the F15 consolidation
php artisan optimize:clear         # drops any cached config/views/routes
```

Nothing else is needed: the two commits share the same migrations and `composer.lock`, valuations are
calculated fresh on every page load, and the seeded data is untouched. The seeder class and this
document disappear from the tree on the old commit – that is fine, you already ran it. (The `SCOT`
Scottish kit and the `NOTE` notices kit *are* present on both commits; ignore their families here.)

## 5. Confirm nothing changed

Refresh the family pages (entitlement, credit reasons and the "This family" panel are recalculated on
every page load, so no reseed is needed):

| Family | This branch said | `be27ecd6` must show | Why it is the same |
|---|---|---|---|
| **PREG-A** | 4/wk, including one pregnancy | **4/wk, including one pregnancy** | one unborn child → old `expecting` is its due date (truthy) → old rule and view both say pregnant |
| **PREG-B** | 8/wk, including one pregnancy | **8/wk, including one pregnancy** | as A; the toddler credit is untouched code |
| **PREG-C** | 4/wk, including one pregnancy | **4/wk, including one pregnancy** | old `expecting` is the *last* unborn child's date, new is the *earliest* — both non-null, and neither is displayed |
| **PREG-D** | 4/wk, including one pregnancy | **4/wk, including one pregnancy** | a past due date is still a non-null date; neither commit looks at whether it has passed |
| PREG-E | 4/wk, no pregnancy wording | **4/wk, no pregnancy wording** | no unborn child → old `expecting` null, new `isPregnant()` false |

Every "Should collect" figure, every credit line, every "including one pregnancy" and every empty
Reminder box must be identical to step 3. **Any** difference is a regression — note the family and the
screen and raise it. If a figure differs on *both* commits from the cheat-sheet, something other than
the branch has changed (the date, or a reseed) — re-run the seeder and compare again.

Then come back:

```bash
git checkout -                     # back to the hotfix branch
php artisan optimize:clear
```

Refresh once more and confirm the step-3 values.

## 6. Extra: env levers

None apply. The other kits move `ARC_SCHOOL_MONTH` to reproduce other months; nothing in this kit
depends on the school month or on the day of the month, so there is no lever to pull and no month in
which the expectations change. If you want to see the kit at a different date, change the system clock
in a scratch environment and reseed; `tests/Unit/Seeders/PregnancyRegressionScenarioSeederTest.php`
already does this for September and February.

## 7. Clean up (optional)

The scenario data lives under its own sponsor/centre and does not interfere with the other dev seeds
(including the `SCOT` and `NOTE` kits). To remove it, run the seeder again (it recreates a clean set),
or drop the rows by hand: sponsor `PREG`, centre prefix `PREG`, user `arc+preg@neontribe.co.uk`, and
their registrations.

## What this script cannot show

* **The consolidation itself.** The value of F15 is that the rule, the model and the view now share one
  predicate, so they cannot drift apart again. That is a code-structure guarantee, invisible in the
  Store. Covered by `EvaluatorAuditTest::testAuditF15PregnancyDefinitionConsolidated`.
* **Earliest-due-date semantics of `Family::expecting`.** Family C's `Due date (new / old)` column on the
  cheat-sheet is the only place this is visible, and nothing in the Store, the printables or the
  exports reads the date. Covered by `FamilyModelTest::testItCanDetermineIfFamilyIsPregnant`.
* **F3 and F4 as fixes.** Both were reclassified *by design*: an unborn child keeps earning the credit
  after its due date (D) and is still subject to the ID-verification reminder if `verified` is set.
  Nothing was changed for either, so there is no "before" and "after" to compare.
* **Non-default rule sets.** Sponsors that disable `FamilyIsPregnant` (social prescribing,
  `value => null`) award 0 for the pregnancy but still avoid `FamilyHasNoEligibleChildren` through the
  unborn child; that route is unchanged on both commits and is not seeded here.

The seeder and its expectations are themselves pinned by
`tests/Unit/Seeders/PregnancyRegressionScenarioSeederTest.php`, which runs the scenarios for fixed
September and February dates and asserts that the live and the independently modelled `be27ecd6`
columns are identical on every row, while the internal due date on C differs.

## Follow-up worth raising (not part of this fix)

`other_info.blade.php` says **"including one pregnancy"** for family C, which has two unborn children
recorded. The line has always read that way (the old `expecting != null` check was equally unaware of
how many unborn children there were), and `FamilyIsPregnant` deliberately awards one credit per family
rather than per unborn child, so the entitlement is right. Only the wording is misleading; rephrasing it
to count unborn children is a separate, wording-only change.
