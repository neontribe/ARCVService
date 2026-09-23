# Manual test: negative Child credit values (audit F16)

A hands-on script for checking the F16 fix described in
[`docs/VOUCHER_EVALUATOR_AUDIT.md`](../VOUCHER_EVALUATOR_AUDIT.md) §F16, and for seeing the same
families **broken** on the unpatched code at `48331e9f`. Everything you need is created by one seeder;
the only thing that changes between "fixed" and "broken" is which commit is checked out.

> **Read this first: no real sponsor is affected by this fix, so the kit brings its own.** F16 is a
> *latent* bug: `BaseChildEvaluation::toReason()` threw away the `value` of any **negatively valued
> Child rule**, so such a rule showed up in the credit list but counted as **0**. No live rule set gives a
> Child rule a negative value, which is why a standard, Scottish or social-prescribing family looks
> exactly the same on both commits. The seeded sponsor carries one extra `evaluations` row –
> `ChildIsPrimarySchoolAge` as a **credit of −2** ("deduct 2 per child at primary school"), with the
> standard school-age *disqualifier* switched off – and it is only on that sponsor's families that the
> weekly total moves. Watch **"Should collect N per week"**; there are **no Reminder-box / notice
> changes** in this kit.

What the fix changed, in one line each:

| Finding | Before (`48331e9f`) | After (this branch) |
|---|---|---|
| **F16** | `BaseChildEvaluation::toReason()` kept the `value` key only when `$this->value > 0` – a Child credit of −2 was reported as a reason with **no value**, so `Valuation::getEntitlement()` summed it as **0** | `$this->value > 0 \|\| $this->value < 0` – non-zero negative values are kept and reduce the total, exactly as `BaseFamilyEvaluation` (e.g. the SP `DeductFromCarer −7`) always has |
| **F16** | `BaseChildEvaluation::test()` had no `null` guard | Short-circuits to `fail()` on a `null` value, mirroring `BaseFamilyEvaluation` (not observable in the UI – `EvaluatorFactory` drops `null` rules before they run) |
| F17 | – | Reclassified *by design*; **no code change**, nothing to test |

Time budget: about 10 minutes.

---

## 0. Before you start

* A local install with its database up (Docker via `./script/server`, Homestead, Valet, …).
* This branch checked out: `git branch --show-current` should say
  `1.20.1/hotfix-22216-evaluation-general-calculations` (or wherever this fix has landed).
* Know your Store URL (e.g. `http://arcv-store.test:8080`).
* Nothing here depends on the calendar or on any `.env` value. The children are 6 months, 2–3½ years
  and 7–10 years old – well clear of the school-start boundary – so `ARC_SCHOOL_MONTH` is irrelevant
  and you can seed on any day.
* **Keep this document open somewhere else** (another checkout, the GitHub web view, a print-out). When
  you check out `48331e9f` in step 4 this file and the seeder disappear from the working tree; the data
  they created does not.
* If you have run the Scottish (`SCOT`), notices (`NOTE`), pregnancy (`PREG`) or social-prescribing
  (`SPHH`) kits before, those families are still there on both sides of the switch. Ignore them; search
  for `NEGV` only.

> **Docker users:** prefix every `php artisan …` below with `./script/artisan …` (or run them from
> `./script/console`). `git checkout` runs on the host as usual.

## 1. Seed the scenarios

```bash
php artisan db:seed --class="Database\\Seeders\\NegativeChildCreditScenarioSeeder"
```

This creates, self-contained and idempotent (re-running first removes what it made last time):

* Sponsor **`Negative Child Credit Test Sponsor`** (shortcode `NEGV`) carrying the **standard** rule set
  (under 1 = +6, between 1 and school = +4, pregnant = +4) plus two `evaluations` rows:
  `ChildIsPrimarySchoolAge / credits / −2` and `ChildIsPrimarySchoolAge / disqualifiers / null`
  (i.e. school-age children are no longer a disqualifier, they are a deduction).
* Centre **`Negative Child Credit Test Centre`** (RVID prefix `NEGV`, individual printing).
* Store login **`arc+negv@neontribe.co.uk` / `store_pass`** (role `centre_user`, home centre = the
  centre above).
* Six families `NEGV-A` … `NEGV-F` (RVIDs `NEGV0001` … `NEGV0006`). Children's DOBs are the 1st of the
  month, computed from today.

It prints a cheat-sheet: one row per family with `Children`, `This branch` (evaluated live by the real
evaluator), `48331e9f (unpatched)` (the same credits with every negative Child value replaced by 0, which
is all the old `toReason()` did differently) and `Differs?`. Under each total the `+` lines are the
credit reasons in the form `<count>x <reason> (<vouchers>)`. Rows **B, C, D** differ in total, **E**
differs in wording only, **A, F** are controls that must be identical on both commits.

## 2. Log in and find the families

1. Log in to the Store as `arc+negv@neontribe.co.uk` / `store_pass`.
2. **Search for a family** → type `NEGV` → all six families are listed with their carer names
   (`NEGV-A Control: baby only`, `NEGV-B F16: toddler plus school-age child`, …).
3. Where the total shows:
   * **Edit family** (click a row → *Edit*): the yellow "This family" box – *Should collect **N** per
     week*. Under *(more)* the box lists the sponsor's **rules**, including
     *If child primary school age : −2 vouchers* – that line comes from the rule configuration and reads
     the same on **both** commits; it is not the observable.
   * **Voucher manager**: *This family should collect: **N** vouchers per week*.
   * **Print** (individual family sheet): *This family should collect **N** vouchers per week*, followed
     by one line per credit reason – *"**−2** vouchers because one child is primary school age"*. This
     is the only screen that itemises the deduction and is the best place to see row E.
   * **Print all** (collection sheet): the ticket icon column is the total per family.

