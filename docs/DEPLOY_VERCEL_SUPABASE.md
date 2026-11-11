# Deploy WatchEarn: Vercel (hosting) + Supabase (database)

Free tiers: Vercel gives you `https://<project-name>.vercel.app`, Supabase
gives a free PostgreSQL database. **Vercel does not officially support
PHP** — this deploy uses the community `vercel-php` runtime (already
configured in `vercel.json` + `api/index.php`). It works well for this
app; expect a short "cold start" delay on the first visit after idle.

> Do every step **in order, one at a time**. Tick each one off before
> moving on. Passwords and keys are typed only into your own files and
> dashboards — never into chat.

---

## Part A — Supabase database (about 10 minutes)

1. Go to `https://supabase.com` → **Sign up** (GitHub or email) → verify.
2. **New project**:
   - Name: `watchearn`
   - **Database password**: click Generate, then **save this password in
     your password manager / a safe note**. You will need it in Part B —
     it cannot be shown again.
   - Region: **South Asia (Mumbai)** (closest to Pakistan; Singapore is
     also fine).
   - Plan: Free → **Create new project**. Wait ~2 minutes for it to be
     ready.
3. Click **Connect** (top of the project page) → open the **Session
   pooler** tab and note these values (the *pooler* is important — the
   direct connection is IPv6-only and fails on many home networks):
   - Host: `aws-0-<region>.pooler.supabase.com`
   - Port: `5432`
   - Database: `postgres`
   - User: `postgres.<project-ref>`

## Part B — Prepare the production .env on your laptop (about 10 min)

1. First enable PostgreSQL support in your terminal PHP (WinGet PHP):
   open your `php.ini`, find these two lines, and remove the `;` in
   front of them, then save and open a NEW terminal:
   ```
   extension=pdo_pgsql
   extension=pgsql
   ```
   Check with `php -m` — you should see `pdo_pgsql` in the list.
2. Extract the latest `watch-earn.zip` fresh (or use your existing
   `C:\xampp\htdocs\watchearn` folder after replacing the changed files).
3. Keep your local config safe: rename your current `.env` to
   `.env.local.bak`.
4. Copy `.env.production.example` and rename the copy to `.env`.
   Fill in, from Part A:
   - `DB_HOST`, `DB_USERNAME`, `DB_PASSWORD` (the database password you
     saved), `DB_DATABASE=postgres`, `DB_PORT=5432` — already set.
   - `ADMIN_PASSWORD` — **choose a new, strong admin password now.**
     This replaces the local default `password` for the live site.
   - Leave `MAIL_*` for Part F (you can deploy first, email comes last —
     but without it, new users cannot receive verification codes).
5. Run these commands **one at a time** in the project folder:
   ```
   composer install
   ```
   ```
   php artisan key:generate
   ```
   ```
   php artisan migrate --seed
   ```
   Success looks like all migrations DONE and both seeders DONE. Your
   live database now exists in Supabase, the settings are in, and the
   admin account is created **with your new ADMIN_PASSWORD**.
   (No demo user is seeded in production.)
6. Keep this `.env` — Part C needs the same values. When you want your
   local site back later, swap the file names back.

## Part C — Upload the code to GitHub (about 10 minutes)

Vercel will deploy **from GitHub** — you never upload files to Vercel
directly, and every future `git push` redeploys automatically.

1. Go to `https://github.com` → sign up / log in.
2. Click **+ → New repository**:
   - Repository name: `watchearn`
   - Visibility: **Private** (recommended)
   - Do NOT tick "Add a README" or any template — the project already
     has everything.
   - **Create repository**. Leave the page open; you need its URL, which
     looks like `https://github.com/<your-username>/watchearn.git`.
3. On your laptop, check Git is installed — in a terminal:
   ```
   git --version
   ```
   If it errors, install **Git for Windows** from
   `https://git-scm.com` (keep clicking Next), then open a NEW terminal.
