# Manual test: evaluator notice fixes (audit F1–F2)

A hands-on script for checking the two Reminder-box fixes described in
[`docs/VOUCHER_EVALUATOR_AUDIT.md`](../VOUCHER_EVALUATOR_AUDIT.md) §F1–F2, and for seeing the same
families **broken** on the unpatched code at `1f019ce7`. Everything you need is created by one seeder;
the only thing that changes between "fixed" and "broken" is which commit is checked out.

> **Read this first: nothing here changes a voucher total.** Both fixes only affect the yellow
> **Reminder** box. Every family collects exactly the same number of vouchers on both commits; if you
> are watching "Should collect N per week" you will see no difference and conclude the fix is a no-op.
> Watch the Reminder box.

What the fix changed, in one line each:

| Finding | Before (`1f019ce7`) | After (this branch) |
|---|---|---|
| F1 | `Valuation::getNoticeReasons()` read a bucket called `disqualifications` that never existed, so the reason a child or family was disqualified never reached the screen – families dropped to 0/wk in silence | Reads `disqualifiers`; the Reminder box now says e.g. "A child is primary school age" |
| F2 | "Almost 1" / "almost primary school age" used `(int) diffInMonths(target) <= 1`, so the reminder appeared **two months** before the event and never *in* the event month | Month-boundary comparison: reminder in the month before **and** the month of the event only |
| – | `dob` mutated in place in `Child::calcFutureMonthYear()` / `IsUnderYears` | `->copy()` first (hardening, no visible change) |

Time budget: about 15 minutes for the core checks (steps 1–5), 10 more for the env-driven extra.

---

## 0. Before you start

* A working local install with the database up (`./script/server`, or your usual setup) and the
  **fixed branch** checked out (`git branch --show-current` should show the hotfix branch, not `1f019ce7`).
* Know the Store URL (locally `http://arcv-store.test` or whatever your hosts file maps to `localhost:8080`).
* `.env` has `ARC_SCHOOL_MONTH=9` (the production England/Wales value). Confirm with
  `grep ARC_SCHOOL_MONTH .env`. (`ARC_SCOTTISH_SCHOOL_MONTH` is irrelevant to this script.)
* Note **today's date**. The F2 windows are calendar-dependent; the seeder prints expectations for the
  day you run it, so seed and test on the same day.
* Keep this document open in your browser or a second checkout: it (and the seeder) do not exist on
  `1f019ce7`, so they vanish from the working tree while you are on the old commit.

> Running through Docker? Prefix every `php artisan …` below with `./script/artisan` instead
> (e.g. `./script/artisan db:seed --class=…`).

## 1. Seed the scenarios

```bash
php artisan db:seed --class="Database\\Seeders\\EvaluatorNoticeScenarioSeeder"
```

This is **not** part of the normal `migrate --seed`; it only runs when you ask. It creates:

* Sponsor **Evaluator Notices Test Sponsor** (`NOTE`) with the **plain default rule set** – no
  sponsor overrides at all, the shape most England/Wales sponsors have in production
* Centre **Evaluator Notices Test Centre** (RVIDs `NOTE0001`…)
* Store login **`arc+note@neontribe.co.uk` / `store_pass`**
* Five families, one per scenario, whose carer names start with `NOTE-A`, `NOTE-B`, … `NOTE-E`

It then prints a **cheat-sheet table**. Keep that terminal visible — it is your expected-results sheet:

* **This branch** – evaluated live by the code you have checked out (i.e. the fix)
* **1f019ce7 (unpatched)** – what the old code will show for today's date and school month
* **Differs?** – `YES` marks the families worth switching branches for

Lines starting `+` are credit reasons (as shown on the collection sheet), lines starting `!` are
Reminder-box notices. Only the `!` lines ever differ between the two columns.

Re-running the seeder is safe: it deletes and recreates its own sponsor, centre, user and families,
and reprints the sheet. Do that whenever you change the date or `ARC_SCHOOL_MONTH`.

