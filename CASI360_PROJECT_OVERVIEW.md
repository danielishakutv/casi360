CASI360
Comprehensive Project Overview

Internal Modular Management System for Care Aid Support Initiative

1. Executive Summary

CASI360 is a modular internal management system designed to streamline operations for Care Aid Support Initiative (CASI). It will serve as a centralized digital platform for administration, HR, communication, procurement, finance, program management, and organizational reporting.

The system is designed to be:

Modular and extensible

Secure and role-based

Scalable for future SaaS expansion

Cost-optimized (Supabase free tier aware)

Backend-flexible (Supabase + PHP hybrid architecture)

Version 1 focuses on building a fully functional frontend using mock/demo data before backend integration.

2. Project Vision

CASI360 is not just a dashboard.
It is a platform framework.

The long-term vision is to build:

A unified internal operating system for CASI

A scalable NGO management system

A potential SaaS product for other organizations

A secure, role-driven digital workspace

The architecture must support future modules without structural redesign.

3. System Architecture Overview
Phase 1 (Current Focus – Frontend Only)

Frontend:

Next.js (App Router)

TypeScript (strict)

TailwindCSS

ShadCN UI

Zustand (state management)

React Hook Form + Zod

Data Source:

Mock JSON files

Local TypeScript services

No real backend integration yet.

Phase 2 (Backend Integration)

Hybrid architecture:

Supabase will handle:

Authentication

PostgreSQL database

Row Level Security (RLS)

Lightweight structured data

Custom PHP backend will handle:

File storage

Bulk email

SMS services

PDF generation

Background jobs

Heavy processing

This keeps Supabase usage within free-tier limits.

4. Core Design Principles
4.1 Modular Architecture

The system must allow modules to be developed independently and plugged into the core dashboard.

Each module must contain:

module.config.ts

navigation definition

route definitions

permission definitions

internal components

mockData.ts

The sidebar dynamically renders based on enabled modules.

4.2 Role-Based Access Control (RBAC)

User Roles:

Super Admin

Admin

Manager

Staff

Permissions will be granular per module.

Even in demo mode, permission simulation must be implemented.

4.3 Cost-Optimized Infrastructure Strategy

To remain within Supabase free tier:

Supabase will NOT store:

Large file uploads

PDFs

Media assets

Heavy logs

Supabase WILL store:

Structured records

User accounts

Roles

Module metadata

Transaction summaries

Audit metadata

Files will be stored on:

VPS server

Or S3-compatible storage

Supabase will store only file metadata and URLs.

5. Core Application Modules (Version 1 Scope)

5.1 Dashboard

Features:

Today's date

Live clock

Calendar

Summary widgets

Activity feed

Charts (demo data)

5.2 Communication Module

Features:

Email (individual & group)

SMS (individual & group)

Compose modal

Sent messages

Draft messages

Mock send notifications

5.3 Settings

Features:
- Theme toggle (light/dark/system)
- Feature toggles (enable/disable modules)
- User management (mock)
- Role assignment
- System configurations
- Data reset (demo mode)

5.4 Profile

Features:
- Update personal information
- Change password
- Delete account (mock confirmation)

5.5 Help Center

Features:
- Searchable FAQs
- Tutorial categories
- Expandable answers
- Demo help articles

5.6 HR Module (First Major Functional Module)

Features:
- Employee management
- Departments
- Leave requests
- Approval workflows
- Employee profile pages
- Filtering & search
- Status badges

All powered by mock data in v1.

6. UI/UX Requirements

The interface must:

Feel like modern SaaS (clean, minimal, structured)

Support collapsible sidebar

Support icon-only navigation mode

Use consistent spacing and typography

Use soft shadows and rounded components

Include skeleton loaders

Include toast notifications

Include confirmation dialogs

Include empty states

Be fully responsive

7. Mock Data Strategy (Frontend Phase)

The system must include realistic demo data:

25 employees

10 departments

50 notifications

20 messages

5 users

10 leave requests

5 approval requests

Data must be structured as if coming from real APIs.

This ensures backend integration later will be seamless.

8. State Management Strategy

Global state:

Auth session (mock)

Current user

Theme mode

Notifications

Module registry

Local state:

Form handling

Filtering

UI interactions

Zustand will manage global state.

9. Security Design (Planned for Backend Phase)

JWT authentication

httpOnly cookies

Row Level Security (Supabase)

Input validation

Password hashing

Rate limiting

Audit logging

Role-based route protection

Even in frontend demo mode, route protection must be simulated.

10. Performance Strategy

Code splitting per module

Lazy loading modules

Suspense boundaries

Optimized component reuse

Minimal re-renders

Efficient state usage

11. File Handling Strategy (Future Backend Phase)

File upload flow:

User uploads file

File sent to PHP backend

Backend stores file

Backend returns secure URL

Supabase stores file metadata

No large files stored in Supabase.

12. Development Phases
Phase 1 – Frontend Complete (Current)

Full UI system

Modular architecture

Mock authentication

Role simulation

HR module demo

Communication module demo

Goal:
Validate UX and architecture.

Phase 2 – Supabase Integration

Replace mock auth

Connect database

Implement RLS

Replace mock CRUD

Phase 3 – PHP Backend Integration

File storage

Email service

SMS service

PDF generation

Background jobs

Phase 4 – Additional Modules

Procurement

Finance

Programs

Reports

Analytics

13. Long-Term Scalability

CASI360 must be built so it can later support:

Multi-organization (multi-tenant)

SaaS model

API versioning

Advanced reporting

Data export

Backup and restore

The architecture must avoid hardcoded organization logic.

14. Risks & Mitigation

Risk: Supabase free tier overuse
Mitigation: Offload heavy tasks to VPS

Risk: Poor schema design
Mitigation: Plan database before integration

Risk: Permission complexity
Mitigation: Clear RBAC matrix

Risk: Overengineering v1
Mitigation: Build frontend first

15. Strategic Positioning

CASI360 is being built:

As a real internal system

With SaaS-level discipline

With cost awareness

With scalability in mind

This is not a simple CRUD dashboard.

It is a platform foundation.

16. Final Objective

Deliver a fully functional frontend system that:

Looks production-ready

Simulates real operations

Has modular structure

Has proper role simulation

Is backend-ready

Once frontend is validated, backend integration will be structured, clean, and predictable.