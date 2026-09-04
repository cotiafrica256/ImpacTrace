# ImpacTrace

A data collection and reporting system for **CodeToInnovate Africa (COTIA)** — built for
the MECPA Uganda household climate vulnerability assessment as the first project, and
designed from the ground up to be reused for every project after it.

Stack: **Laravel 11 (API) · Vue 3 · Tailwind CSS · MySQL**

---

## 1. What this system does

**COTIA is the platform's super-admin.** Every other organisation that comes onto the
platform — MECPA Uganda, and anyone after them — is its own **tenant**: it sees only
its own users, projects, forms, and data. Organisations never see each other's
information, and duplicate-respondent checks never compare one organisation's people
against another's.

- **COTIA (super_admin)** onboards a new client organisation and creates its first
  Executive Director account in one step. From there, that organisation runs itself —
  COTIA staff do not reach into its project data.
- Within an organisation:
  - **Field Officers** collect data on any device (phone, tablet, laptop) using a form
    that walks through the project's questionnaire section by section.
  - Before any data is recorded, the system captures **informed consent**: a photo of
    the respondent's ID (camera or a desk scanner via file upload), a **live signature**
    captured on the device right after the ID is scanned, a respondent photo, and an
    optional voice note.
  - **No ID or respondent identity can be registered twice within that organisation.**
    Every submission is checked against everyone already in that organisation's records
    before it's allowed through.
  - **Project Officers** see and review the work of the Field Officers under them.
  - The **M&E Officer** sees data quality and analytics across all of that
    organisation's projects.
  - The **Executive Director (ED)** is the admin of their own organisation — creates
    user accounts, assigns roles, and has final approval on reports.
  - The system auto-generates the numbers for **Weekly, Monthly (Activity + M&E),
    Quarterly Progressive, and Annual reports** from real submitted data; officers then
    add the narrative before it goes up for ED approval and funder/partner sharing.
  - **No form is hard-coded.** Every data-collection instrument (MECPA's household
    climate vulnerability form included) is stored as a JSON schema under one project.
    Each organisation designs its own forms for its own projects — no developer needed,
    no shared template imposed on them.

## 2. One honest limitation, up front

You asked that a respondent's signature match the one on their ID card, and that this
be used to stop duplicate registrations. A web application (or any off-the-shelf
software, really) **cannot reliably compare a handwritten signature against a
photographed ID card** — forensic signature verification is a specialist field, and a
false "match"/"no match" would be worse than not attempting it.

What the system does instead, which solves the real problem — stopping the same person
being registered twice — is:

1. Capture the ID photo, the live signature, and the respondent's photo as one evidence
   bundle attached to that submission, so there is a full audit trail a human can check.
2. **Hash the ID number** (never store it in the clear) and hard-block any second
   submission using the same ID number.
3. For people with no formal ID, fall back to a **name + age + village** match, which
   is *flagged* (not silently blocked, since two different people can share this) for a
   supervisor to confirm before the entry proceeds.

This is described in `backend/app/Services/RespondentDeduplicationService.php`.

## 3. Project structure

```
mecpa-meal-system/
├── backend/     Laravel 11 API (auth, roles, projects, forms, submissions, reports)
└── frontend/    Vue 3 + Tailwind SPA (mobile-first — this is what field staff use)
```

## 4. Setting it up

### Backend

Requires PHP 8.2+, Composer, and MySQL.

```bash
cd backend
composer install
cp .env.example .env
php artisan key:generate
```

Edit `.env` — set `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` to your MySQL details,
and create that database (e.g. `mysql -u root -p -e "CREATE DATABASE mecpa_meal"`).

```bash
php artisan migrate
php artisan storage:link
php artisan db:seed
php artisan serve
```

`db:seed` creates COTIA's platform admin account, plus one demo client organisation
(MECPA Uganda) with its own ED/M&E/PO/FO users and the full MECPA project already
loaded. Change all of these passwords immediately.

| Level | Role | Email | Password |
|---|---|---|---|
| Platform | COTIA super-admin | admin@cotia.africa | ChangeMe!2026 |
| MECPA Uganda (org) | Executive Director | ed@mecpa.org | ChangeMe!2026 |
| MECPA Uganda (org) | M&E Officer | meo@mecpa.org | ChangeMe!2026 |
| MECPA Uganda (org) | Project Officer | po@mecpa.org | ChangeMe!2026 |
| MECPA Uganda (org) | Field Officer | fo@mecpa.org | ChangeMe!2026 |

The API now runs at `http://localhost:8000`.

### Frontend

Requires Node.js 18+.

```bash
cd frontend
npm install
npm run dev
```

