# StreakFit Arena V4.2 — Practical Experience

## Installation
Apply this patch over a complete V4.1 installation. Merge the folders app/, public/, docs/ into the project. Do not replace entire directories or overwrite .env. If other contributors changed the same files, review/merge those changes first. V4.2 does not add a migration; V4.1's member_presence migration is still required for presence on shared MySQL. Start `php -S 127.0.0.1:8000 -t public public/index.php` and reload without cache.

## Features
- Overview: active session timer based on server-rendered UTC start and a monotonic browser timer, with minimum duration from settings. Server validates checkout unchanged. Existing camera and weekly 3D progress retained. Weather stays on Overview only.
- Insights: bounded 84-day valid-workout query, 7/30/84-day selectable stats and line chart, calendar heatmap with accessible day buttons, current month to date versus full previous month. No synthetic data. Existing health/weekly charts stay. Month comparison deliberately does not label change as growth because the periods differ.
- Community: up to 12 members derived from existing feed/activity; recent post link, public scheduled dates and times only (no notes, email, health measurements, GPS). Presence remains distinct from workout status. Feed images, reactions, comments and timeline retained.
- Rank: six derived achievement milestones from existing user totals/best streak, no bonus XP, no unlock timestamps or reward writes. Existing rank 3D and podium stay.
- Groups: search ignoring Vietnamese accents, sports/membership/status filters on loaded results, local CSS/SVG cover by sport, counts of loaded groups. Server GET sport filter remains available with JS disabled; reset link if a prefiltered URL was opened. No changes to group permissions/actions.
- Fund: aggregate recorded transaction amounts by type/status, personal transactions up to 100, paid counts. Per-member payment status list remains restricted to users who can manage the fund. No claim of actual bank balance, liability, or net payout. Existing PayOS and vote flows unchanged.
- Appeals: steps and personal history (up to 20), status uses existing backend values, form still uses server validation and configured limit. Does not introduce automatic leave or busy cards.

## Verification
PHP lint, JS syntax, original rank/final/atmosphere/presence checks. Chromium fixture smoke testing at 1440, 390, and 320 px: active timer, charts, heatmap, member modal, groups, milestones, appeal form, finance, no horizontal overflow. Fixture data only; no live MySQL, real camera, payment, multi-account presence or actual external API tests. Browser used PHP-rendered HTML with local assets inlined because direct localhost navigation is blocked in test environment. Screenshots contain illustrative fixture data. Backend changes add read-only SELECTs, using existing authenticated user ID. Some reads occur together for the existing instant-switch feed tabs. No database schema change, no external packages or API keys.

## Manual production checklist
1. Open Overview before and during a valid session; verify minimum-duration progress and checkout using a real camera. Verify week totals and weather opt-in.
2. Open Insights; reconcile 7/30/84-day stats and month totals with SQL/workout history. Check no-data account; confirm private health data is not exposed to other accounts.
3. Open Community; verify member dialog uses actual latest feed post and current week public schedule. Check reaction/comment and presence from two accounts; verify opt-out.
4. Open Rank/Board/Court; verify milestone numbers, vote permissions, personal appeal history and existing validation.
5. Open Groups; verify search/filters and create/join/leave/archive actions. Check mobile dock and desktop sidebar.
6. Open Fund as member and manager; verify personal vs manager views, totals against ledger, payment and vote actions. Do not treat recorded transactions as bank balances.