## 2. Log in and find the families

1. Open the Store and log in as `arc+note@neontribe.co.uk` / `store_pass`.
2. Go to **Registrations** (the families list) and search for `NOTE`. All five families appear,
   named `NOTE-A …` to `NOTE-E …` with RVIDs `NOTE0001`–`NOTE0005`.
3. The **Reminder** box is what you are testing. It is rendered from the same notice list in four places;
   check at least the first two:
   * **Voucher manager** (the family's *Vouchers* button) – the yellow bell-icon box under the family
     details, listing lines such as "A child is primary school age"
   * **Edit** (the family page) – the same box in the right-hand "other info" column
   * **Print a 4 week collection sheet for this family** – a **Reminder** paragraph under the voucher reasons
   * **Print collection sheets for all families** – a **!** icon against families that have any notice
4. "Should collect N per week" must match the cheat-sheet **and must not change** when you switch commits.

## 3. Check the fixed behaviour (default school month = 9)

Tick each against the cheat-sheet. In a **September** run you should see:

| Family | What it demonstrates | Expected Reminder box on the fix | Entitlement (both commits) |
|---|---|---|---|
| **NOTE-A** | F1 – 6-year-old disqualified, toddler still credited | **"A child is primary school age"** | 4/wk |
| **NOTE-B** | F1 – only child at school, family gets 0 **and is told why** | **"A child is primary school age"** | 0/wk |
| **NOTE-C** | F2 start-month direction + F1 – child who started school on 1 September this year, plus a 6-month-old | **"A child is almost primary school age"** *and* **"A child is primary school age"** | 6/wk |
| NOTE-D | control – 11-month-old | "A child is almost 1 year old" (identical on both commits) | 6/wk |
| NOTE-E | control – 3-year-old plus pregnancy | **empty** – no Reminder box at all | 8/wk |

Testing in another month? NOTE-A, NOTE-B, NOTE-D and NOTE-E read the same all year. NOTE-C's
"almost primary school age" only appears in **August and September** on the fix (and in **July and
August** on the old code – that is the F2 bug, see step 6); from October to July the fix shows nothing
or just the F1 line. Trust the cheat-sheet's `Differs?` column over this table.

## 4. Switch to the unpatched code

Leave the browser tab open. In the terminal:

```bash
git status                         # make sure you have nothing uncommitted you care about
git checkout 1f019ce7              # the commit before the notice fixes
php artisan optimize:clear         # drops any cached config/views/routes
```

Nothing else is needed: the two commits share the same migrations and `composer.lock`, valuations are
calculated fresh on every page load, and the seeded data is untouched. The seeder class and this
document disappear from the tree on the old commit – that is fine, you already ran it. (The Scottish
kit from `MANUAL_TEST_SCOTTISH_EVALUATOR.md` *is* present on both commits; ignore it here.)

## 5. See the broken behaviour

Refresh the family pages (the Reminder box is recalculated on every page load, so no reseed is needed):

| Family | Fixed said | Unpatched shows | Why |
|---|---|---|---|
| **NOTE-A** | "A child is primary school age" | **no Reminder box**, still 4/wk | F1 – the disqualifier reason is thrown away before the view |
| **NOTE-B** | "A child is primary school age" | **0/wk and nothing else** | F1 – the audit's "zero with no explanation", the most user-hostile symptom |
| **NOTE-C** | almost primary school age + primary school age | **no Reminder box**, still 6/wk | F2 – old window closed once 1 September passed; F1 hides the disqualifier |
| NOTE-D | almost 1 year old | almost 1 year old | control – unchanged |
| NOTE-E | nothing | nothing | control – unchanged |

Every "Should collect" figure must be identical to step 3. If a *number* changed, something other
than the branch differs (date, `.env`, or a reseed) – check the cheat-sheet.

Then come back:

```bash
git checkout -                     # back to the fixed branch
php artisan optimize:clear
```

Refresh and confirm the Reminder boxes are back to the step-3 values.

## 6. Extra: the "two months early" direction of F2 (needs an env change)

Step 5 showed the old code staying quiet *in* the start month. The audit's headline complaint is the
opposite half: the old reminder appearing **two months before** school starts. In July with the
production setting you would see that on NOTE-C directly; in any other month (except November and
December, see below) you can reproduce it by moving the school month, because both commits read
`ARC_SCHOOL_MONTH` live:

1. In `.env` set `ARC_SCHOOL_MONTH=<this month + 2>` (e.g. `11` in September).
2. `php artisan config:clear` (both branches read the same `.env`, so set it once).
3. Re-run the seeder on the fixed branch to reprint the cheat-sheet for the new month.
4. **NOTE-C** on the fix: **no** "almost primary school age" line (school is two months away).
   Note the entitlement is now **10/wk** – the child is no longer "at school" under the moved date. That
   shift is the same on both commits and is *not* part of what is under test.
5. Switch to `1f019ce7` (step 4), refresh NOTE-C: **"A child is almost primary school age"** – two
   months early. Entitlement still 10/wk.
6. Switch back (end of step 5).
7. Control: set `ARC_SCHOOL_MONTH=<this month + 1>` (e.g. `10` in September), `config:clear`, reseed.
   **Both** commits now show "almost primary school age" on NOTE-C – the fix did not simply remove
   the reminder, it moved it to the right months.
8. Put `.env` back to `ARC_SCHOOL_MONTH=9`, `php artisan config:clear`, and re-run the seeder one last
   time so the sheet matches production settings again.

`<this month + 2>` has no valid value in **November or December** (it would wrap into next year);
in those months skip this section and rely on the unit tests listed below.

## 7. Clean up (optional)

The scenario data lives under its own sponsor/centre and does not interfere with the other dev
seeds (including the `SCOT` Scottish kit). To remove it, run the seeder again (it recreates a clean
set), or drop the rows by hand: sponsor `NOTE`, centre prefix `NOTE`, user `arc+note@neontribe.co.uk`,
and their registrations.

## What this script cannot show

* **The "almost 1 year old" half of F2.** The Store stores every DOB as the 1st of the month, and for
  those DOBs the old and new arithmetic give the same answer on every day the app is used (they only
  diverge when evaluated on the 29th–31st against a short target month). NOTE-D is therefore a
  *control*, not a demonstration. Covered by `tests/Unit/Specifications/IsAlmostYearsTest.php`.
* **Dec → Jan year wrap** of the start-date window – needs the real clock in December with a January
  school month. Covered by `IsAlmostStartDateTest::it_handles_year_wrapping_for_custom_january_start_month`.
* **The `->copy()` hardening** in `Child::calcFutureMonthYear()` and `IsUnderYears` – `dob` is a
  `datetime` cast, so Eloquent already handed each rule a fresh object; no observable change. Covered by
  `tests/Unit/Services/VoucherEvaluator/EvaluatorAuditTest.php`.
* **Family-level and secondary-school disqualifier reasons** – only present in sponsor-specific rule
  sets (Scottish, SK/Southwark), not the default one seeded here. The Scottish kit shows the family
  one on `SCOT-A` etc.

The seeder and its expectations are themselves pinned by
`tests/Unit/Seeders/EvaluatorNoticeScenarioSeederTest.php`, which runs the scenarios for fixed
September and July dates with `ARC_SCHOOL_MONTH` at 9, 10 and 11 and asserts the fixed/unpatched
notices above.

## Follow-up worth raising (not part of this fix)

Now that F1 makes disqualifier reasons visible, some of them read badly because they were written to
follow the credit sentence "N vouchers because one …". In particular `FamilyHasNoEligibleChildren`
renders as "A family has no child under primary school age then children of primary school age get"
and `ChildIsSecondarySchoolAge` as "A child is secondary school age they get". Rewording those
`$reason` strings is a separate, wording-only change.
