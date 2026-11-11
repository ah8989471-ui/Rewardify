# WatchEarn — Watch-to-Earn Micro-Task Platform

A complete Laravel 13 web application: users watch timed ads and complete
CPAGrip offers to earn USD, refer others with a **mandatory referral code**,
and withdraw via PayPal, Payoneer, USDT or local wallets. Admins manage
everything — including the **WhatsApp number** behind the registration
page's "Get it via WhatsApp" button — from a dedicated admin panel.

## Feature summary

**Accounts & referrals**
- Registration requires a **valid referral code** — the form cannot be
  submitted without one (`exists:users,referral_code` + required rule).
- Next to the code field: a green **"Don't have a referral code? Get it
  via WhatsApp"** button. It opens
  `https://wa.me/<admin-number>?text=Hello, I need a referral code to register.`
  where `<admin-number>` is read live from the database setting the admin
  controls in **Admin → Global Settings**.
- Email verification with a **6-digit code** (15-minute expiry, hashed at
  rest). Unverified users cannot log in to the panel.
- Every user gets their own unique referral code at registration and earns
  a configurable commission (default 10%) on referrals' ad + offerwall
  earnings.

**Worker panel**
- Dashboard: total balance, today's earnings, completed tasks, referral
  earnings (all USD).
- Watch Ads: task cards with reward + timer; the **ad player** issues a
  one-time server token; the ad opens in a **new tab** and a 15–20s
  (configurable) countdown counts only while the user is on the ad tab -
  returning to the player early stops the timer and shows a warning -
  and credits **only** after the server
  confirms the full duration elapsed. Tokens are single-use — no double
  credit, no double-click, no instant-claim cheating. After completion,
  each ad locks for that user for exactly **24 hours** (counted from the
  completion moment), shows a live countdown on its card, and moves to
  the end of the task list until the timer ends. Ads are global: every
  active ad added by the admin is visible to all users.
- Offerwall page: embeds the CPAGrip offerwall (URL or iframe code) with
  the user's id substituted as `{USER_ID}` / subid.
- Wallet & history: full ledger of every credit/debit with running
  balance.
- Withdrawals: PayPal / Payoneer / USDT / local wallet, minimum set by
  admin; the amount is held at request time and **refunded automatically
  if the admin rejects** the request.

**Admin panel**
- Analytics: total users, active users, total ads watched, total payouts
  cleared, pending withdrawals.
- Users: search, per-user detail (referrer chain, referral list,
  transactions), **manual balance adjustment** (audited in the ledger),
  suspend / reactivate.
- Global settings: site name/tagline, **admin WhatsApp number**,
  minimum withdrawal, default reward/duration, referral commission %.
- CPAGrip settings: offerwall URL / iframe code, postback secret
  (regenerable), ready-to-paste postback URL, recent postback log.
- Ad tasks CRUD; withdrawal approve/reject workflow.

**CPAGrip postback** — `GET|POST /postback/cpagrip`
```
?key=<secret>&user_id={subid}&payout={payout}&txn_id={transaction_id}&offer_id={offer_id}
```
Secret-checked (constant-time compare), each `txn_id` credited at most
once, every call logged (credited / duplicate / rejected). See
`docs/CPAGRIP_SETUP.md`.

## Tech

Laravel 13 · PHP 8.3+ · MySQL (SQLite for tests) · Blade + Tailwind CSS
(CDN) · USD everywhere, amounts stored at 4-decimal precision.

## Install

- Windows/XAMPP: see `docs/INSTALL_WINDOWS_XAMPP.md`
- Demo logins (local only, seeded):
  - Admin: `admin@watchearn.local` / `password` (referral code `ADMIN001`)
  - Worker: `demo@watchearn.local` / `password` (referral code `DEMO2026`)

## Tests

`php artisan test` — 29 tests covering mandatory referral registration,
the dynamic WhatsApp link, code verification, the timed ad player
(early-claim refusal, single credit, exact 24-hour per-ad cooldown with end-of-list ordering), referral commissions,
withdrawal hold/approve/refund, postback secret + dedupe, admin settings
and access control, and a render smoke test of every page.

## Honest limits

- The countdown/UX anti-cheat is backed by **server-side** token +
  elapsed-time validation, which is the part that actually protects
  payouts. A determined user with a modified browser can still *watch*
  dishonestly — pair high-value tasks with CPAGrip offers (whose
  postbacks are confirmed by the network) when fraud matters.
- Approving a withdrawal only marks it approved; the admin pays the user
  outside the app (PayPal etc.) and keeps the receipt.
