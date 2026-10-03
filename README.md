# School Markaz — Complete School Management System

**Stack:** PHP 8.1+ · MySQL · HTML/CSS/vanilla JS — no frameworks, no Composer.
Fully responsive (mobile-first) with dark-mode toggle.

## Features

**Public site**
- Landing page: hero, features, **packages/pricing table**, one-click demo logins
- Online admission inquiry form (goes to admin inbox)
- New-school registration (creates school profile + admin account + package)

**Admin**
- Dashboard: students, teachers, fee collection, attendance %, defaulters + Chart.js graphs
- Students CRUD (auto admission no. + login), promote, parent linking
- Teachers & parents CRUD with auto-created logins
- Classes + subjects, timetable manager (Mon–Sat × 6 periods)
- Attendance overview + mark-all-present
- Fee heads, one-click monthly invoice generation, fee collection → **PDF receipt**
- Defaulters list, finance (income/expense + charts), salary payments
- Exams, marks entry grid, **PDF report cards** (grades + class position)
- Announcements (role audiences), admission inquiries, CSV reports, settings + logo upload

**Teacher portal** — dashboard, mark attendance (own classes), enter marks, timetable, salary slips, notices
**Student portal** — dashboard, attendance calendar, results + report cards, fee invoices/receipts, timetable, notices
**Parent portal** — children cards (attendance %, fee balance, results), per-child detail, fee receipts, notices

**Packages (seeded):** Micro — 75 students — PKR 1,500/mo · Starter — 150 — PKR 3,000/mo · Growth — 300 — PKR 6,000/mo

## How a plan is purchased (subscription flow)

1. Visitor clicks **Choose** on a package → `subscribe.php`: picks Monthly or Yearly (yearly = 10× monthly, 2 months free), enters school + contact details → order created (e.g. `PO-2026-0001`, status `pending`).
2. Redirected to `pay.php`: shows amount + your **JazzCash / Easypaisa / Bank** details (set them in **Admin → Settings → Payment Methods**). Buyer sends the money, then submits the transaction ref/TID + screenshot → status `submitted`.
3. **Admin → Subscriptions**: see pending badge in sidebar, view proof screenshot, **Approve** (subscription activates: start = today, end = +1 or +12 months, school's package updated) or **Reject** with reason.
4. Buyer can reopen their `pay.php?o=PO-...` link anytime to see status; admin dashboard shows active subscription + days remaining and alerts on new orders.

No online gateway needed — manual verification, the standard way Pakistani schools pay.

## Demo logins (after seeding)

| Role    | Username  | Password |
|---------|-----------|----------|
| Admin   | admin     | demo123  |
| Teacher | teacher   | demo123  |
| Student | student   | demo123  |
| Parent  | parent    | demo123  |

Or use the one-click **Demo** buttons on the login page / landing page.

## cPanel deploy

1. Create a MySQL database + user (e.g. `schoolmarkaz_db`).
2. Upload the ZIP contents to `public_html` (or a subfolder).
3. Import `database/schema.sql` via phpMyAdmin.
4. Edit `config/database.php` (or set `DB_HOST/DB_NAME/DB_USER/DB_PASS` env vars).
5. Run the seeder once: open `https://your-domain/database/seed.php` in the browser
   (it exits harmlessly if already seeded).
6. Log in as `admin` / `demo123` and immediately change the admin password
   via phpMyAdmin (`users` table). New staff/student/parent logins are
   auto-created from their respective admin pages.

> The app self-heals: on first load it creates any missing tables from `database/schema.sql`.

## Project layout

```
config/        app + database config (env-overridable)
includes/      bootstrap, auth (RBAC), helpers, layout, portal helpers, fpdf/
database/      schema.sql, seed.php
admin/         admin panel (14 pages)
teacher/       teacher portal (6 pages)
student/       student portal (6 pages)
parent/        parent portal (4 pages)
auth/          login/logout
assets/        css (dark mode + responsive), js, icons
uploads/       logo/ (writable)
```

## Notes / simplifications

- Salary payments record into `salary_payments` only (no automatic finance expense entry).
- Teacher salary slip uses browser print (`.slip` CSS), not a second PDF path.
- Holiday (H) attendance is excluded from percentage denominators.
- Report-card class position is computed among same-class students with marks in that exam.
- FPDF 1.9 is vendored in `includes/fpdf/` (no Composer needed).
