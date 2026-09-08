# ImpacTrace Screen Specification

## 1. Document Purpose

This document is the implementation brief for the development team working on the ImpacTrace platform.

The product has two clearly separated experiences:

1. **ImpacTrace Knowledge Hub**: the public reading, research, publication, payment, and reader-access experience.
2. **ImpacTrace Operations Workspace**: the authenticated staff system for organizations, projects, data collection, reporting, finance, publishing, support, and administration.

The interface must remain powerful for advanced users while being easy to navigate for field staff, organization leaders, and public readers.

## 2. Product Principles

- Use role-based navigation. Users should only see screens and actions relevant to their role.
- Keep public readers separate from staff users technically and visually.
- Prioritize the next action on every screen.
- Use clear cards, tables, filters, search, status labels, and empty states.
- Every high-volume list must include search and, where applicable, filters and pagination.
- Every financial action must leave an auditable record.
- Protect personal data by collecting only what is necessary for the selected workflow.
- Design mobile-first for field staff and responsive layouts for administrators.
- Avoid browser `prompt()` dialogs for important workflows. Use forms, drawers, and modal panels.
- Never hide important actions below an unscrollable viewport.

## 3. User Types and Access

### 3.1 Public Reader

A reader explores the Knowledge Hub and may purchase access to protected publications.

Storage and authentication:

- Model/table: `PublicUser` / `public_users`
- Browser token: `public_token`
- API client: public-reader client
- Must never appear in staff user management
- Must never receive staff permissions

### 3.2 Field Officer (`fo`)

Collects field data on a phone, tablet, or laptop. Sees only assigned projects and permitted submissions.

### 3.3 Project Officer (`po`)

Coordinates field officers, reviews submissions, monitors assigned projects, and follows up data-quality issues.

### 3.4 M&E Officer (`meo`)

Manages projects and forms, reviews organizational data, builds reports, manages knowledge records, and works with publications and finance.

### 3.5 Executive Director (`ed`)

Manages one organization, its users, projects, reports, finance, publications, and approvals.

### 3.6 Reader Manager (`reader_manager`)

Reviews reader payments, approves/rejects manual payment references, and monitors active and expired reader access.

### 3.7 Customer Service (`customer_service`)

Handles reader questions, payment issues, access issues, and support requests.

### 3.8 ImpacTrace Platform Admin (`super_admin`)

Manages organizations and platform-level controls. Can work across organizations only through an explicit acting-organization context.

## 4. Authentication Separation

### Staff authentication

- Staff login route: `/login`
- Staff token: `meal_token`
- Staff API client: `api/client.js`
- Staff landing area: `/app`

### Reader authentication

- Reader route: `/reader/register`
- Reader token: `public_token`
- Reader API client: `api/publicClient.js`
- Public routes: `/` and `/publications/:slug`

### Required behavior

- An expired reader token must not redirect the reader to staff `/login`.
- An expired staff token must redirect to staff `/login`.
- Public requests must not inherit the staff Authorization header.
- Staff requests must not inherit the reader Authorization header.
- Reader users cannot access `/app`.
- Staff users cannot use reader access records as staff permissions.

## 5. Navigation Model

### Public navigation

- Knowledge Hub home
- Search
- Plans
- Issues in action
- Reader sign in/register
- Organization Login
- Support

### Staff navigation

The sidebar should be grouped visually rather than presented as one long flat list.

#### Workspace

- Dashboard
- Projects
- Data collected
- Attendance
- Reports

#### Knowledge and publishing

- Publications
- Knowledge records
- Presentations

#### Finance and access

- Finance
- Payment review
- Reader access tracking

#### Administration

- Organizations
- Users
- Help inbox
- Settings, when implemented

## 6. Screen Specifications by Role

# A. Public Reader Screens

## A1. Knowledge Hub Home

Route: `/`

Purpose:

- Introduce the public research collection.
- Allow visitors to search and browse without an account.
- Present published publications, development plans, and advocacy issues.

Visible elements:

- Brand: `ImpacTrace Knowledge Hub`
- Subtitle: research, evidence, participation, advocacy
- Search field
- Search button
- Publication cards
- Cover image on every published publication
- Category label
- Publication title
- Summary
- Read more action
- Development plans section
- Issues in action section
- YouTube/free viewing section
- Support form
- WhatsApp contact icon
- Organization Login action
- Reader Sign in/Register action

Responsive behavior:

- Header stacks vertically on small screens.
- Navigation wraps without horizontal overflow.
- Search input and button stack on narrow screens.
- Cards use one column on phones, two on tablets, and three on large screens.
- Floating contact buttons remain reachable and must not cover form controls.

Acceptance criteria:

