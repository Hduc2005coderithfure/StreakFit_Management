# StreakFit V4.5 — Final Ascension

## Installation

Apply the patch to V4.4, or use the cumulative overlay on the original StreakFit project. Merge folders rather than replacing whole directories. Back up local work and compare changes made by other contributors before overwriting. Do not overwrite `.env`, uploads or storage. Copy `remove-v41-presence.ps1` into the project root.

Windows PowerShell (from project root):

```powershell
powershell -NoProfile -ExecutionPolicy Bypass -File .\remove-v41-presence.ps1 -Preview
powershell -NoProfile -ExecutionPolicy Bypass -File .\remove-v41-presence.ps1
php -S 127.0.0.1:8000 -t public public/index.php
```

No V4.5 SQL migration. The six obsolete presence source/migration/test files are deleted by the cleanup script. If `member_presence` was previously created in MySQL, it is no longer read or written by this release; the script does not drop it. Do not import the old presence migration. Existing baseline schema and data remain required.

## Changes
- Weekly energy uses existing valid-workout counts and weekly goal. No goal/zero/charge/surge/complete/surplus states; rank palette and backdrop stay tied to real XP. Motion reduced when system setting requests it. No new network or 3D engine for this effect.
- Running and gym only for new or reopened groups, and membership joins. Existing unsupported groups are not deleted; admin can view and archive them or explicitly change their sport. Member list hides obsolete groups. Existing workout route recorder stays available for running.
- Presence API, heartbeat, privacy setting and migration removed. Member rail shows workout activity, not last seen or online status. Sidebar clock remains local.
- Personal subpages render their own sections only; fund and health history queries restricted to relevant pages. No changes to points, streak, checkout validity, financial calculation rules or the existing payment integration. Weekly and monthly processing remains in the existing backend.
- Local tab switches hide other panels, preserve navigation, respect reduced motion. Check-in page prioritizes camera. History and schedule labels localized. Shared system fonts support Vietnamese.

## Verification

```bash
php tests/arena_v45_final.php
php tests/arena_v43_rank_assets.php
php tests/arena_v33_rank.php
php tests/arena_final.php
node tests/arena_atmosphere.js
```

Run integration tests against a disposable copy of the group's database, not production. Verify two accounts, camera permission, GPS, PayOS and Gemini with real services separately. The browser fixture uses rendered PHP and sample data; it is not a production end-to-end test.
