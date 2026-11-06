# Novesta launch checklist

Work top to bottom. Each phase should be finished before the next one starts.
Rough money needed before the first sale: **about $150–250** (domain, Apple developer account, hosting, email).

---

## Phase 1 — Must fix before anyone pays (1–2 weeks)

### Getting paid
- [ ] **Choose a payment provider that pays out to Pakistan.** Stripe does not accept businesses registered in Pakistan.
  - **Paddle (recommended):** it acts as the "merchant of record", so it charges customers, handles VAT and sales tax in every country, and pays you. Its payout options include wire transfer and Payoneer. Confirm Pakistan is listed when you sign up.
  - The **last committed version of this website already uses Paddle.** Only the uncommitted changes switched it to Stripe, so going back is quick.
  - Use Stripe only if you open a US company (for example through Stripe Atlas, about $500 plus yearly fees).
- [ ] Create the Pro and Business products in the provider's **sandbox**, then put their IDs in `.env`.
- [ ] Test the full flow in sandbox: sign up, upgrade, change plan, cancel, resume, download invoice, failed card.
- [ ] Go live only after the provider approves your website. Paddle reviews the site, pricing, Terms, Privacy and Refund pages; those pages already exist.

### Costs and limits
- [ ] Cut the AI cost of each message: send fewer tools and less history per request, and confirm OpenAI's prompt caching is working.
- [ ] Set limits so a user who maxes out a plan still leaves you profit (aim for AI cost under 50% of the price).
- [ ] Set a **monthly spending limit** in the OpenAI dashboard so a bug or abuse can't run up a big bill.
- [ ] **Rotate the OpenAI API key.** The current one has been stored in local `.env` files.
- [ ] On the account page, show that usage includes trial usage, so new Free users don't see confusing red "over limit" numbers.

### YouTube
- [ ] Replace YouTube page scraping (`media.js`) with the **official YouTube Data API**.
  - The free quota is 10,000 units a day and one search costs 100 units, so that's **about 100 searches a day for all users combined**.
  - Request a quota increase from Google (free, but it involves a review), and cache popular searches.
  - Do this through the website's gateway so the API key isn't inside the app.

---

## Phase 2 — Make it installable and trustworthy (1–2 weeks)

### Mac app
- [ ] Join the **Apple Developer Program** ($99 a year).
- [ ] **Sign and notarize** the Mac app (electron-builder supports this). Without it, macOS blocks the app with an "unidentified developer" warning, and most people give up there.
- [ ] Test a fresh install on a Mac that has never run Novesta: permissions for the microphone, Contacts, Calendar and Accessibility, then sign-in.
- [ ] Add auto-updates (electron-updater) so you can fix bugs without asking users to reinstall.

### Windows app
- [ ] Build and test the Windows version (`npm run dist:win`) on a real Windows PC.
- [ ] Code signing: an unsigned app triggers a "Windows protected your PC" warning. Options:
  - Azure Trusted Signing (about $10 a month; check whether it's available for your country or company).
  - An OV code-signing certificate (about $200–400 a year).
  - Without either, launch Windows as "beta" and explain the warning on the download page.

### Website hosting
- [ ] Domain: check that **novesta.com** (or similar) is available. Also do a quick trademark search for "Novesta" in your main markets.
- [ ] Hosting: a VPS (Hetzner or DigitalOcean, about $6–12 a month) or Laravel Cloud/Forge, with HTTPS.
- [ ] Database: SQLite is fine at first, but set up **daily automatic backups**. Move to MySQL or Postgres once you have real users.
- [ ] **Real email sending** (Resend, Postmark or Amazon SES). Sign-in links are sent by email, so if email doesn't work, nobody can log in. Use an address on your own domain, such as hello@novesta.com.
- [ ] Update Google and GitHub sign-in callback URLs to the live domain.
- [ ] Set `APP_URL`, `APP_ENV=production` and `APP_DEBUG=false`, and set the desktop app's `electron/config.json` `apiUrl` to the live domain.
- [ ] Error alerts (Sentry has a free tier) for both the website and the desktop app.

### Trust
- [ ] Review the Privacy page: what is sent to OpenAI, what stays on the computer (files, contacts, memories), and how to delete an account.
- [ ] On the homepage, explain the permissions Novesta asks for, and why.
- [ ] Add a support email or contact form, and reply within a day.

---

## Phase 3 — Free beta (2–3 weeks)

- [ ] Choose a target audience. Suggested: Urdu and Hindi speakers, freelancers and small-business owners who use WhatsApp all day.
- [ ] Recruit **20–50 beta users**: friends, family, Facebook groups, LinkedIn, Reddit (r/pakistan, r/macapps), local freelancer communities.
- [ ] Give them Pro free for the beta.
- [ ] Track, per user, how many days a week they actually use Novesta. Usage records already exist in `usage_events`.
- [ ] Talk to at least 10 of them: what do they use most, what broke, and would they pay? How much?
- [ ] Fix the top 5 complaints.

**Go / no-go:** if around a third of beta users still use it a few times a week after 2 weeks, launch paid plans. If not, change the focus based on what the most active users liked, and run another beta.

---

## Phase 4 — Paid launch

- [ ] Final prices. Suggested start: Pro at **$5–8 a month**, plus a yearly plan with 2 months free. Consider local pricing for Pakistan and India (Paddle supports this).
- [ ] Offer beta users a launch discount; they're your first customers and testimonials.
- [ ] Add 3–5 real testimonials to the homepage (with permission).
- [ ] Post the demo video on YouTube, LinkedIn, TikTok and Instagram. Short clips of single features (WhatsApp, music, Urdu) work better than the full video.
- [ ] Launch posts: Product Hunt, Reddit, Hacker News "Show HN", and Pakistani tech groups.
- [ ] Each week, check signups, Free→Pro conversions, cancellations, AI cost per user and profit per plan.

---

## Ongoing
- [ ] Each month, compare OpenAI costs with revenue for each plan.
- [ ] Keep `git` commits small, and tag every release you ship.
- [ ] Keep a short public changelog, so users see the app improving.