4. In the WatchEarn project folder, run these **one at a time**:
   ```
   git init
   ```
   ```
   git add .
   ```
   ```
   git status
   ```
   Read the status list: you must NOT see `.env` or a `vendor` folder
   in it (the project's `.gitignore` already excludes them — this check
   protects your database password and app key).
   ```
   git -c user.name="Ali Hassan" -c user.email="YOUR-GITHUB-EMAIL" commit -m "WatchEarn first deploy"
   ```
   (use the email you signed up to GitHub with)
   ```
   git branch -M main
   ```
   ```
   git remote add origin https://github.com/<your-username>/watchearn.git
   ```
   ```
   git push -u origin main
   ```
   A browser window or login popup appears the first time — sign in to
   GitHub and approve. When the push finishes, refresh the GitHub repo
   page: all project files should be listed there.

## Part D — Vercel hosting from GitHub (about 10 minutes)

1. Go to `https://vercel.com` → **Sign up → Continue with GitHub**
   (same GitHub account) → free Hobby plan.
2. **Add New… → Project** → in "Import Git Repository" find
   `watchearn` → **Import**. If asked to install the Vercel app on
   GitHub, allow it for this repository.
3. On the Configure screen keep everything as-is (Root Directory `./`;
   `vercel.json` in the repo handles the PHP runtime). Open
   **Environment Variables** and add these (values from your `.env` in
   Part B): `APP_KEY`, `APP_ENV` = `production`, `APP_DEBUG` = `false`,
   `DB_CONNECTION` = `pgsql`, `DB_HOST`, `DB_PORT`, `DB_DATABASE`,
   `DB_USERNAME`, `DB_PASSWORD`, `DB_SSLMODE` = `require`,
   and the `MAIL_*` values once Part F is done.
   (`ADMIN_PASSWORD` is NOT needed here — it was only for seeding.)
4. Click **Deploy** and wait for the build to finish. Your free domain
   appears on the success screen — it looks like
   `https://watchearn-<something>.vercel.app`.
5. Go to **Settings → Environment Variables** and add
   `APP_URL` = `https://<that-domain>`, then open **Deployments → ⋯ on
   the latest deployment → Redeploy** so the app picks it up.
6. Open `https://<that-domain>` — the WatchEarn homepage should load.
   Log in as admin with the email from `.env` (`ADMIN_EMAIL`, default
   `admin@watchearn.local`) and your **new** password. Then go to
   **Admin → Global Settings** and set your **real WhatsApp number** —
   the seeded placeholder must not stay on a live site.

## Part E — Quick live checks

- Register a test user using referral code `ADMIN001` (the admin's
  code). After Part F, the 6-digit code arrives by email.
- As the demo check: watch one sample ad to the end → reward credited →
  the ad shows the 24-hour countdown at the end of the list.
- In Supabase → **Table Editor**, you should see `users`,
  `transactions`, etc. filling with live rows.

## Part F — Email for the live site (Brevo, free, about 10 minutes)

Without SMTP, verification codes cannot be delivered and nobody can
finish registering. Brevo's free plan (300 emails/day) is enough:

1. Sign up at `https://www.brevo.com` (free).
2. **Senders & IPs → Senders → Add a sender**: your own email; click the
   verification link Brevo emails you.
3. **SMTP & API → SMTP tab → Generate a new SMTP key**; save it.
4. Put into BOTH your laptop `.env` and Vercel's env vars:
   `MAIL_USERNAME` = your Brevo login email,
   `MAIL_PASSWORD` = the SMTP key,
   `MAIL_FROM_ADDRESS` = the verified sender email.
   (`MAIL_HOST`/`MAIL_PORT` are already in the template.)
5. Redeploy: in Vercel open **Deployments → ⋯ → Redeploy** (or just `git push` any small change).

## Changing the admin password later

Any time after the first login: **Admin → Global Settings → "Change
admin password"** card at the bottom of the page. Enter your current
password, then the new one twice, and save. (The `ADMIN_PASSWORD` in
`.env` is only used once, when the seeder first creates the account —
changing it there afterwards has no effect.)

## Limits to know (free tiers)

- Vercel Hobby: serverless functions pause when idle (first hit is
  slower), max function duration is limited — fine for this app's
  pages and postbacks.
- Supabase free projects pause after ~1 week of inactivity (any visit
  or login unpauses; the database itself stays).
- Nothing is stored on Vercel's disk between requests in this app by
  design (sessions are cookies, cache is per-request, logs go to
  Vercel's log stream).
