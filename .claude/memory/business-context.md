# Business context

_Committed. If the repo is public, keep only public-safe facts here: published tiers and list prices, positioning, cost drivers. Private numbers go in the "Business (private)" section of the gitignored `CLAUDE.local.md`._

Read by `finance-reviewer`, `marketing-reviewer`, `legal-reviewer` and `product-reviewer`. Items marked _TBD_ are open questions for Odell. Agents must not fill them in by guessing.

## Product and positioning
- **Product:** Watch Out Wednesday, by Duppins Technology (https://duppinstech.com).
- **Audience:** WordPress sites that publish a weekly vulnerability recap. Operators rerun a missed Wednesday or backfill older weeks from the settings screen (`INTENT.md`).
- **Positioning:** Pulls vulnerability intelligence from Wordfence and publishes a weekly "Watch Out Wednesday" post on a schedule.
- **Built for (client):** product work

## Tiers
| Tier | What's included | Price | Distribution |
|---|---|---|---|
| Core | Weekly cron post, current-week run, missed-week rerun, bulk backfill, intro and outro text, category, featured image, and notification email | Free | GitHub Release zip. Not on WordPress.org. |

## Cost drivers
- The Wordfence Intelligence v3 key is entered on Settings → Watch Out Wednesday → Authentication by the person configuring the plugin. It is stored in `wow_settings['wordfence_api_key']` and is not hardcoded.
- Hosting: _TBD (Odell)_
- Support time: _TBD (Odell)_
