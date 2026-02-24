import { Approval } from "@/types";

export const mockApprovals: Approval[] = [
  { id: "apr-001", type: "Procurement", requestedBy: "Patricia Mba", description: "Purchase of 10 laptops for new staff", status: "pending", date: "2026-02-24", priority: "high" },
  { id: "apr-002", type: "Budget", requestedBy: "Helen Ogundimu", description: "Q2 budget allocation amendment", status: "pending", date: "2026-02-23", priority: "high" },
  { id: "apr-003", type: "Travel", requestedBy: "Samuel Eze", description: "Field visit to Kano state - 5 days", status: "pending", date: "2026-02-22", priority: "medium" },
  { id: "apr-004", type: "Training", requestedBy: "Winifred Akinola", description: "Staff development workshop registration", status: "approved", date: "2026-02-20", priority: "low" },
  { id: "apr-005", type: "Recruitment", requestedBy: "Nneka Uche", description: "Hire 2 additional program officers", status: "rejected", date: "2026-02-18", priority: "medium" },
];
