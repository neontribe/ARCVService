# Manual test: Scottish voucher evaluator fixes (audit F8–F12)

A hands-on script for checking the Scottish school-age fixes described in
[`docs/VOUCHER_EVALUATOR_AUDIT.md`](../VOUCHER_EVALUATOR_AUDIT.md) §F8–F12, and for seeing the same
families **broken** on the unpatched code. Everything you need is created by one seeder; the only
thing that changes between "fixed" and "broken" is which commit is checked out.

What the fix changed, in one line each:

| Finding | Before | After |
|---|---|---|
| F9/F10 | "At school" decided by `8 - currentMonth`, so every 4y1m+ child is at school Sep–Dec and none are Jan–Aug | Real start date `1 <school month> <start year>` computed from the child's DOB |
| F11 | `age >= 5` short-circuits before the `deferred` flag is read | Deferral adds a year to the start date first |
| F8 | Evaluation date ignored | Threaded through (only visible in unit tests) |
| F12 | Three copies of the logic | One specification |

Time budget: about 20 minutes for the core checks (steps 1–5), 10 more for the env-driven extras.

---

## 0. Before you start

* A working local install with the database up (`./script/server`, or your usual setup) and the
  **fixed branch** checked out (`git branch --show-current` should show the hotfix branch, not `ae14917c`).
* Know the Store URL (locally `http://arcv-store.test` or whatever your hosts file maps to `localhost:8080`).
* `.env` has `ARC_SCOTTISH_SCHOOL_MONTH=8` (the production value). Confirm with
  `grep ARC_SCOTTISH_SCHOOL_MONTH .env`.
* Note **today's date**. The old bug is calendar-dependent; the seeder prints expectations for the day
  you run it, so seed and test on the same day.

> Running through Docker? Prefix every `php artisan …` below with `./script/artisan` instead
> (e.g. `./script/artisan db:seed --class=…`).

## 1. Seed the scenarios

```bash
php artisan db:seed --class="Database\\Seeders\\ScottishEvaluatorScenarioSeeder"
```

This is **not** part of the normal `migrate --seed`; it only runs when you ask. It creates:

* Sponsor **Scottish Evaluator Test Sponsor** (`SCOT`) with the Scottish rule set
* Centre **Scottish Evaluator Test Centre** (RVIDs `SCOT0001`…)
* Store login **`arc+scot@neontribe.co.uk` / `store_pass`**
* Eleven families, one per scenario, whose carer names start with `SCOT-A`, `SCOT-B`, … `SCOT-H4`

It then prints a **cheat-sheet table**. Keep that terminal visible — it is your expected-results sheet:

* **This branch** – evaluated live by the code you have checked out (i.e. the fix)
* **ae14917c (unpatched)** – what the old code will show for today's date and school month
* **Differs?** – `YES` marks the families worth switching branches for

Lines starting `+` are credit reasons (as shown under "This family should collect…"), lines starting
`!` are Reminder-box notices.

Re-running the seeder is safe: it deletes and recreates its own sponsor, centre, user and families,
and reprints the sheet. Do that whenever you change the date or `ARC_SCOTTISH_SCHOOL_MONTH`.

## 2. Log in and find the families

1. Open the Store and log in as `arc+scot@neontribe.co.uk` / `store_pass`.
2. Go to **Registrations** (the families list) and search for `SCOT`. All eleven families appear,
   named `SCOT-A …` to `SCOT-H4 …` with RVIDs `SCOT0001`–`SCOT0011`.
3. For each family, open **Edit** (the family page). You are looking at:
   * **"Should collect N per week"** under *This family* – the entitlement
   * the **Reminder** box (only present when there is a notice)
   * the children table: age, month/year, and the **Defer** checkbox (Scottish sponsors only)

   (The "(more)" link under the entitlement lists the sponsor's *rules*, not this family's result.)
4. To see **which reason** each voucher comes from, click **Print a 4 week collection sheet for this
   family**: the sheet lists e.g. "**4** vouchers because one Child is between 1 and start of primary
   school age (SCOTLAND)" and repeats the Reminder notices. The entitlement and Reminder box are also
   shown in **Voucher manager**.

> **About disqualified families (audit finding F1):** on **this branch** a family that drops to 0/wk
> also shows a Reminder line explaining why – for the Scottish rules it reads "A family has no child
> under primary school age then children of primary school age get (SCOTLAND)" (clumsy wording, known
> follow-up). On **`ae14917c`** you see **0 per week with no explanation**: F1 was fixed in a later
> commit on this branch, so "0 and silent" is the correct *unpatched* symptom, not a display bug you
> have found. F1 has its own script: [`MANUAL_TEST_EVALUATOR_NOTICES.md`](MANUAL_TEST_EVALUATOR_NOTICES.md).

## 3. Check the fixed behaviour (default school month = 8)

Tick each against the cheat-sheet. In a September–December run you should see:

| Family | What it demonstrates | Expected on the fix |
|---|---|---|
| **SCOT-A** | F9/F10 – 4-year-old who does not start until next August | **4/wk**, "between 1 and start of primary school age (SCOTLAND)" |
| **SCOT-B** | F11 – 5-year-old with **Defer** ticked, starts next August | **4/wk**, same reason |
| SCOT-C | control – deferred child still 4 | 4/wk |
| SCOT-D | control – genuinely at school + toddler | **8/wk**, one "primary school age (SCOTLAND)" and one "between 1 and…" |
| SCOT-E | reserved for step 6 | 0/wk (only child already at school), family reason in the Reminder box |
| SCOT-F | control – 7-year-old only child | 0/wk, family reason in the Reminder box |
| SCOT-G | control – pregnancy only | 4/wk, "pregnant" |
| SCOT-H1 | reserved for step 7 (also shows the F9/F10 bug Sep–Dec) | 4/wk |
| SCOT-H2–H4 | reserved for step 7 | as printed |