Open `http://localhost:5173` and log in with one of the accounts above. On the same
Wi-Fi network, field officers can open `http://<your-computer's-IP>:5173` on their own
phones to collect data — camera, signature pad, and voice recorder all work through the
browser, no app install needed.

For real deployment (not just testing on one Wi-Fi network), build the frontend with
`npm run build` and serve the `dist/` folder from a proper web server (e.g. behind the
same domain as the API, or on any static host), and point `VITE_API_URL` at your live
API URL before building.

## 5. How the roles work

- **COTIA super-admin (`super_admin`)** — the platform level, above every organisation.
  Onboards new client organisations (Organizations page) and can suspend/reactivate
  one's access. Does **not** see any organisation's projects, submissions, or reports —
  that boundary is enforced server-side, not just hidden in the UI.
- **Executive Director (`ed`)** — admin of ONE organisation. Only the ED can create
  user accounts and change roles within their organisation (Users page).
- **M&E Officer (`meo`)** — creates/edits projects and forms, sees all data and reports
  across their organisation's projects, reviews and approves submissions.
- **Project Officer (`po`)** — manages the Field Officers on their assigned project(s),
  reviews their submissions, prepares reports for ED sign-off.
- **Field Officer (`fo`)** — collects data on their assigned project(s). Sees only their
  own submissions.

## 6. Onboarding a new organisation

1. Log in as the COTIA super-admin → **Organizations** → **Onboard organisation**.
2. Fill in the organisation's name and their first Executive Director's details — this
   creates the organisation and that ED account together, in one step.
3. Hand the ED their login. From there they run their organisation independently:
   create their own users, projects, and forms. Nothing they do is visible to any other
   organisation, and COTIA staff cannot open their project data.

## 7. Adding a project within an organisation

1. Log in as that organisation's ED or M&E Officer → **Projects** → **New project**.
2. Open the project → **New form** → give it a title and a `schema` (JSON — see
   `backend/database/seeders/mecpa_form_schema.json` for a full worked example with
   every field type currently supported: `text`, `number`, `date`, `textarea`,
   `select`, `multi_select`, `boolean_yes_no`, `gps`, `rating_table`, and the consent
   fields `id_capture`, `signature_pad`, `photo_capture`, `voice_recorder`,
   `multi_photo_capture`).
3. Assign Field/Project Officers to the project.
4. Field Officers immediately see the new form on their dashboard and can start
   collecting data — no deployment, no code change.

## 8. What's built vs. what to extend next

**Built and working end-to-end:** COTIA super-admin onboarding client organisations,
full data isolation between organisations (enforced in every controller, not just the
UI), authentication & roles, project/form management, the full MECPA form as a live
example, consent + duplicate-prevention data collection, attendance extraction, report
generation with auto-computed statistics, the public searchable data bank, reader
accounts, paid reading/download access, manual MoMo verification, publication comments,
PAR cycles, QuickBooks-style finance import/summary, district plans, stakeholder
meetings, advocacy issues, and presentation-deck records.

**Sensible next additions**, once you're using this day to day and know what you need:
- PDF/Word export of generated reports (branded with your COTIA/logo letterhead) —
  `barryvdh/laravel-dompdf` is already in `composer.json`, ready to wire up.
- Proper offline support for Field Officers in areas with no signal (draft entries
  saved to the device and synced once back online).
- A visual (drag-and-drop) form builder in place of pasting JSON, once you've built a
  few forms by hand and know the shapes you actually need.
- A production mobile-money adapter after the provider and webhook credentials are supplied.
- Full visual editors and presentation rendering for the deck, PAR, plans, meetings, and advocacy records.
- Dashboards/charts summarising vulnerability scores and disaggregated data over time.

None of these require restructuring what's here — they build on top of it.

## 9. Public data bank and participation

The platform also includes a public knowledge bank at `/publications`. Published
research is searchable by title, summary, category, and issue. Visitors can read the
summary, create a reader account, and request either a time-limited reading package or
a separate download package. Package prices, reading duration, and the different
gateway/MoMo amounts are configured by administrators. Manual MoMo payments accept the
last five characters of the transaction reference and remain pending until the COTIA
super-admin verifies them; provider webhooks can activate access automatically once the
real provider adapter and signature validation are configured.

Organisation users can also use the PAR cycle endpoint (`plan`, `act`, `observe`, and
`reflect`), QuickBooks-style CSV finance import and summaries, and the publication admin
workflow. The merged source includes the presentation, district/sub-county plan,
stakeholder meeting, advocacy, and publication data models as the next API expansion
surface.

The application cannot technically guarantee that a reader will not photograph a
screen or capture content with operating-system tools. It protects content through
server-side access checks, time limits, download permissions, and publication controls;
additional browser deterrents should be treated as a user-experience measure, not a
security boundary.