- Anonymous visitors can browse and search.
- No reader account is required to see public summaries.
- No article without a cover image appears publicly.
- Page has no horizontal overflow at 320px, 375px, 390px, 768px, and desktop widths.

## A2. Publication Details

Route: `/publications/:slug`

Purpose:

- Show the public publication summary.
- Offer access packages.
- Start reader authentication and payment without losing context.

Visible elements:

- Back to Knowledge Hub
- Category
- Cover image
- Publication title
- Summary
- Related YouTube link
- Public/free content where applicable
- Access package cards
- Package name
- Price
- Reading duration or download entitlement
- Continue reading button

Access states:

1. Anonymous visitor with no access
2. Anonymous visitor opening checkout
3. Signed-in reader with no access
4. Payment pending
5. Payment approved and active access
6. Access expired
7. Download entitlement active
8. Rejected payment

Acceptance criteria:

- Continue reading opens the checkout panel first.
- The page must not redirect immediately to staff login.
- If authentication is required, the selected publication and package are preserved.
- After registration/login, the same package checkout reopens.
- Active access shows start/expiry information.
- Expired access clearly explains that a new package is required.

## A3. Reader Registration and Login

Route: `/reader/register`

Registration fields:

- Full name
- Email
- Optional phone number
- Password
- Confirm password

Login fields:

- Email
- Password

Requirements:

- Show server validation errors near the form.
- Do not show generic invalid credentials when a more useful validation message exists.
- Preserve the original publication URL and package ID.
- Return the reader to checkout after successful authentication.
- Explain that the account is used for access tracking and payment records.

## A4. Reader Checkout

This should be a scrollable modal or dedicated checkout page depending on device size.

Payment methods:

### Manual Mobile Money

Display:

- Merchant code: `99485612`
- USSD code: `*165*3#`
- Amount
- Instructions to complete payment
- Last-five transaction-reference field
- Submit payment button

Do not ask for the mobile-money phone number again in this manual-reference step.

Validation:

- Last-five field accepts exactly five characters.
- Submit activates after five characters are entered.
- Duplicate last-five references are rejected server-side.
- A reused pending or paid reference must not be accepted.
- Show a clear error explaining that the reference has already been submitted.

### PesaPal

Display:

- PesaPal branding or clear gateway label
- Visa/card and mobile-money availability
- Amount
- Secure checkout action

Requirements:

- Do not claim payment succeeded before PesaPal confirms it.
- Store provider reference and provider payload.
- Support callback/IPN confirmation.
- Show pending state when the provider response is delayed.
- Show failure state with retry action.

Checkout layout:

- Must fit within the viewport.
- On short screens, the panel must scroll internally.
- Close button must remain reachable.
- Merchant code and last-five field must not be hidden below the fold.
- Do not cover the entire page with an unscrollable panel.

## A5. Reader Payment and Access History

Recommended route: `/reader/account/payments`

Show:

- Publication
- Package
- Amount
- Method
- Payment date
- Status
- Last-five reference, masked where appropriate
- Access start time
- Access expiry time
- Download entitlement

## A6. Reader Support

Show:

- Frequently asked questions
- Contact form
- Payment issue category
- Access issue category
- Message status

# B. Field Officer Screens

## B1. Field Dashboard

Show only operational information:

- Assigned projects
- Forms ready for collection
- Draft submissions
- Unsynced submissions
- Recently submitted records
- Sync status
- Alerts requiring action

Primary action: `Start collection`.

## B2. Project and Form Selection

Show:

- Project name
- Project location
- Form name
- Form version
- Required evidence indicators
- Last updated date

Primary action: `Open form`.

## B3. Data Collection Form

Sections:

1. Household/respondent identification
2. Location and GPS
3. Consent
4. ID capture
5. Signature
6. Respondent photo
7. Questionnaire fields
8. Review
9. Submit/sync

Required features:

- Save draft
- Resume draft
- Offline queue
- Camera capture
- File upload fallback
- GPS capture
- Signature pad
- Validation before submission
- Duplicate check before final submission
- Clear progress indicator
- Do not lose data when navigating between sections

## B4. My Submissions

Show a searchable list with:

- Submission code
- Respondent display name or masked identity
- Village
- Activity date
- Status
- Sync state

Statuses:

- Draft
- Queued
- Submitted
- Reviewed
- Approved
- Returned
- Rejected

## B5. Submission Detail

Show:

- Submission metadata
- Evidence files
- Consent record
- Answers
- Vulnerability score/class
- Review history
- Sync history

# C. Project Officer Screens

## C1. Project Dashboard

Show:

- Project progress
- Submission totals
- Review backlog
- Field officer activity
- Village coverage
- Data-quality warnings
- Recent submissions

## C2. Review Queue

