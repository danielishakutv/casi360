# CASI360 — Complete API & Feature Documentation

> **Care Aid Support Initiative Management System**
> Version: 1.0.0 | Base URL: `https://api.casi360.com/api/v1` | Frontend: `https://casi360.com`

---

## Table of Contents

1. [Application Overview](#1-application-overview)
2. [Complete Feature List](#2-complete-feature-list)
3. [Technical Architecture](#3-technical-architecture)
4. [Authentication (Sanctum SPA)](#4-authentication-sanctum-spa)
5. [API Response Format](#5-api-response-format)
6. [API Endpoints — Authentication](#6-api-endpoints--authentication)
7. [API Endpoints — User Management](#7-api-endpoints--user-management)
8. [API Endpoints — HR Module](#8-api-endpoints--hr-module)
9. [API Endpoints — System](#9-api-endpoints--system)
10. [Role-Based Access Control](#10-role-based-access-control)
11. [Error Codes Reference](#11-error-codes-reference)

---

## 1. Application Overview

CASI360 is a full-stack internal management platform for **Care Aid Support Initiative**, a non-profit organization. It provides modules for human resources, procurement, program management, and internal communications — all in a single dashboard.

| Item | Detail |
|------|--------|
| **Frontend** | Next.js 16 (static export) + TypeScript + Tailwind CSS + shadcn/ui |
| **Backend** | Laravel 11 + PHP 8.2 + MySQL |
| **Auth** | Laravel Sanctum cookie-based SPA authentication |
| **Hosting** | Frontend at `casi360.com`, API at `api.casi360.com` |
| **Primary Keys** | UUID v4 across all tables |

---

## 2. Complete Feature List

### 2.1 Authentication & Security

| Feature | Description |
|---------|-------------|
| **Cookie-Based SPA Auth** | Sanctum stateful session authentication with encrypted cookies across `casi360.com` ↔ `api.casi360.com` |
| **CSRF Protection** | Automatic XSRF-TOKEN cookie handling for all mutating requests |
| **Rate Limiting** | Login: 5 attempts/min, password reset: 3/min, registration: 3/min (all configurable) |
| **Session Management** | Database-backed sessions, 120-minute lifetime, encrypted, HTTP-only, Secure flag |
| **Force Password Change** | New users must change password on first login before accessing any feature |
| **Password Policy** | Min 8 chars, mixed case, numbers, symbols, checked against breach databases (Have I Been Pwned) |
| **Forgot/Reset Password** | Email-based token flow with anti-enumeration (always returns success message) |
| **Login History** | Every login attempt (success/failure) is recorded with IP and user agent |
| **Audit Logging** | All CRUD operations, logins, logouts, role changes, and status changes are audit-logged |
| **Security Headers** | HSTS, X-Frame-Options DENY, X-Content-Type-Options nosniff, Referrer-Policy, Permissions-Policy |
| **Account Deactivation** | Soft-delete (status → inactive) for audit trail preservation |

### 2.2 Dashboard

| Feature | Description |
|---------|-------------|
| **Summary Cards** | At-a-glance counts: total employees, messages, pending approvals, leave requests |
| **Analytics Charts** | Area chart and pie/bar charts for workforce data visualization (Recharts) |
| **Activity Feed** | Recent system activity log |
| **Calendar Widget** | Date-picker calendar for quick date reference |
| **Live Clock** | Real-time clock display |

### 2.3 HR Management (8 pages)

| Feature | Description |
|---------|-------------|
| **HR Overview** | Summary cards linking to all HR sub-modules, recent hires list |
| **Staff List** | Searchable, filterable, paginated employee table with add-employee dialog |
| **Departments** | Department cards with member count, head info, color-coded |
| **Designations** | Designation table with level badges (junior→executive), department filter, add/delete |
| **Leave Requests** | Leave request table with approve/reject actions, type filtering |
| **Approvals** | Approval cards with status indicators and approve/reject workflows |
| **Payroll** | Payroll table with salary breakdown, process/mark-paid actions |
| **Notes** | Pinnable note cards grid with category filter, add/edit/delete dialogs |
| **HR Settings** | 4 sub-tabs: Leave Policy, Payroll Config, Notifications, Work Schedule |

### 2.4 Communication (4 pages)

| Feature | Description |
|---------|-------------|
| **Communication Overview** | Quick action cards, email/SMS statistics, searchable message list with type filter |
| **Send Email** | Compose form with TO, CC, subject, body, attachments, priority, individual/group toggle |
| **Send SMS** | SMS compose with character counter (160 char), group toggle, tips panel |
| **Send Notice** | Notice broadcast with priority levels, audience targeting, channel selection, scheduling, preview |

### 2.5 Procurement (5 pages)

| Feature | Description |
|---------|-------------|
| **Procurement Overview** | PO value statistics, module cards, recent orders, low-stock alerts |
| **Purchase Orders** | PO table with status/priority badges, search, stats dashboard |
| **Vendors** | Vendor cards with ratings, contact info, category/status filtering |
| **Inventory** | Inventory table with stock-level indicators (low/out-of-stock), stats, filters |
| **Requisitions** | Requisition cards with item previews, status/priority badges |

### 2.6 Programs (4 pages)

| Feature | Description |
|---------|-------------|
| **Programs Overview** | Budget overview, module cards, program list with progress bars |
| **Projects** | Project cards with milestone tracking, team avatars, progress indicators |
| **Beneficiaries** | Beneficiary table with gender/status demographic stats, filters |
| **Reports** | Report cards grid with type/status badges, filtering options |

### 2.7 Settings (3 tabs)

| Feature | Description |
|---------|-------------|
| **Appearance** | Light / Dark / System theme toggle with visual previews |
| **Users** | Full user management table: role changes, status toggle, delete users, role simulation (demo) |
| **Data** | Reset mock data, clear localStorage, demo data summary |

### 2.8 Profile (3 tabs)

| Feature | Description |
|---------|-------------|
| **Profile Info** | Edit name, phone, email, department |
| **Password** | Change password with current-password verification |
| **Account** | Danger zone: deactivate own account |

### 2.9 Help Center

| Feature | Description |
|---------|-------------|
| **FAQ** | Searchable FAQ accordion with category filter |
| **Contact Support** | Support contact information |

### 2.10 Platform-Wide UX Features

| Feature | Description |
|---------|-------------|
| **Responsive Design** | Desktop sidebar + mobile sheet drawer |
| **Collapsible Sidebar** | Persistent collapse state (saved to localStorage) |
| **Dark Mode** | Full dark/light/system theme support via next-themes |
| **Notifications** | Bell dropdown with unread count, mark-read, mark-all-read |
| **Toast Alerts** | Sonner toast notifications for all user actions |
| **Global Search** | Search bar in header |
| **User Avatar Dropdown** | Quick access to profile, settings, logout |

---

## 3. Technical Architecture

```
┌─────────────────────────────┐        ┌─────────────────────────────┐
│   casi360.com (Frontend)    │        │ api.casi360.com (Backend)   │
│                             │        │                             │
│  Next.js 16 Static Export   │  HTTPS │  Laravel 11 + Sanctum 4    │
│  Tailwind + shadcn/ui       │◄──────►│  PHP 8.2 + MySQL           │
│  Zustand State Management   │  CORS  │  UUID Primary Keys         │
│                             │        │  Database Sessions          │
└─────────────────────────────┘        └─────────────────────────────┘

Authentication Flow:
1. GET  /sanctum/csrf-cookie         → Sets XSRF-TOKEN + session cookies
2. POST /api/v1/auth/login           → Authenticates, returns user data
3. GET  /api/v1/auth/session         → Validates session on page refresh
4. POST /api/v1/auth/logout          → Invalidates session + CSRF token

Cookie Config:
  Domain:    .casi360.com (shared across subdomains)
  Secure:    true (HTTPS only)
  HttpOnly:  true (not readable by JS)
  SameSite:  Lax
  Encrypted: true
```

---

## 4. Authentication (Sanctum SPA)

### CSRF Initialization

Before making any mutating request (POST/PATCH/PUT/DELETE), the frontend must fetch a CSRF cookie:

```
GET https://api.casi360.com/sanctum/csrf-cookie
Credentials: include
```

This sets two cookies:
- `XSRF-TOKEN` — readable by JS, sent back as `X-XSRF-TOKEN` header
- `casi360_session` — HTTP-only session cookie

### Required Headers

| Header | When | Value |
|--------|------|-------|
| `Accept` | Always | `application/json` |
| `Content-Type` | POST/PATCH/PUT with body | `application/json` |
| `X-XSRF-TOKEN` | POST/PATCH/PUT/DELETE | Decoded value from `XSRF-TOKEN` cookie |

### Required Fetch Options

```javascript
credentials: "include"  // MUST be on EVERY request for cross-domain cookies
```

---

## 5. API Response Format

### Success Response

```json
{
  "success": true,
  "message": "Operation completed successfully",
  "data": { ... }
}
```

### Error Response

```json
{
  "success": false,
  "message": "Descriptive error message",
  "errors": {
    "field_name": ["Validation error message"]
  }
}
```

### Paginated Response

All list endpoints return pagination metadata:

```json
{
  "success": true,
  "message": "Success",
  "data": {
    "items": [ ... ],
    "meta": {
      "current_page": 1,
      "last_page": 5,
      "per_page": 25,
      "total": 112
    }
  }
}
```

---

## 6. API Endpoints — Authentication

### 6.1 Login

```
POST /api/v1/auth/login
```

**Access:** Public (rate limited: 5 requests/min per email+IP)

**Request Body:**

| Field | Type | Required | Validation |
|-------|------|----------|------------|
| `email` | string | Yes | Valid email, max 255 chars |
| `password` | string | Yes | Min 1 char |
| `remember` | boolean | No | Default: false |

**Success Response (200):**

```json
{
  "success": true,
  "message": "Login successful",
  "data": {
    "user": {
      "id": "uuid",
      "name": "John Doe",
      "email": "john@casi.org",
      "role": "admin",
      "department": "Operations",
      "phone": "+234 800 000 0001",
      "avatar": null,
      "status": "active",
      "email_verified_at": "2025-01-01T00:00:00.000000Z",
      "last_login_at": "2026-03-04T10:30:00.000000Z",
      "force_password_change": false,
      "created_at": "2025-01-01T00:00:00.000000Z"
    }
  }
}
```

**Error Responses:**

| Status | Condition |
|--------|-----------|
| 422 | Invalid credentials — `"The provided credentials are incorrect."` |
| 403 | Account deactivated — `"Your account has been deactivated."` |
| 422 | Rate limited — `"Too many login attempts. Please try again in {n} seconds."` |

**Side Effects:**
- Session regenerated (prevents session fixation)
- Login recorded in `login_history` table
- Audit log entry created
- Rate limiter cleared on success

---

### 6.2 Logout

```
POST /api/v1/auth/logout
```

**Access:** Authenticated

**Request Body:** None

**Success Response (200):**

```json
{
  "success": true,
  "message": "Logged out successfully",
  "data": null
}
```

**Side Effects:**
- Session invalidated
- CSRF token regenerated
- Audit log entry created

---

### 6.3 Get Session

```
GET /api/v1/auth/session
```

**Access:** Authenticated

**Query Parameters:** None

**Success Response (200):**

```json
{
  "success": true,
  "message": "Success",
  "data": {
    "authenticated": true,
    "user": {
      "id": "uuid",
      "name": "John Doe",
      "email": "john@casi.org",
      "role": "admin",
      "department": "Operations",
      "phone": "+234 800 000 0001",
      "avatar": null,
      "status": "active",
      "email_verified_at": "2025-01-01T00:00:00.000000Z",
      "last_login_at": "2026-03-04T10:30:00.000000Z",
      "force_password_change": false,
      "created_at": "2025-01-01T00:00:00.000000Z"
    }
  }
}
```

**Error Response (401):** Session expired or invalid.

---

### 6.4 Change Password

```
POST /api/v1/auth/change-password
```

**Access:** Authenticated (allowed even when `force_password_change` is true)

**Request Body:**

| Field | Type | Required | Validation |
|-------|------|----------|------------|
| `current_password` | string | Yes | Must match current password |
| `new_password` | string | Yes | Min 8 chars, mixed case, numbers, symbols, not compromised (HIBP), different from current |
| `new_password_confirmation` | string | Yes | Must match `new_password` |

**Success Response (200):**

```json
{
  "success": true,
  "message": "Password changed successfully",
  "data": null
}
```

**Error Responses:**

| Status | Condition |
|--------|-----------|
| 422 | Current password incorrect |
| 422 | New password same as current |
| 422 | New password doesn't meet policy |

**Side Effects:**
- `password_changed_at` updated
- `force_password_change` set to `false`
- Audit log entry created

---

### 6.5 Forgot Password

```
POST /api/v1/auth/forgot-password
```

**Access:** Public (rate limited: 3 requests/min)

**Request Body:**

| Field | Type | Required | Validation |
|-------|------|----------|------------|
| `email` | string | Yes | Valid email, max 255 chars |

**Success Response (200):**

```json
{
  "success": true,
  "message": "If an account exists with that email, a password reset link has been sent.",
  "data": null
}
```

> **Security:** Always returns success to prevent email enumeration attacks.

---

### 6.6 Reset Password

```
POST /api/v1/auth/reset-password
```

**Access:** Public (rate limited: 3 requests/min)

**Request Body:**

| Field | Type | Required | Validation |
|-------|------|----------|------------|
| `token` | string | Yes | Reset token from email link |
| `email` | string | Yes | Valid email |
| `password` | string | Yes | Min 8 chars, mixed case, numbers, symbols, not compromised |
| `password_confirmation` | string | Yes | Must match `password` |

**Success Response (200):**

```json
{
  "success": true,
  "message": "Password has been reset successfully. You can now log in.",
  "data": null
}
```

**Error Response (422):** Invalid or expired token.

---

### 6.7 Get Profile

```
GET /api/v1/auth/profile
```

**Access:** Authenticated + password changed (ForcePasswordChange middleware)

**Response:** Same user object as session endpoint.

---

### 6.8 Update Profile

```
PATCH /api/v1/auth/profile
```

**Access:** Authenticated + password changed

**Request Body (all optional):**

| Field | Type | Required | Validation |
|-------|------|----------|------------|
| `name` | string | No | Max 255 chars |
| `phone` | string | No | Max 20 chars |
| `department` | string | No | Max 255 chars |

**Success Response (200):**

```json
{
  "success": true,
  "message": "Profile updated successfully",
  "data": {
    "user": { ... }
  }
}
```

**Side Effects:** Audit log records old and new values.

---

### 6.9 Deactivate Own Account

```
DELETE /api/v1/auth/account
```

**Access:** Authenticated + password changed

**Request Body:** None

**Success Response (200):**

```json
{
  "success": true,
  "message": "Account deactivated successfully",
  "data": null
}
```

**Error Response (403):** Super admin accounts cannot self-delete.

**Side Effects:** Status set to `inactive`, session invalidated, audit log created.

---

## 7. API Endpoints — User Management

> All endpoints require **Authenticated + super_admin or admin role**.

### 7.1 Register New User

```
POST /api/v1/auth/register
```

**Access:** Admin only (rate limited: 3 requests/min)

**Request Body:**

| Field | Type | Required | Validation |
|-------|------|----------|------------|
| `name` | string | Yes | Max 255 chars |
| `email` | string | Yes | Valid email, unique in `users` table |
| `password` | string | Yes | Min 8 chars, mixed case, numbers, symbols, not compromised |
| `role` | string | No | One of: `super_admin`, `admin`, `manager`, `staff`. Default: `staff` |
| `department` | string | No | Max 255 chars |
| `phone` | string | No | Max 20 chars |

**Success Response (201):**

```json
{
  "success": true,
  "message": "User created successfully",
  "data": {
    "user": { ... }
  }
}
```

**Side Effects:** New user has `force_password_change: true`.

---

### 7.2 List All Users

```
GET /api/v1/auth/users
```

**Access:** Admin only

**Query Parameters (Filters):**

| Parameter | Type | Description | Example |
|-----------|------|-------------|---------|
| `search` | string | Search by name or email (partial match) | `?search=john` |
| `role` | string | Filter by exact role | `?role=admin` |
| `status` | string | Filter by status: `active`, `inactive` | `?status=active` |
| `department` | string | Filter by department name | `?department=Operations` |
| `per_page` | integer | Items per page (default: 25) | `?per_page=10` |
| `page` | integer | Page number | `?page=2` |

**Sorting:** Ordered by `created_at` descending (newest first).

**Success Response (200):**

```json
{
  "success": true,
  "message": "Success",
  "data": {
    "users": [
      {
        "id": "uuid",
        "name": "John Doe",
        "email": "john@casi.org",
        "role": "admin",
        "department": "Operations",
        "phone": "+234 800 000 0001",
        "avatar": null,
        "status": "active",
        "email_verified_at": "2025-01-01T00:00:00.000000Z",
        "last_login_at": "2026-03-04T10:30:00.000000Z",
        "force_password_change": false,
        "created_at": "2025-01-01T00:00:00.000000Z"
      }
    ],
    "meta": {
      "current_page": 1,
      "last_page": 1,
      "per_page": 25,
      "total": 5
    }
  }
}
```

---

### 7.3 Get User by ID

```
GET /api/v1/auth/users/{id}
```

**Access:** Admin only

**Path Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `id` | UUID | User ID |

**Success Response (200):** Single user object in `data.user`.

**Error Response (404):** User not found.

---

### 7.4 Update User

```
PATCH /api/v1/auth/users/{id}
```

**Access:** Admin only

**Path Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `id` | UUID | User ID |

**Request Body (all optional):**

| Field | Type | Validation |
|-------|------|------------|
| `name` | string | Max 255 chars |
| `email` | string | Valid email, unique (excludes current user) |
| `phone` | string | Max 20 chars |
| `department` | string | Max 255 chars |
| `status` | string | `active` or `inactive` |

**Success Response (200):** Updated user object.

---

### 7.5 Delete (Deactivate) User

```
DELETE /api/v1/auth/users/{id}
```

**Access:** Admin only

**Path Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `id` | UUID | User ID |

**Business Rules:**
- Cannot delete super admin accounts
- Cannot delete your own account via this endpoint

**Success Response (200):**

```json
{
  "success": true,
  "message": "User deactivated successfully",
  "data": null
}
```

---

### 7.6 Change User Role

```
PATCH /api/v1/auth/users/{id}/role
```

**Access:** Admin only

**Path Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `id` | UUID | User ID |

**Request Body:**

| Field | Type | Required | Validation |
|-------|------|----------|------------|
| `role` | string | Yes | One of: `super_admin`, `admin`, `manager`, `staff` |

**Business Rules:**
- Only `super_admin` can assign the `super_admin` role
- Cannot change your own role

**Success Response (200):** Updated user object + message like `"User role updated from staff to admin"`.

---

### 7.7 Change User Status

```
PATCH /api/v1/auth/users/{id}/status
```

**Access:** Admin only

**Path Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `id` | UUID | User ID |

**Request Body:**

| Field | Type | Required | Validation |
|-------|------|----------|------------|
| `status` | string | Yes | `active` or `inactive` |

**Business Rules:** Cannot change your own status.

**Success Response (200):** Updated user object.

---

## 8. API Endpoints — HR Module

### Departments

#### 8.1 List Departments

```
GET /api/v1/hr/departments
```

**Access:** Authenticated (any role)

**Query Parameters:**

| Parameter | Type | Description | Example |
|-----------|------|-------------|---------|
| `search` | string | Search by name, head, or description | `?search=admin` |
| `status` | string | Filter: `active`, `inactive` | `?status=active` |
| `sort_by` | string | Sort field: `name`, `head`, `status`, `created_at` | `?sort_by=name` |
| `sort_dir` | string | Sort direction: `asc`, `desc` (default: `asc`) | `?sort_dir=desc` |
| `per_page` | integer | Items per page (default: 25, use `0` for all) | `?per_page=0` |
| `page` | integer | Page number | `?page=2` |

**Success Response (200):**

```json
{
  "success": true,
  "message": "Success",
  "data": {
    "departments": [
      {
        "id": "uuid",
        "name": "Administration",
        "head": "Adeola Johnson",
        "employee_count": 12,
        "description": "Central administration and operations",
        "color": "#6366F1",
        "status": "active",
        "created_at": "2025-01-01T00:00:00.000000Z",
        "updated_at": "2025-06-15T00:00:00.000000Z"
      }
    ],
    "meta": {
      "current_page": 1,
      "last_page": 1,
      "per_page": 25,
      "total": 10
    }
  }
}
```

---

#### 8.2 Create Department

```
POST /api/v1/hr/departments
```

**Access:** Authenticated + `super_admin`, `admin`, or `manager`

**Request Body:**

| Field | Type | Required | Validation |
|-------|------|----------|------------|
| `name` | string | Yes | Max 255, unique in `departments` table |
| `head` | string | No | Max 255 |
| `description` | string | No | Max 1000 |
| `color` | string | No | Hex color, e.g. `#6366F1` (regex: `/^#[0-9A-Fa-f]{6}$/`) |
| `status` | string | No | `active` or `inactive`. Default: `active` |

**Success Response (201):** Created department object.

---

#### 8.3 Get Department

```
GET /api/v1/hr/departments/{id}
```

**Access:** Authenticated (any role)

**Path Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `id` | UUID | Department ID |

**Success Response (200):** Single department object.

---

#### 8.4 Update Department

```
PATCH /api/v1/hr/departments/{id}
```

**Access:** Authenticated + `super_admin`, `admin`, or `manager`

**Request Body:** Same fields as Create (all optional, `name` uniqueness excludes current record).

**Success Response (200):** Updated department object.

---

#### 8.5 Delete Department

```
DELETE /api/v1/hr/departments/{id}
```

**Access:** Authenticated + `super_admin`, `admin`, or `manager`

**Business Rules:**
- Cannot delete a department that has active (non-terminated) employees
- Returns 422 with message: `"Cannot delete department with active employees. Reassign or terminate them first."`

**Success Response (200):**

```json
{
  "success": true,
  "message": "Department deleted successfully",
  "data": null
}
```

---

### Designations

#### 8.6 List Designations

```
GET /api/v1/hr/designations
```

**Access:** Authenticated (any role)

**Query Parameters:**

| Parameter | Type | Description | Example |
|-----------|------|-------------|---------|
| `search` | string | Search by title, description, or department name | `?search=manager` |
| `status` | string | Filter: `active`, `inactive` | `?status=active` |
| `department_id` | UUID | Filter by department | `?department_id=uuid` |
| `level` | string | Filter: `junior`, `mid`, `senior`, `lead`, `executive` | `?level=senior` |
| `sort_by` | string | Sort field: `title`, `level`, `status`, `created_at` | `?sort_by=level` |
| `sort_dir` | string | Sort direction: `asc`, `desc` | `?sort_dir=desc` |
| `per_page` | integer | Items per page (default: 25, use `0` for all) | `?per_page=0` |
| `page` | integer | Page number | `?page=2` |

**Success Response (200):**

```json
{
  "success": true,
  "message": "Success",
  "data": {
    "designations": [
      {
        "id": "uuid",
        "title": "Senior Program Manager",
        "department_id": "uuid",
        "department": "Programs",
        "level": "senior",
        "employee_count": 3,
        "description": "Leads program delivery and stakeholder coordination",
        "status": "active",
        "created_at": "2025-01-01T00:00:00.000000Z",
        "updated_at": "2025-06-15T00:00:00.000000Z"
      }
    ],
    "meta": { ... }
  }
}
```

---

#### 8.7 Create Designation

```
POST /api/v1/hr/designations
```

**Access:** Authenticated + `super_admin`, `admin`, or `manager`

**Request Body:**

| Field | Type | Required | Validation |
|-------|------|----------|------------|
| `title` | string | Yes | Max 255 |
| `department_id` | UUID | Yes | Must exist in `departments` table |
| `level` | string | Yes | One of: `junior`, `mid`, `senior`, `lead`, `executive` |
| `description` | string | No | Max 1000 |
| `status` | string | No | `active` or `inactive` |

**Success Response (201):** Created designation object (includes `department` name).

---

#### 8.8 Get Designation

```
GET /api/v1/hr/designations/{id}
```

**Access:** Authenticated (any role)

**Success Response (200):** Single designation object with department info.

---

#### 8.9 Update Designation

```
PATCH /api/v1/hr/designations/{id}
```

**Access:** Authenticated + `super_admin`, `admin`, or `manager`

**Request Body:** Same fields as Create (all optional).

**Success Response (200):** Updated designation object.

---

#### 8.10 Delete Designation

```
DELETE /api/v1/hr/designations/{id}
```

**Access:** Authenticated + `super_admin`, `admin`, or `manager`

**Business Rules:**
- Cannot delete a designation held by active (non-terminated) employees
- Returns 422: `"Cannot delete designation with active employees. Reassign them first."`

**Success Response (200):** `"Designation deleted successfully"`

---

### Employees

#### 8.11 List Employees

```
GET /api/v1/hr/employees
```

**Access:** Authenticated (any role)

**Query Parameters:**

| Parameter | Type | Description | Example |
|-----------|------|-------------|---------|
| `search` | string | Search by name, email, staff_id, or phone | `?search=CASI-1001` |
| `status` | string | Filter: `active`, `on_leave`, `terminated` | `?status=active` |
| `department_id` | UUID | Filter by department | `?department_id=uuid` |
| `designation_id` | UUID | Filter by designation | `?designation_id=uuid` |
| `gender` | string | Filter: `male`, `female`, `other` | `?gender=female` |
| `sort_by` | string | Sort field: `name`, `email`, `staff_id`, `status`, `join_date`, `salary`, `created_at` | `?sort_by=join_date` |
| `sort_dir` | string | Sort direction: `asc`, `desc` | `?sort_dir=desc` |
| `per_page` | integer | Items per page (default: 25) | `?per_page=50` |
| `page` | integer | Page number | `?page=3` |

**Success Response (200):**

```json
{
  "success": true,
  "message": "Success",
  "data": {
    "employees": [
      {
        "id": "uuid",
        "staff_id": "CASI-1001",
        "name": "Chinedu Okafor",
        "email": "chinedu@casi.org",
        "phone": "+234 800 000 0010",
        "department_id": "uuid",
        "department": "Programs",
        "designation_id": "uuid",
        "position": "Senior Program Manager",
        "manager": "Adeola Johnson",
        "status": "active",
        "join_date": "2023-06-15",
        "termination_date": null,
        "salary": 450000.00,
        "avatar": null,
        "address": "15 Victoria Island, Lagos",
        "gender": "male",
        "date_of_birth": "1990-04-22",
        "emergency_contact_name": "Amaka Okafor",
        "emergency_contact_phone": "+234 800 111 2222",
        "created_at": "2025-01-01T00:00:00.000000Z",
        "updated_at": "2025-06-15T00:00:00.000000Z"
      }
    ],
    "meta": {
      "current_page": 1,
      "last_page": 1,
      "per_page": 25,
      "total": 25
    }
  }
}
```

---

#### 8.12 Create Employee

```
POST /api/v1/hr/employees
```

**Access:** Authenticated + `super_admin`, `admin`, or `manager`

**Request Body:**

| Field | Type | Required | Validation |
|-------|------|----------|------------|
| `name` | string | Yes | Max 255 |
| `email` | string | Yes | Valid email, unique in `employees` table |
| `phone` | string | No | Max 30 |
| `department_id` | UUID | Yes | Must exist in `departments` table |
| `designation_id` | UUID | Yes | Must exist in `designations` table |
| `manager` | string | No | Max 255 |
| `status` | string | No | `active`, `on_leave`, `terminated`. Default: `active` |
| `join_date` | date | Yes | Valid date (YYYY-MM-DD) |
| `salary` | numeric | No | Min: 0 |
| `avatar` | string | No | Max 500 (URL) |
| `address` | string | No | Max 1000 |
| `gender` | string | No | `male`, `female`, `other` |
| `date_of_birth` | date | No | Must be before today |
| `emergency_contact_name` | string | No | Max 255 |
| `emergency_contact_phone` | string | No | Max 30 |

> `staff_id` is **auto-generated** in format `CASI-XXXX` (starting from CASI-1001, incrementing).

**Success Response (201):** Full employee object with department and designation names.

---

#### 8.13 Get Employee

```
GET /api/v1/hr/employees/{id}
```

**Access:** Authenticated (any role)

**Success Response (200):** Full employee object.

---

#### 8.14 Update Employee

```
PATCH /api/v1/hr/employees/{id}
```

**Access:** Authenticated + `super_admin`, `admin`, or `manager`

**Request Body:** Same fields as Create (all optional). Also accepts:

| Field | Type | Validation |
|-------|------|------------|
| `termination_date` | date | Must be on or after `join_date` |

**Success Response (200):** Updated employee object.

---

#### 8.15 Terminate Employee

```
DELETE /api/v1/hr/employees/{id}
```

**Access:** Authenticated + `super_admin`, `admin`, or `manager`

**Business Rules:**
- Sets `status` to `terminated` and `termination_date` to today
- Returns 422 if employee is already terminated

**Success Response (200):** Terminated employee object.

> Not a hard delete — records are preserved for audit history.

---

#### 8.16 Update Employee Status

```
PATCH /api/v1/hr/employees/{id}/status
```

**Access:** Authenticated + `super_admin`, `admin`, or `manager`

**Request Body:**

| Field | Type | Required | Validation |
|-------|------|----------|------------|
| `status` | string | Yes | `active`, `on_leave`, `terminated` |

**Side Effects:** If status is `terminated` and no `termination_date` is set, auto-fills with today's date.

**Success Response (200):** Updated employee object.

---

#### 8.17 Employee Statistics

```
GET /api/v1/hr/employees/stats
```

**Access:** Authenticated (any role)

**Query Parameters:** None

**Success Response (200):**

```json
{
  "success": true,
  "message": "Success",
  "data": {
    "total": 25,
    "active": 20,
    "on_leave": 3,
    "terminated": 2,
    "by_department": [
      {
        "department_id": "uuid",
        "department": "Programs",
        "count": 8
      },
      {
        "department_id": "uuid",
        "department": "Administration",
        "count": 5
      }
    ]
  }
}
```

---

## 9. API Endpoints — System

### 9.1 Health Check

```
GET /api/v1/health
```

**Access:** Public (no authentication required)

**Success Response (200):**

```json
{
  "success": true,
  "message": "CASI360 API is running",
  "version": "1.0.0",
  "timestamp": "2026-03-04T10:30:00.000000Z"
}
```

---

## 10. Role-Based Access Control

### Roles Hierarchy

| Role | Level | Description |
|------|-------|-------------|
| `super_admin` | Highest | Full system access, can assign super_admin role, cannot be deleted |
| `admin` | High | User management, HR write access, all features |
| `manager` | Medium | HR write access (departments, designations, employees), all read access |
| `staff` | Base | Read-only access to HR data, profile management, communication |

### Permissions Matrix

| Endpoint Group | `super_admin` | `admin` | `manager` | `staff` |
|----------------|:---:|:---:|:---:|:---:|
| Login / Logout / Session | ✅ | ✅ | ✅ | ✅ |
| Change Own Password | ✅ | ✅ | ✅ | ✅ |
| Profile (Read/Update) | ✅ | ✅ | ✅ | ✅ |
| Deactivate Own Account | ❌ (blocked) | ✅ | ✅ | ✅ |
| Register New User | ✅ | ✅ | ❌ | ❌ |
| User Management (CRUD) | ✅ | ✅ | ❌ | ❌ |
| Change User Role | ✅ (all roles) | ✅ (except super_admin) | ❌ | ❌ |
| HR — Read (list/show/stats) | ✅ | ✅ | ✅ | ✅ |
| HR — Write (create/update/delete) | ✅ | ✅ | ✅ | ❌ |
| Health Check | ✅ | ✅ | ✅ | ✅ (public) |

---

## 11. Error Codes Reference

### HTTP Status Codes

| Code | Meaning | When |
|------|---------|------|
| 200 | OK | Successful GET, PATCH, DELETE |
| 201 | Created | Successful POST (resource created) |
| 401 | Unauthorized | Session expired, not logged in |
| 403 | Forbidden | Insufficient role/permissions, account deactivated |
| 404 | Not Found | Resource UUID doesn't exist |
| 419 | CSRF Mismatch | XSRF-TOKEN expired or missing |
| 422 | Validation Error | Invalid input, business rule violation |
| 429 | Too Many Requests | Rate limit exceeded |
| 500 | Server Error | Unexpected backend error |

### Special Response Codes

| Code (in body) | Field | Meaning |
|-----------------|-------|---------|
| `FORCE_PASSWORD_CHANGE` | `code` | User must change password before accessing other endpoints |

### Common Validation Errors

```json
{
  "success": false,
  "message": "Validation failed",
  "errors": {
    "email": ["The email field is required.", "A user with this email already exists."],
    "password": ["The password must be at least 8 characters."],
    "name": ["The name field must not be greater than 255 characters."],
    "department_id": ["The selected department does not exist."],
    "color": ["Color must be a valid hex color code (e.g., #6366F1)."]
  }
}
```

---

*Document generated: March 4, 2026 — CASI360 v1.0.0*