With the default school month the only Reminder lines are the family disqualifier reasons on the
0/wk families (SCOT-E, SCOT-F, SCOT-H2, SCOT-H3); no "almost" or "defer" notices anywhere.

Optional interactive check on **SCOT-B**: untick **Defer**, save, and the family should drop to
0/wk (child now counted as at school since last August); re-tick and save to restore 4/wk. The
Defer checkbox is the only child field the Store lets you change without re-entering the child.

## 4. Switch to the unpatched code

Leave the browser tab open. In the terminal:

```bash
git status                         # make sure you have nothing uncommitted you care about
git checkout ae14917c              # or the pre-fix branch name
php artisan optimize:clear         # drops any cached config/views/routes
```

Nothing else is needed: the two commits share the same migrations and `composer.lock`, valuations are
calculated fresh on every page load, and the seeded data is untouched. (The seeder class itself does
not exist on the old commit – that is fine, you already ran it.)

## 5. See the broken behaviour

Refresh the family pages (the figure is recalculated on every page load, so no reseed is needed):

| Family | Fixed said | Unpatched shows (Sep–Dec) | Why |
|---|---|---|---|
| **SCOT-A** | 4/wk | **0/wk, no reason** | old code treats any 4y1m+ child as at school once September arrives |
| **SCOT-B** | 4/wk | **0/wk, no reason** | `age >= 5` wins before the Defer flag is looked at – **visible in every month** |
| SCOT-C, D, G | – | unchanged | controls – the fix does not alter correct cases |
| SCOT-E, F | 0/wk with the family reason | 0/wk, **Reminder box gone** | same entitlement; only the F1 reason text disappears on the old commit |
| SCOT-H1 | 4/wk | 0/wk | same bug as A |

"No reason" above is F1, not F9–F11: the old commit never shows why a family is disqualified.

If you are testing between **January and August**, SCOT-A and SCOT-H1 will *not* differ (the old bug
flips direction; see step 6) but **SCOT-B always does**. Trust the cheat-sheet's `Differs?` column
over this table.

Then come back:

```bash
git checkout -                     # back to the fixed branch
php artisan optimize:clear
```

Refresh and confirm the families are back to the step-3 values.

## 6. Extra: the over-credit direction of F9 (needs an env change)

Between January and July the old code makes the opposite mistake – a 4-year-old who *is* at school
is still credited. You can reproduce that today by moving the school month to **the current month**:

1. In `.env` set `ARC_SCOTTISH_SCHOOL_MONTH=<current month number>` (e.g. `9` in September).
2. `php artisan config:clear` (both branches read the same `.env`, so set it once).
3. Re-run the seeder on the fixed branch to reprint the cheat-sheet for the new month.
4. **SCOT-E** on the fix: **0/wk** (the child started school this month).
   Switch to `ae14917c` (step 4), refresh: **4/wk** – over-credited.
5. Switch back (end of step 5).

## 7. Extra: the notice fixes (needs an env change)

The "almost primary school age" and "able to defer" reminders only fire in the month before / the
month of school start. Set the school month to **next month** to open the window on both branches:

1. `.env`: `ARC_SCOTTISH_SCHOOL_MONTH=<next month number>` (e.g. `10` in September), then
   `php artisan config:clear`, then re-run the seeder for a fresh cheat-sheet.
2. Open each of SCOT-H1…H4 and read the **Reminder** box:

| Family | Fix shows | Unpatched shows | Point proven |
|---|---|---|---|
| SCOT-H1 (4y3m, starts next year) | nothing | almost **and** able to defer | old code fires purely on the age string 4y1m–4y6m |
| SCOT-H2 (5y5m, this cohort) | almost primary school age | nothing | fix uses the real start cohort; old string check misses over-5s |
| SCOT-H3 (4y10m, this cohort) | almost **and** able to defer | almost only | deferral eligibility is "under 5 at start", not "4y1m–4y6m" |
| SCOT-H4 (4y9m, **Defer ticked**) | nothing | almost primary school age | old code never reads the Defer flag for notices |

   (SCOT-H2 and SCOT-H3 are 0/wk families, so on the fix their Reminder box also carries the family
   disqualifier line described in step 2; ignore it here, the "almost"/"defer" lines are the point.)

3. Switch to `ae14917c`, refresh, compare with the right-hand column; switch back.
4. Put `.env` back to `ARC_SCOTTISH_SCHOOL_MONTH=8`, `php artisan config:clear`, and re-run the
   seeder one last time so the sheet matches production settings again.

## 8. Clean up (optional)

The scenario data lives under its own sponsor/centre and does not interfere with the other dev
seeds. To remove it, run the seeder again (it recreates a clean set), or drop the rows by hand:
sponsor `SCOT`, centre prefix `SCOT`, user `arc+scot@neontribe.co.uk`, and their registrations.

## What this script cannot show

* **Dec → Jan year wrap (part of F9)** – needs the real clock in December with a January school
  month. Covered by `IsScottishAlmostStartDateTest::it_handles_year_wrapping_for_custom_january_start_month`.
* **F8 (offset date)** – no UI passes a date. Covered by `EvaluatorAuditTest::testAuditF8ScottishRulesRespectOffsetDate`.
* **Per-child voucher differences** – under the Scottish rules a child at school and a child under
  school age are both worth 4, so a wrong classification only changes the *reason text*, or drops the
  family to 0 when it is the only child. That is why most scenario families have a single child.

The seeder and its expectations are themselves pinned by
`tests/Unit/Seeders/ScottishEvaluatorScenarioSeederTest.php`, which runs the scenarios for a fixed
September date and asserts the fixed/unpatched numbers above.