Features:

- Search by submission code, respondent, village, officer
- Filter by status and date
- Sort by newest, oldest, risk score, or review priority
- Bulk-safe review actions where appropriate

## C3. Review Detail

Actions:

- Approve
- Return for correction
- Add review note
- Escalate duplicate warning
- View evidence
- View audit history

# D. M&E Officer Screens

## D1. Organization Dashboard

Show:

- Active projects
- Total submissions
- Reviewed/approved percentage
- High-vulnerability count
- Pending reviews
- Reporting deadlines
- Data-quality alerts

## D2. Project and Form Management

Features:

- Create/edit project
- Manage project officers
- Create/edit form schema
- Form versioning
- Required evidence configuration
- Preview form
- Publish form version

## D3. Data Quality Dashboard

Show:

- Duplicate warnings
- Missing consent
- Missing required photos
- Missing GPS
- Unreviewed submissions
- Outlier scores
- Failed syncs

## D4. Reports

Features:

- Generate weekly, monthly, quarterly, and annual reports
- Select project and reporting period
- Show auto-generated statistics
- Edit narrative fields
- Save draft
- Submit for review
- Approve where permitted
- Download PDF
- Export supporting data

## D5. Knowledge Records

Tabs:

- Development plans
- Stakeholder meetings
- Advocacy issues
- PAR cycles
- Geography

Each tab must have:

- Search
- Status filter
- Create/edit form
- Detail view
- Empty state
- Audit metadata

# E. Executive Director Screens

## E1. Organization Dashboard

Show:

- Organization health
- Project progress
- Submission/review performance
- Report approval backlog
- Finance snapshot
- Publication status
- Support escalations

## E2. Users and Roles

Features:

- Search staff users
- Filter by role and active status
- Create staff user
- Change role
- Deactivate user
- Reset password
- Assign supervisor

Important: reader accounts must never appear in this screen.

## E3. Report Approval

Show:

- Reports awaiting approval
- Reporting period
- Project
- Auto-generated metrics
- Narrative
- Review comments
- Approve/return actions
- Download PDF

## E4. Finance

Show:

- Income
- Expenses
- Balance
- Categories
- Transactions
- Payment accountability
- Import history
- Export/download controls

# F. Reader Manager Screens

## F1. Payment Review

Show pending payments with:

- Reader name
- Reader email
- Publication
- Package
- Amount
- Method
- Last-five reference
- Provider reference
- Submitted time

Actions:

- Approve
- Reject
- Add verification note

## F2. Reader Access Tracking

Filters:

- All
- Active
- Expired
- Download enabled
- Reading only

Columns:

- Reader
- Publication
- Start time
- Expiry time
- Last seen
- Payment status
- Download permission

## F3. Payment Accountability

Show:

- Verified revenue
- Pending payment count
- Rejected payment count
- Revenue by method
- Revenue by publication
- Revenue by date range
- Export reconciliation document

# G. Customer Service Screens

## G1. Help Inbox

Features:

- Search messages
- Filter by category and status
- Reader identity
- Related publication/payment
- Conversation history
- Response field
- Mark resolved
- Escalate to reader manager

# H. Platform Admin Screens

## H1. Organization Management

Features:

- Organization list
- Search organizations
- Onboard organization
- Activate/deactivate organization
- Organization branding
- Primary/secondary colors
- Organization contact details

## H2. Platform Overview

Show aggregated but permission-safe metrics:

- Organizations
- Active users
- Active projects
- Submissions
- Reports
- Publications
- Verified reader revenue
- System health

## H3. Platform Audit Logs

Show:

- Actor
- Organization
- Action
- Entity
- Timestamp
- Before/after metadata where permitted
- Search and filters

## H4. System Configuration

Future settings:

- Payment providers
- Merchant code
- PesaPal credentials status, never display secrets
- Email/SMS settings
- Storage status
- Feature flags
- Data retention settings

## 7. Finance and Payment Data Rules

### Payment statuses

- `pending`: initiated or submitted but not verified
- `paid`: verified and access activated
- `rejected`: reviewed and rejected
- `failed`: provider or technical failure

### Required payment audit fields

- Reader ID
- Publication ID
- Package ID
- Method
- Provider
- Amount
- Currency
- Merchant/provider reference
- Last-five reference for manual MoMo
- Submitted timestamp
- Verified timestamp
- Verifier ID
- Payment payload where permitted

### Duplicate protection

- Normalize last-five references to uppercase.
- Reject a reference already attached to a pending or paid manual-MoMo payment.
- Do not reject a reference solely because it exists on a rejected payment unless policy changes.
- Return a user-readable validation message.
- Log duplicate attempts for audit purposes.

## 8. Responsive Design Requirements