## 3. Check the fixed behaviour (this branch)

Expected "should collect" on this branch (and what the unpatched code will say in step 5):

| Family | Children | What it demonstrates | This branch | `48331e9f` |
|---|---|---|---|---|
| NEGV-A `Control: baby only` | 6 months | `+6`, nothing negative – must not change | 6 | 6 |
| **NEGV-B** `F16: toddler plus school-age child` | 3y, 8y | `+4 −2` – the deduction now counts | **2** | 4 |
| **NEGV-C** `F16: two toddlers plus school-age child` | 2y, 3y6m, 9y | `+4 +4 −2` | **6** | 8 |
| **NEGV-D** `F16: deduction can wipe the total` | 3y, 7y, 10y | `+4 −2 −2` – down to zero, still clamped, never negative | **0** | 4 |
| NEGV-E `Wording only: school-age only child` | 9y | `−2` clamped at 0 on both; the printable reads *"−2 vouchers because …"* here and *"0 vouchers because …"* there | 0 | 0 |
| NEGV-F `Control: baby plus pregnancy` | 6 months, due in 3 months | `+6 +4` – must not change | 10 | 10 |

Reading the search results / collection sheet top to bottom (sorted by RVID) the totals should read
**6, 2, 6, 0, 0, 10**. Nothing is negative anywhere – the `max(0, …)` clamp (F6) is on both commits.

## 4. Switch to the unpatched code

```bash
git status                      # make sure you have nothing uncommitted you care about
git checkout 48331e9f
php artisan optimize:clear      # drop compiled views / route / config caches
```

Nothing else is needed: `48331e9f` and this branch have identical migrations, `composer.lock` and
`config/`, the rules are resolved by class name from the `evaluations` rows the seeder wrote, and every
total is computed live from the database on each request. The seeder and this document are gone from
the tree while you are on `48331e9f`; the `NEGV` data and your `.env` are not.

## 5. See the broken behaviour

Reload the family list (or refresh the edit / voucher-manager / print tabs):

| Family | Fixed said | Unpatched shows | Why |
|---|---|---|---|
| **NEGV-B** | 2 | **4** | the `−2` school-age credit lost its value and summed as 0 |
| **NEGV-C** | 6 | **8** | same |
| **NEGV-D** | 0 | **4** | both `−2` credits dropped; the toddler's `+4` stands alone |
| NEGV-E | 0 | 0 | total unchanged; on **Print** the itemised line now reads *"**0** vouchers because one child is primary school age"* |

The totals should now read **6, 4, 8, 4, 0, 10**.

Sanity check – these figures **must not** move: A (6), E (0), F (10). If everything changed, or
nothing did, something other than the checkout is wrong (stale cache, wrong database, wrong commit).
Note that the *(more)* rule list on the edit page still says *−2 vouchers* on the unpatched code – the
rule is configured correctly on both commits; it is the **result** that the old code discarded.

When you are done:

```bash
git checkout -
php artisan optimize:clear
```

and refresh – the totals return to 6 / 2 / 6 / 0 / 0 / 10.

## 6. Clean up (optional)

The kit owns its own sponsor, centre, user and families and touches nothing else. Re-running the seeder
replaces them; to remove them entirely, delete the sponsor `NEGV` and its centre/registrations by hand,
or re-run the seeder and then delete the `NEGV` sponsor. Vouchers a tester allocated to a `NEGV` family
are unlinked from their bundle, never deleted.

## What this kit cannot show

* **The `null` short-circuit** added to `BaseChildEvaluation::test()`. `EvaluatorFactory::generateEvaluations()`
  treats a `null`-valued `evaluations` row as "disable this rule" and never instantiates it, so no
  page can reach the new guard. It is covered by
  `tests/Unit/Services/VoucherEvaluator/EvaluatorAuditTest::testAuditF16NegativeChildCreditValueIsDropped`.
* **Why** the old code was wrong – the seeder's `48331e9f` column is a model (live credits with negative
  Child values zeroed), not the old code running. The authoritative proof is the audit test above; the
  kit itself is pinned by `tests/Unit/Seeders/NegativeChildCreditScenarioSeederTest.php`.
* Anything on a **real** sponsor – standard, Scottish and social-prescribing rule sets have no negative
  Child value, so nothing changes there by design. The family-level `DeductFromCarer −7` (SP) always
  worked because `BaseFamilyEvaluation` already kept negative values.
* **F17** – reclassified *by design* on this branch with no code change; there is nothing to observe.
* Opening `/voucher-manager` stores the current entitlement on a `Bundle` row
  (`Registration::currentBundle()`). The page still displays the live value, so the comparison is
  unaffected; the seeder deletes those bundles when it re-runs.

## Follow-up worth raising (not part of this fix)

* The family printable itemises a deduction as *"−2 vouchers because one child is primary school age"*
  under the heading *"This family should collect … vouchers per week"*. Correct, but reads oddly; if a
  sponsor ever adopts a negative Child rule the wording ("2 fewer vouchers because …") is worth a look.
  Cosmetic, out of scope here.
* A negatively valued Child credit is now a supported configuration, but there is no admin UI for
  `evaluations` rows – they are written by migrations/seeders. Any sponsor wanting a per-child deduction
  needs a data change, not a setting.
