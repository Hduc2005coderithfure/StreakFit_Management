# StreakFit Arena V3.8 — Health & Community

Patch against the delivered V3.7 source. Merge the contents into the existing project; do not delete unrelated files or overwrite local configuration. Compare the changed PHP/JS files with any later team edits before copying. No new API key, database table, migration, package installation, or `.env` changes are required.

## Member UI
- Dashboard opens with the actual start/finish workout action, an inline collapsible camera form, active-workout timer, and shortcuts to schedule, absence/explanation, group fund, and coach. The cinematic also has a workout shortcut. Camera capture still uses the same PHP actions, CSRF, server timestamps and minimum duration. If today already has a valid workout, dashboard links to history instead.
- Existing weather system remains; the forecast card is placed after the workout card on overview. Theme remains applied when switching community tabs.
- Desktop bento displays daily valid-workout minutes and private body measurements side by side. Mobile stacks them. Charts use up to 30 recorded measurements with weight, height, and BMI calculated per recorded measurement. Missing measurements show an empty state, not invented data. Current BMR is an estimate, not a measurement or a historical series. Graph x-axis represents sequential measurements (not proportional calendar-time intervals). Details tables show actual timestamps and values.
- Recent timeline is derived from the existing newest 20 valid workout posts and the existing limited recent-member activity query, plus the member's active workout. This is not a WebSocket/live feed or a complete audit log. Dashboard shows 4 events and Community shows 12. Photo viewer opens existing authenticated media URLs; photos are not copied or sent to AI. Existing reactions and comments remain.
- Court UI renamed Nghỉ tập & giải trình; unchanged appeal/vote mechanism, not automatic leave approval. Fund UI renamed Quỹ nhóm; payment, opt-in and proposal rules unchanged. Existing technical route keys/actions remain intact for compatibility.
- Visible rotate hints and the duplicate large dashboard rank display are removed; interactive rank hall remains. Duplicate schedule summary and redundant replay/CTA removed. Intro still accessible in account drawer. Accessibility labels retained.

## Data and privacy
`app/Legacy/application.php` changes the existing health history SELECT to retrieve height and up to 30 rows, and adds a parameterized read-only aggregate of valid workout duration for the signed-in member's current week. No new SQL migration; existing database schema unchanged. Health charts remain private to signed-in member, do not add health values to the community or AI payload. Existing health form still writes the existing `health_metrics` and `users` fields. Workout form still writes through existing CheckInService. No paid API added.

Existing Gemini coach remains optional. The UI explicitly states that questions and weekly progress are sent to Google when submitted; do not enter sensitive health information. Gemini free tier can have data-use terms and quota limits. No automatic sending of body measurements, images, precise GPS or personal health history. BMI/BMR are informational only. The existing Gemini model is configured server-side; subscription to Google AI Pro/ChatGPT Plus does not itself guarantee API access. 

## Verify
1. Start existing PHP app (`php -S 127.0.0.1:8000 -t public public/index.php`), sign in, visit `/dashboard?view=overview`.
2. Verify camera opens via the start action and existing validation applies; finish an eligible active workout, check community. Try collapsing camera/switching tab and verify camera tracks stop. Camera permission remains required.
3. Verify zero/one/multiple historical body measurements; switch between chart series and expand data. Check against database rows for the authenticated member.
4. Verify week bars reflect valid workouts only; test completed-today and active states; review timeline and image viewer. Verify no horizontal overflow.
5. Visit `/leaderboard?view=court`, `/profile?section=fund`, `/profile?section=health`, `/profile?section=schedule`; verify existing forms, payment and roles.
6. Existing tests: `php tests/arena_v33_rank.php`, `php tests/arena_final.php`, `node tests/arena_atmosphere.js`.

Tested PHP syntax, JS syntax, existing rank/final/weather tests, Chromium fixture UI (1440, 390, 320 pixels), chart tabs, inline camera reveal and form actions, active timer, community timeline, photo dialog, no horizontal overflow. Fixture uses example metrics and placeholder photos; not a live MySQL or physical camera test. No production database access, no live Gemini call, no payment or multi-account integration test. The patch does not include the team's changes since the V3.7 delivery.