Required test widths:

- 320px
- 375px
- 390px
- 414px
- 768px
- 1024px
- 1280px+

Rules:

- No horizontal page overflow.
- Tables may scroll horizontally inside a contained panel.
- Modals must use internal scrolling and a viewport height limit.
- Buttons must remain reachable and have stable dimensions.
- Forms should become one column on mobile.
- Navigation should collapse into a mobile menu.
- Touch targets should be at least approximately 44px high.
- Long titles and labels must wrap rather than push the layout wider.
- Empty, loading, error, and success states must be designed for every data screen.

## 9. Visual System

Recommended visual language:

- Product name: ImpacTrace
- Public area: ImpacTrace Knowledge Hub
- Primary dark: deep navy/green
- Action green: emerald/teal
- Warning: amber
- Error: rose/red
- Neutral surfaces: white, pale green, slate
- Cards: subtle border, small radius, restrained shadow
- Avoid excessive rounded cards nested inside other cards.
- Use familiar icons for actions.
- Use tooltips for unfamiliar icon-only controls.

## 10. API and Frontend Boundaries

### Public API client

Used by:

- Knowledge Hub
- Reader registration/login
- Publication details
- Public records
- Reader payments
- Reader access
- Public support

Token: `public_token`

### Staff API client

Used by:

- Staff login/logout
- Organizations
- Users
- Projects
- Forms
- Submissions
- Reports
- Finance administration
- Publications administration
- Payment review
- Support inbox

Token: `meal_token`

Never share Authorization defaults between these clients.

## 11. Offline and Mobile App Direction

The future mobile app should prioritize field collection instead of copying every web screen.

### Mobile app bottom navigation for Field Officers

1. Home
2. Collect
3. Drafts
4. Submissions
5. Profile

### Mobile app bottom navigation for Managers

1. Overview
2. Review
3. Reports
4. Finance
5. More

### Mobile app reader navigation

1. Explore
2. Search
3. My access
4. Payments
5. Account

The mobile app must support:

- Offline drafts
- Background sync
- Camera capture
- GPS capture
- Signature capture
- Upload retry
- Clear sync status
- Secure local storage
- Session expiry handling

## 12. Definition of Done for Each Screen

A screen is not complete until:

- Its role permissions are defined.
- Its loading state is implemented.
- Its empty state is implemented.
- Its error state is implemented.
- Its success state is implemented.
- Search/filter behavior is implemented where the list can grow.
- Mobile layout is tested.
- Keyboard navigation works for forms.
- Buttons have disabled/loading states.
- API validation messages are shown clearly.
- Sensitive values are masked where necessary.
- Audit-relevant actions are recorded server-side.
- A focused test or manual verification is documented.

## 13. Suggested Development Order

### Phase 1: Stabilize foundations

- Authentication separation
- Role permissions
- Responsive layout shell
- Shared buttons, forms, cards, tables, status badges
- Loading/error/empty components

### Phase 2: Field operations

- Field dashboard
- Data collection form
- Draft/offline queue
- Submission list/detail
- Review queue

### Phase 3: Management and reporting

- Organization dashboard
- Project/form management
- Data quality
- Reports
- Knowledge records

### Phase 4: Finance and payments

- Finance transactions
- Categories
- Imports/exports
- Reader payments
- PesaPal integration
- Payment accountability
- Access expiry monitoring

### Phase 5: Knowledge Hub

- Publication management
- Cover-photo upload
- Reader checkout
- Reader account
- Access history
- Public search and discovery

### Phase 6: Mobile app

- Field Officer mobile app
- Offline sync
- Manager mobile dashboard
- Reader mobile experience

## 14. Final Product Test Scenarios

1. Anonymous visitor searches and opens a publication.
2. Anonymous visitor opens checkout without being redirected to staff login.
3. Reader registers and returns to the selected package.
4. Reader selects manual mobile money and sees merchant code `99485612`.
5. Reader sees USSD code `*165*3#`.
6. Reader enters five reference characters and the submit button activates.
7. Reusing a pending or paid reference is rejected.
8. Reader selects PesaPal and receives a secure gateway flow or a clear configuration message.
9. Verified payment creates active access.
10. Expired access is shown as expired and does not reveal protected content.
11. Staff users log in through `/login`.
12. Reader accounts do not appear in staff Users.
13. Staff users cannot access reader-only routes as staff permissions.
14. Field officer can save and resume a draft.
15. Manager can review a submission.
16. Finance staff can see payment totals by method.
17. Organization A cannot see Organization B's finance or project data.
18. The public homepage has no horizontal overflow on a 390px viewport.
19. Checkout modal scrolls internally on a short screen.
20. Every major list supports search or filtering.
