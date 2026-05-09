<?php

namespace Database\Seeders;

use App\Models\HelpArticle;
use Illuminate\Database\Seeder;

/**
 * Seeds the CASI360 knowledge base with the canonical onboarding and
 * how-to articles. Idempotent — `updateOrCreate` keyed on `title` so
 * re-running the seeder updates content without creating duplicates.
 *
 * Article bodies use a small markdown subset that the frontend
 * renderer understands: headings (#, ##, ###), bold (**…**), italic
 * (*…*), inline `code`, lists (- and 1.), blockquotes (>), and links
 * — both internal ([Staff List](/hr/staff)) and external.
 */
class HelpArticleSeeder extends Seeder
{
    public function run(): void
    {
        $articles = [];
        $cat = function (string $category, array $items) use (&$articles) {
            foreach ($items as $i => [$title, $body]) {
                $articles[] = [
                    'category' => $category,
                    'title' => $title,
                    'content' => trim($body),
                    'sort_order' => $i + 1,
                ];
            }
        };

        /* ─────────────────────────────────────────────────────────── */
        /* 1. Getting Started                                          */
        /* ─────────────────────────────────────────────────────────── */
        $cat('Getting Started', [
            ['Welcome to CASI360', <<<'MD'
**CASI360** is the all-in-one operations platform for the Care Aid Support Initiative. It brings HR, Procurement, Projects, Finance, Operations, Communication and Reporting together in one place so every team works from the same data.

### What you can do in CASI360
- Manage staff, departments and designations.
- Run the full procurement workflow from BoQ to final payment.
- Track projects and budgets end-to-end.
- Approve documents at the Finance and Operations gates.
- Send notices, post in forums and message colleagues.
- Pull reports and audit every action.

### How this Knowledge Base is organised
Articles are grouped by topic on the left. Use the **search box** at the top to jump straight to an answer, or browse a category. Every step links to the exact page in the app — clicking a link will take you there.

If you can't find what you need, scroll to the bottom and **submit a support ticket** — the team will respond.
MD],
            ['Logging In for the First Time', <<<'MD'
1. Open the platform and go to the [Login page](/login).
2. Enter the email and temporary password sent to you by your administrator.
3. On first sign-in you'll be sent to [Change Password](/change-password) — pick a strong password you'll remember.
4. After that you'll land on the [Dashboard](/).

> If you weren't sent credentials, contact your CASI360 administrator. Accounts are created from [User Management](/settings/users) by a super-admin.
MD],
            ['Forgot or Reset Your Password', <<<'MD'
If you can't sign in:

1. Click **Forgot password?** on the [Login page](/login), or open [/forgot-password](/forgot-password) directly.
2. Enter your email — a reset link will be sent if your account exists.
3. Click the link, set a new password on the [Reset Password](/reset-password) page, and you'll be redirected to log in.

> Reset emails expire after a short window. If yours has expired, request a new one. If your email is wrong on file, an admin can update it from [User Management](/settings/users).
MD],
            ['A 5-Minute Tour of the Dashboard', <<<'MD'
The [Dashboard](/) is your home screen. It surfaces the things you need most often:

- **Quick stats** for the modules you have access to.
- **Pending items** that need your attention (approvals, drafts, replies).
- **Recent activity** across the modules you can see.

The **left sidebar** is the main map of the system. The **top bar** shows your profile, the theme toggle, and notifications.

### Tips
- Click the chevron at the top of the sidebar to **collapse** it on small screens.
- The sidebar only shows menus you have access to — if you expect to see something but don't, check the *"I don't see a menu I expected"* article in **Troubleshooting**.
MD],
            ['Switching Between Light and Dark Theme', <<<'MD'
CASI360 supports both light and dark themes.

- Click the **sun / moon icon** in the top-right of the top bar to toggle.
- Your choice is remembered on this device.
- If you've never picked one, the platform follows your operating-system preference.

The theme is purely visual — it doesn't affect data or permissions.
MD],
            ['Updating Your Profile and Avatar', <<<'MD'
Open [Profile](/profile) from the sidebar footer (your name + avatar at the bottom-left).

You can update:
- Display name
- Phone number
- Avatar image
- Password (you can change it any time, not just at first login)

Your **role** and **department** are managed by an administrator in [User Management](/settings/users) — they aren't editable from your own profile.
MD],
        ]);

        /* ─────────────────────────────────────────────────────────── */
        /* 2. Navigating CASI360                                       */
        /* ─────────────────────────────────────────────────────────── */
        $cat('Navigating CASI360', [
            ['Understanding the Sidebar', <<<'MD'
The **left sidebar** is the main navigation. It groups the platform into modules:

- [Dashboard](/) — your home screen
- [HR Management](/hr) — staff, departments, designations
- [Procurement](/procurement) — the full procure-to-pay workflow
- [Projects](/projects) — projects, budgets and reports
- [Finance](/finance) *(Finance department)* — approvals and project spend
- [Operations](/operations) *(Operations department)* — executive sign-off
- [Communication](/communication) — messages, forums, notices
- [Reports](/reports), [Settings](/settings/users), [Help Center](/help)

### Behaviour
- Click a section to expand its children. Only one section is open at a time.
- Click the chevron at the top to collapse the entire sidebar to icons only.
- On mobile, the sidebar slides over the page — tap the menu icon in the top bar.
MD],
            ['Why Some Menus Are Hidden', <<<'MD'
CASI360 hides menus you don't have access to so the interface stays focused. A menu is shown only if **all** of these pass:

1. **Role** — some sections (e.g. [Settings](/settings/users), [Operations](/operations)) require `admin` or `super_admin`.
2. **Department** — *Finance* and *Operations* sections only show for users in those departments (admins always see them).
3. **Permission** — fine-grained permissions like `procurement.boq.view` decide whether a child page appears.

If a colleague sees something you can't, ask an administrator to grant the permission from [Roles & Access](/settings/roles), or to update your role/department in [User Management](/settings/users).
MD],
            ['Searching, Filtering and Pagination', <<<'MD'
Most list pages share the same toolbar pattern:

- A **search box** that filters as you type (with a small debounce so it doesn't fire on every keystroke).
- One or more **filter dropdowns** — status, category, date range.
- A **page size** selector and pagination controls at the bottom.

Search is server-side and case-insensitive. It looks at the most relevant fields for that page (e.g. title and content for articles, code and supplier name for vendors).

> Tip: clear the search box to see everything again — empty values are ignored, not sent as filters.
MD],
            ['Working Offline — What the Banner Means', <<<'MD'
If your device loses internet, an **orange offline banner** appears at the top of the page.

While offline:
- You can still **read** data that's already loaded.
- You **can't save, submit or approve** anything — the platform needs to talk to the server.
- The banner disappears automatically as soon as you reconnect.

> If you were typing into a form when you went offline, finish your edits, wait for the banner to disappear, then submit. The form keeps its content — refreshing the page would lose it.
MD],
        ]);

        /* ─────────────────────────────────────────────────────────── */
        /* 3. HR Management                                            */
        /* ─────────────────────────────────────────────────────────── */
        $cat('HR Management', [
            ['HR Overview', <<<'MD'
The [HR Overview](/hr) summarises headcount, departments and recent HR activity. From here you can drill into:

- [Staff List](/hr/staff)
- [Departments](/hr/departments)
- [Designations](/hr/designations)
- [Notes](/hr/notes)
- [HR Purchase Requests](/hr/purchase-requests)

Visibility of each child page depends on your `hr.*` permissions.
MD],
            ['Adding and Managing Staff', <<<'MD'
Open [Staff List](/hr/staff). You'll see every employee with their department, designation and status.

### To add a new staff member
1. Click **+ New Staff**.
2. Fill in name, email, phone, department, designation and join date.
3. Save. CASI360 auto-creates the linked employee record and (if requested) a user account so they can log in.

### To edit
- Click any row to open the staff profile.
- Update fields and save. Changes are written immediately and recorded in the [Audit Log](/settings/audit-log).

### To deactivate
- Set their **status** to *Terminated*. Their data is preserved for reporting; they can no longer log in.
MD],
            ['Departments', <<<'MD'
Open [Departments](/hr/departments). Departments do two important things in CASI360:

1. They group staff in HR.
2. They **gate access** to certain modules — Finance and Operations menus only show for users in the matching department.

### Adding a department
1. Click **+ New Department**.
2. Give it a name and a **code** (the canonical short identifier — e.g. `FINANCE`, `OPERATIONS`). Department codes drive menu visibility, so use the existing codes when extending an existing function.
3. Save.

> Renaming a department is safe — codes are what gate access, not display names.
MD],
            ['Designations (Job Titles)', <<<'MD'
Open [Designations](/hr/designations). A designation is a job title (e.g. *Programme Officer*, *Finance Lead*) that you can assign to staff.

- Designations are reference data — add, edit or remove them as your structure evolves.
- They appear on staff profiles and in HR reports.
- They don't affect permissions on their own. Permissions come from **roles** and **department** membership.
MD],
            ['HR Notes for the Team', <<<'MD'
[HR Notes](/hr/notes) is a lightweight noticeboard for the HR team — quick reminders, follow-ups, internal context.

- Click **+ New Note** to add one.
- Notes are visible to anyone with `hr.notes.view`.
- Edit or delete a note from its row.

> Notes are not for sensitive personnel records. Confidential information belongs on the staff profile or in a private message.
MD],
            ['Submitting a Purchase Request from HR', <<<'MD'
HR can raise its own purchase requests for HR-specific spend (e.g. recruitment, training).

1. Open [HR Purchase Requests](/hr/purchase-requests).
2. Click **+ New Purchase Request** to land on [Create Purchase Request](/hr/purchase-requests/create).
3. Fill in the items, quantities, justification and (if relevant) the project.
4. **Save as draft** to come back later, or **Submit** to send it into the approval workflow.

The PR then follows the standard procurement flow — see *The Procurement Document Flow* under **Procurement Workflow**.
MD],
        ]);

        /* ─────────────────────────────────────────────────────────── */
        /* 4. Procurement Workflow                                     */
        /* ─────────────────────────────────────────────────────────── */
        $cat('Procurement Workflow', [
            ['The Procurement Document Flow', <<<'MD'
CASI360 mirrors the standard procure-to-pay workflow. Each document feeds the next:

```
BoQ → Purchase Request → RFQ → Purchase Order → GRN → Invoice → Request for Payment
```

1. **[Bill of Quantities](/procurement/boq)** — itemised list for a project or scope.
2. **[Purchase Request](/procurement/purchase-requests)** — formal request to spend.
3. **[Request for Quotation](/procurement/rfq)** — solicit prices from vendors.
4. **[Purchase Order](/procurement/purchase-orders)** — committed order to a chosen vendor.
5. **[Goods Received Note](/procurement/grn)** — confirmation that goods/services arrived.
6. **[Invoice](/procurement/invoices)** — the vendor's bill, matched against the PO/GRN.
7. **[Request for Payment](/procurement/rfp)** — ask Finance/Operations to release funds.

### Approval gates
At each stage the document goes through one or more approvers — typically Procurement → Finance → Operations. Track everything that's waiting on you in [Pending Approvals](/procurement/pending-approvals).

> Tip: every list page in this section supports search, status filters and pagination, and the **+ New** button always sits in the top-right.
MD],
            ['Creating a Bill of Quantities', <<<'MD'
A **Bill of Quantities (BoQ)** is the itemised, priced breakdown of a scope of work — the source of truth that PRs and POs reconcile against.

1. Open [Bill of Quantities](/procurement/boq) and click **+ New BoQ** → [Create BoQ](/procurement/boq/create).
2. Choose **Project-linked** or **Standalone** (see the next article).
3. Add line items: description, unit, quantity, unit price.
4. Save as draft to keep editing, or submit for review.

To edit later, open the BoQ and click **Edit** — you'll be taken to [/procurement/boq/:id/edit](/procurement/boq).
MD],
            ['Project-linked vs Standalone BoQ', <<<'MD'
Pick the right type when creating a BoQ:

### Project-linked
Choose this when the spend belongs to a **specific project**. Pick the project; CASI360 then ties every downstream document (PR, RFQ, PO, GRN, Invoice, RFP) back to that project's budget.

Use this for: project deliverables, programme activities, ring-fenced budgets.

### Standalone
Choose this for general / operational spend not tied to a single project (e.g. office supplies, generic services).

> You can convert *standalone* to *project-linked* by editing the BoQ before it's locked into a PR. After that the link is fixed for traceability.
MD],
            ['Creating a Purchase Request', <<<'MD'
A **Purchase Request (PR)** is the formal ask to spend money on items.

1. Open [Purchase Requests](/procurement/purchase-requests) and click **+ New PR** → [Create PR](/procurement/purchase-requests/create).
2. Pick the **structure** (department) or **project** — this drives the budget the PR draws from.
3. Add items. If a BoQ is linked, items can be **auto-pulled** from the BoQ.
4. Add justification (why this is needed) and supporting attachments.
5. **Save as draft** or **Submit** for approval.

Once submitted, the PR appears in the approver's [Pending Approvals](/procurement/pending-approvals) queue.
MD],
            ['Creating a Request for Quotation', <<<'MD'
An **RFQ** invites one or more vendors to quote on a list of items. CASI360 supports two modes:

### Multi-vendor
Pick specific vendors from your [Vendors directory](/procurement/vendors). Each invited vendor sees only their copy.

### Open call
Don't pick vendors — instead, share the public link with anyone. New vendors can self-register and quote.

### Steps
1. Open [Request for Quotation](/procurement/rfq) and click **+ New RFQ** → [Create RFQ](/procurement/rfq/create).
2. Choose a source PR (or build the line items manually).
3. Pick **multi-vendor** or **open call**.
4. Set the closing date and any notes.
5. Submit. Invited vendors receive an email with a quote-submission link.

Edit later from [/procurement/rfq/:id/edit](/procurement/rfq) — choose **Quick edit** for small tweaks or **Full form** to redo the whole thing.
MD],
            ['Selecting a Vendor and Issuing a Purchase Order', <<<'MD'
Once quotes come in:

1. Open the RFQ from [Request for Quotation](/procurement/rfq).
2. Compare quotes side-by-side.
3. Pick the winning vendor — their quote becomes the basis for the PO.
4. Click **Generate Purchase Order**, or open [Create Purchase Order](/procurement/purchase-orders/create) directly and link the RFQ.
5. Review the PO terms (delivery date, payment terms, totals) and submit for approval.

After approval, the PO is sent to the vendor and the order is officially placed.
MD],
            ['Recording Goods Received (GRN)', <<<'MD'
A **Goods Received Note (GRN)** confirms that what was ordered has arrived.

1. When the delivery comes in, open [Goods Received](/procurement/grn) → [Create GRN](/procurement/grn/create).
2. Pick the matching PO. Its line items pre-fill.
3. Enter the **received quantity** for each line — partial deliveries are supported.
4. Add the receiver's name (only those scoped to the vendor are listed) and any notes about condition or shortfalls.
5. Submit. The GRN locks in the received quantities and unlocks the next step (invoicing).

> Discrepancies (short shipment, damage) should be captured in the notes — they're visible to Finance during invoice matching.
MD],
            ['Recording an Invoice', <<<'MD'
After delivery, the vendor sends an invoice.

1. Open [Invoices](/procurement/invoices) and click **+ New Invoice**.
2. Pick the matching PO — invoice lines reconcile against the PO and any GRNs.
3. Upload the vendor's invoice PDF.
4. Submit.

The invoice is now eligible to be referenced in a **Request for Payment**.
MD],
            ['Submitting a Request for Payment', <<<'MD'
A **Request for Payment (RFP)** asks Finance and Operations to release funds against a recorded invoice.

1. Open [Request for Payment](/procurement/rfp) → [Create RFP](/procurement/rfp/create).
2. Pick the matching invoice. Amounts pre-fill.
3. Add payment instructions and any internal notes.
4. Submit.

The RFP routes through Finance and then Operations for final sign-off — the same gates apply as elsewhere in the workflow.
MD],
            ['Tracking Pending Approvals', <<<'MD'
Open [Pending Approvals](/procurement/pending-approvals) to see every document the system is waiting on you to act on.

Each row shows:
- Document type and reference number
- Current stage in the workflow
- Who submitted it and when
- A direct link to review it

Filter by document type or date to focus on what matters. As soon as you approve or reject, the row drops out of your queue and moves to the next stage.
MD],
        ]);

        /* ─────────────────────────────────────────────────────────── */
        /* 5. Vendors & Inventory                                      */
        /* ─────────────────────────────────────────────────────────── */
        $cat('Vendors & Inventory', [
            ['Adding and Managing Vendors', <<<'MD'
Open [Vendors](/procurement/vendors) to manage the directory.

### To add a vendor
1. Click **+ New Vendor**.
2. Fill in business name, contact person, phone, email, address, tax ID.
3. Pick one or more **categories** (see *Categorising Vendors*).
4. Save.

### To edit or deactivate
- Click any row to open the vendor profile.
- Toggle **status** to *Inactive* if you no longer transact with them — past records stay intact.

> Vendors created from an *open-call* RFQ self-registration land here too — review and approve them before they can quote on future RFQs.
MD],
            ['Categorising Vendors', <<<'MD'
Vendor categories make it faster to invite the right vendors to an RFQ.

Open [Vendor Categories](/procurement/vendor-categories) to:
- Add a new category (e.g. *Stationery*, *IT Services*, *Construction*).
- Rename or delete an existing one.

When creating an RFQ, you can filter the vendor picker by category so you only invite relevant suppliers.
MD],
            ['Maintaining the Inventory Catalog', <<<'MD'
The [Inventory](/procurement/inventory) catalog is the master list of items you can put on a BoQ, PR or RFQ.

- Add an item: name, unit (e.g. *each*, *kg*), category, default unit price.
- Edit to update prices when they change.
- Items in the catalog auto-complete on BoQ/PR/RFQ line entry — much faster than typing them every time.

> Keep names consistent. Two near-identical items will create reporting headaches downstream.
MD],
        ]);

        /* ─────────────────────────────────────────────────────────── */
        /* 6. Projects & Budgets                                       */
        /* ─────────────────────────────────────────────────────────── */
        $cat('Projects & Budgets', [
            ['Projects Overview', <<<'MD'
Open [Projects](/projects) to see the portfolio. The overview surfaces:

- Active projects and their status
- Committed vs. spent vs. remaining budget per project
- Recent project activity

Drill in via [All Projects](/projects/list) or jump straight to [Project Reports](/projects/reports).
MD],
            ['Creating and Managing a Project', <<<'MD'
1. Open [All Projects](/projects/list) and click **+ New Project**.
2. Fill in name, code, start/end dates, total budget, owner.
3. Add **budget categories** (see the next article).
4. Save.

Once created, the project becomes selectable on BoQs, PRs and other procurement documents — and every linked spend rolls up to its budget.
MD],
            ['Project Detail Page', <<<'MD'
Click any project from [All Projects](/projects/list) to open its detail page.

What you'll see:
- **Summary** — total budget, committed (POs raised), spent (paid RFPs), remaining.
- **BoQs and PRs** linked to the project.
- **Activity timeline** — every document change in chronological order.
- **Reports** — drill-downs by category, vendor, period.

Edit project metadata or update its status from the action bar at the top.
MD],
            ['Budget Categories', <<<'MD'
Open [Budget Categories](/projects/budget-categories) to manage the buckets that project spend rolls up to (e.g. *Personnel*, *Travel*, *Materials*).

- Add a category if a new spend type emerges.
- Rename to clarify reporting.
- Delete only if no project is using it.

Categories appear in the budget breakdown on the project detail page and in [Project Reports](/projects/reports).
MD],
            ['Project Reports', <<<'MD'
[Project Reports](/projects/reports) lets you slice spend by:

- Project
- Budget category
- Vendor
- Time period

Export to PDF or Excel for sharing with donors or the board.

> Reports respect your access. If you can't see a project in the report, you don't have view permission for it.
MD],
        ]);

        /* ─────────────────────────────────────────────────────────── */
        /* 7. Finance & Approvals                                      */
        /* ─────────────────────────────────────────────────────────── */
        $cat('Finance & Approvals', [
            ['Finance Overview', <<<'MD'
The [Finance Overview](/finance) is the home page for the Finance department. From here you can reach:

- [Approvals](/finance/approvals) — documents waiting on Finance review.
- [Projects](/finance/projects) — project spend at a glance.
- [Purchase Requests](/finance/purchase-requests) — PRs raised by Finance itself.

> Finance menus are restricted to users in the **Finance** department (and admins).
MD],
            ['Approving Documents in the Finance Queue', <<<'MD'
Open [Finance Approvals](/finance/approvals).

For each document:
1. Click the row to review the full document.
2. Verify amounts, budget availability and supporting attachments.
3. Click **Approve** to advance it to the next stage, or **Reject** with a reason — the originator can amend and resubmit.

Approvals are recorded in the [Audit Log](/settings/audit-log) with the approver, timestamp and any comments.
MD],
            ['Reviewing Project Spend', <<<'MD'
Open [Finance Projects](/finance/projects) for a portfolio view tuned for Finance:

- Burn rate per project
- Variance vs. plan
- Committed vs. paid

Drill into any project to see the full spend timeline. Use this to spot over-runs early and to advise programme leads.
MD],
            ['Submitting a Purchase Request from Finance', <<<'MD'
Finance can raise its own PRs for finance-team spend.

Open [Finance Purchase Requests](/finance/purchase-requests) → **+ New PR** → [Create PR](/finance/purchase-requests/create).

The form is identical to the standard PR form. Once submitted, the PR enters the standard approval flow — see *The Procurement Document Flow* under **Procurement Workflow**.
MD],
        ]);

        /* ─────────────────────────────────────────────────────────── */
        /* 8. Operations & Executive Approvals                         */
        /* ─────────────────────────────────────────────────────────── */
        $cat('Operations & Executive Approvals', [
            ['Operations Overview', <<<'MD'
The [Operations Overview](/operations) is for executive-level oversight — the final gate before funds are released.

It surfaces:
- Documents waiting on the Operations gate
- Recent sign-off activity
- Cross-module exception alerts

> Visibility is restricted to **admins** and users in the **Operations** department.
MD],
            ['Final Sign-off in Operations Approvals', <<<'MD'
Open [Operations Approvals](/operations/approvals) to see Requests for Payment that have cleared Finance and need executive sign-off.

For each item:
1. Click to review — invoice, PO, GRN and any prior comments are all linked.
2. **Approve** to release for payment, or **Reject** with a reason.

Once approved, the RFP is final and the document chain is complete.
MD],
            ['Why You May Not See This Menu', <<<'MD'
The **Operations** menu is gated by:

- **Role** — `admin` or `super_admin`, **or**
- **Department** — your user record's department equals `OPERATIONS` (or matches one of the configured display names like *"Operations & Logistics"*).

If you should have access but don't see it, ask a super-admin to check your department in [User Management](/settings/users) and (if needed) the department code in [Departments](/hr/departments).
MD],
        ]);

        /* ─────────────────────────────────────────────────────────── */
        /* 9. Communication                                            */
        /* ─────────────────────────────────────────────────────────── */
        $cat('Communication', [
            ['Sending an Internal Message', <<<'MD'
Open [Messages](/communication/messages) for one-to-one and small-group conversations.

- Click **+ New Message**, pick recipients and write your note.
- Replies thread under the original message.
- Attachments are supported.

> For broadcast announcements use **Notices** instead — see the related article in this section.
MD],
            ['Posting to a Forum', <<<'MD'
[Forums](/communication/forums) are topic-based discussion boards (e.g. *Field Operations*, *General*).

- Pick a forum and click **+ New Topic** to start a thread.
- Reply inline; threads are visible to anyone with `communication.forums.view`.
- Pin or close a thread if you have moderation rights.
MD],
            ['Broadcasting a Notice to Staff', <<<'MD'
Open [Notices](/communication/notices) to send a one-to-many announcement.

1. Click **+ New Notice**.
2. Write title and body. The body supports rich formatting.
3. Pick the **audience** — everyone, a department, a single person, or a custom group.
4. Send. Recipients see the notice on their dashboard and can be sent an email/SMS depending on system settings.

Notices are kept in the archive — useful when someone says "I missed it".
MD],
        ]);

        /* ─────────────────────────────────────────────────────────── */
        /* 10. Reports                                                 */
        /* ─────────────────────────────────────────────────────────── */
        $cat('Reports', [
            ['Where Each Report Lives', <<<'MD'
CASI360 has reports embedded in the modules they belong to, plus a cross-module home at [Reports](/reports).

- **Procurement KPIs** — top of [Procurement Overview](/procurement).
- **Project burn-down and variance** — [Project Reports](/projects/reports).
- **Finance audit trail** — [Finance Approvals](/finance/approvals) (history tab).
- **System-wide audit** — [Audit Log](/settings/audit-log) (super-admin).
- **HR headcount** — [HR Overview](/hr).

If a report you expected isn't visible, you may not have the underlying view permission — check with an admin.
MD],
            ['Exporting and Sharing Reports', <<<'MD'
Most report views support **Export** in the top-right:

- **PDF** for sharing externally (donors, board).
- **Excel/CSV** for further analysis.

The export contains exactly what's on screen — apply your filters first, then export.

> Exports are generated server-side and respect your access. If you can't see something in the UI, it won't be in the export either.
MD],
        ]);

        /* ─────────────────────────────────────────────────────────── */
        /* 11. Settings & Administration                               */
        /* ─────────────────────────────────────────────────────────── */
        $cat('Settings & Administration', [
            ['User Management', <<<'MD'
Open [User Management](/settings/users) to manage who can sign in.

### Common tasks
- **Invite a new user** — click **+ New User**, fill in name, email, role, department. They get a temporary password and a forced reset on first login.
- **Change role / department** — click a user row.
- **Deactivate** — set status to *Inactive*. They can no longer log in but their data history is preserved.
- **Reset password** — sends them a fresh temporary password.

Every change here is recorded in the [Audit Log](/settings/audit-log).
MD],
            ['Roles & Access', <<<'MD'
[Roles & Access](/settings/roles) is where the permission matrix lives.

- Roles are defined globally (`super_admin`, `admin`, `manager`, `staff`, etc.).
- Each role has a checklist of permissions (e.g. `procurement.boq.view`, `hr.staff.update`).
- Toggle a checkbox to grant or revoke a permission for that role.

> `super_admin` always has every permission — checkboxes for that role are locked. Changes take effect immediately for all users in the role.
MD],
            ['System Settings', <<<'MD'
[System Settings](/settings/general) holds organisation-wide configuration:

- Organisation name, acronym, contact info.
- Localisation (currency, date format, timezone).
- Notification and security defaults.

Edits are scoped to **super-admin** only and recorded in the audit log.
MD],
            ['Reading the Audit Log', <<<'MD'
The [Audit Log](/settings/audit-log) records every meaningful change in the system: logins, document creates/updates, approvals, role changes, settings edits.

### How to use it
- **Filter** by user, action type, target (e.g. *help_article*, *purchase_request*) or date range.
- Click a row to see the **before / after** snapshot for the change.
- Export for compliance reviews.

> The log is append-only. Entries cannot be edited or deleted.
MD],
            ['Data & Backup', <<<'MD'
[Data & Backup](/settings/data) provides:

- **Export** — pull a JSON archive of selected modules for offline backup.
- **Import** — bring data back from a previous export (use carefully — this is a destructive operation when applied to existing tables).
- **On-demand backup** — trigger a server-side database dump.

> Always run a backup before a major upgrade or a bulk import. Restoring is much easier than reconstructing.
MD],
        ]);

        /* ─────────────────────────────────────────────────────────── */
        /* 12. Troubleshooting & FAQs                                  */
        /* ─────────────────────────────────────────────────────────── */
        $cat('Troubleshooting & FAQs', [
            ["I Don't See a Menu I Expected", <<<'MD'
Three things gate menu visibility:

1. **Role** — admin-only menus (e.g. [Settings](/settings/users)) need `admin` or `super_admin`.
2. **Department** — *Finance* and *Operations* sections need the matching department on your user record.
3. **Permission** — most child pages need a specific permission like `procurement.boq.view`.

### What to do
- Open [Profile](/profile) and confirm your role and department.
- Ask an admin to grant the permission from [Roles & Access](/settings/roles), or to fix your department in [User Management](/settings/users).
- Hard-refresh after a permission change so the sidebar re-reads it.
MD],
            ["My Approval Isn't Moving", <<<'MD'
Documents move through stages: Procurement → Finance → Operations (with variations per document type). If yours feels stuck:

1. Open the document and check the **stage** indicator at the top.
2. Check who the current approver is — they may be on leave.
3. Look at the **history** tab for any rejection comment you missed.
4. If rejected, address the reason and resubmit.
5. If genuinely stalled, submit a support ticket below or message the approver via [Messages](/communication/messages).
MD],
            ["I Can't Log In", <<<'MD'
- **Wrong password?** Reset it from [Forgot Password](/forgot-password).
- **Forced password change loop?** Make sure your new password meets the complexity rules (length, mix of cases, number/symbol).
- **Account locked or inactive?** An admin can re-enable it from [User Management](/settings/users).
- **Email isn't right?** An admin can update your email there too — your data isn't tied to the email, so you keep all your history.

If none of the above helps, submit a ticket below.
MD],
            ['How Do I Get a Permission Granted?', <<<'MD'
Permissions follow your **role**. To get a new permission:

1. Identify the exact permission you need (most modules surface this in the message when access is denied — e.g. `procurement.rfq.view`).
2. Submit a support ticket below describing what you're trying to do.
3. A super-admin can grant the permission to your role from [Roles & Access](/settings/roles), or change your role in [User Management](/settings/users).

> Permission changes take effect immediately, but you may need to refresh the page to see new menus.
MD],
            ['Where Did My Draft Go?', <<<'MD'
Drafts and submitted documents live in the same list — filter by **status = draft** to find yours.

- Open the draft to continue editing on its **edit** page (e.g. [/procurement/boq/:id/edit](/procurement/boq) for BoQ).
- *Save as draft* keeps editing open. *Submit* locks it into the workflow.
- If you can't find a draft you know you saved, check whether you may have created it under a different module (e.g. an HR PR appears in [HR Purchase Requests](/hr/purchase-requests), not in [Procurement Purchase Requests](/procurement/purchase-requests)).
MD],
        ]);

        /* ─────────────────────────────────────────────────────────── */
        /* 13. Glossary                                                */
        /* ─────────────────────────────────────────────────────────── */
        $cat('Glossary', [
            ['Procurement Glossary', <<<'MD'
Quick definitions for the acronyms used across the platform:

- **BoQ** — *Bill of Quantities*. Itemised, priced breakdown of a scope of work.
- **PR** — *Purchase Request*. Formal request to spend.
- **RFQ** — *Request for Quotation*. Solicitation sent to vendors for prices.
- **PO** — *Purchase Order*. Committed order to a chosen vendor.
- **GRN** — *Goods Received Note*. Confirmation that ordered goods arrived.
- **Invoice** — Vendor's bill, matched against the PO and GRN.
- **RFP** — *Request for Payment*. Asks Finance/Operations to release funds against an invoice.
- **Approval** — A sign-off step. Documents pass through one or more approval gates.
- **Department** — A team grouping in HR (e.g. Finance, Operations). Drives menu visibility.
- **Designation** — A job title (e.g. *Programme Officer*).
- **Permission** — A fine-grained right (e.g. `procurement.boq.view`) attached to a role.
- **Role** — A named bundle of permissions (e.g. `admin`, `staff`).
- **Audit Log** — Append-only record of every meaningful change.
MD],
        ]);

        /* ─────────────────────────────────────────────────────────── */
        /* Persist                                                     */
        /*                                                             */
        /* `firstOrCreate` is intentional: once an article exists,     */
        /* editors may have updated it through the Help Center UI.     */
        /* Re-running the seeder must NOT clobber those edits — it     */
        /* only fills in articles that don't yet exist. To re-seed     */
        /* a single article, delete it from the UI first.              */
        /* ─────────────────────────────────────────────────────────── */
        foreach ($articles as $a) {
            HelpArticle::firstOrCreate(
                ['title' => $a['title']],
                [
                    'category' => $a['category'],
                    'content' => $a['content'],
                    'sort_order' => $a['sort_order'],
                    'status' => 'published',
                ]
            );
        }
    }
}
