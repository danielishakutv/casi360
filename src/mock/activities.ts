import { Activity } from "@/types";

export const mockActivities: Activity[] = [
  { id: "act-001", user: "Daniel Okonkwo", action: "approved", target: "Leave request for George Adekunle", date: "2026-02-24T09:15:00Z", type: "approval" },
  { id: "act-002", user: "Amina Bello", action: "added", target: "New employee: Zainab Abubakar", date: "2026-02-24T08:30:00Z", type: "create" },
  { id: "act-003", user: "Grace Adeyemi", action: "updated", target: "Q1 Budget Report", date: "2026-02-24T08:00:00Z", type: "update" },
  { id: "act-004", user: "Ibrahim Hassan", action: "deployed", target: "System update v1.2", date: "2026-02-23T17:00:00Z", type: "update" },
  { id: "act-005", user: "Helen Ogundimu", action: "processed", target: "February payroll", date: "2026-02-23T16:00:00Z", type: "update" },
  { id: "act-006", user: "Samuel Eze", action: "submitted", target: "Monthly program report", date: "2026-02-23T14:30:00Z", type: "create" },
  { id: "act-007", user: "Patricia Mba", action: "requested", target: "Procurement: 10 laptops", date: "2026-02-23T11:00:00Z", type: "create" },
  { id: "act-008", user: "Nneka Uche", action: "scheduled", target: "Interviews for 3 candidates", date: "2026-02-22T15:00:00Z", type: "create" },
  { id: "act-009", user: "Linda Ogbonna", action: "uploaded", target: "Annual report 2025", date: "2026-02-22T14:00:00Z", type: "create" },
  { id: "act-010", user: "David Okafor", action: "logged in", target: "System access", date: "2026-02-22T09:00:00Z", type: "login" },
  { id: "act-011", user: "Winifred Akinola", action: "completed", target: "Training module: Fire Safety", date: "2026-02-21T16:00:00Z", type: "update" },
  { id: "act-012", user: "Bayo Adewale", action: "submitted", target: "Leave request - 3 days", date: "2026-02-21T14:00:00Z", type: "create" },
  { id: "act-013", user: "Daniel Okonkwo", action: "rejected", target: "Recruitment: 2 program officers", date: "2026-02-20T16:00:00Z", type: "approval" },
  { id: "act-014", user: "Chidinma Eze", action: "updated", target: "January expense report", date: "2026-02-20T11:00:00Z", type: "update" },
  { id: "act-015", user: "George Adekunle", action: "deleted", target: "Outdated logistics record", date: "2026-02-19T15:00:00Z", type: "delete" },
];
