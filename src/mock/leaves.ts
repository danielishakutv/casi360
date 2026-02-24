import { LeaveRequest } from "@/types";

export const mockLeaveRequests: LeaveRequest[] = [
  { id: "leave-001", employeeId: "emp-007", employeeName: "George Adekunle", type: "annual", startDate: "2026-02-24", endDate: "2026-03-07", status: "pending", reason: "Family vacation", appliedDate: "2026-02-20" },
  { id: "leave-002", employeeId: "emp-020", employeeName: "Ugochi Onyekachi", type: "sick", startDate: "2026-02-22", endDate: "2026-02-28", status: "approved", reason: "Medical procedure recovery", appliedDate: "2026-02-21" },
  { id: "leave-003", employeeId: "emp-002", employeeName: "Bayo Adewale", type: "personal", startDate: "2026-03-01", endDate: "2026-03-03", status: "pending", reason: "Personal matters", appliedDate: "2026-02-22" },
  { id: "leave-004", employeeId: "emp-010", employeeName: "Joy Adebayo", type: "annual", startDate: "2026-03-10", endDate: "2026-03-20", status: "approved", reason: "Annual vacation", appliedDate: "2026-02-15" },
  { id: "leave-005", employeeId: "emp-014", employeeName: "Nneka Uche", type: "maternity", startDate: "2026-04-01", endDate: "2026-06-30", status: "approved", reason: "Maternity leave", appliedDate: "2026-02-01" },
  { id: "leave-006", employeeId: "emp-004", employeeName: "David Okafor", type: "sick", startDate: "2026-02-18", endDate: "2026-02-19", status: "approved", reason: "Flu", appliedDate: "2026-02-18" },
  { id: "leave-007", employeeId: "emp-011", employeeName: "Kalu Chukwuma", type: "personal", startDate: "2026-03-05", endDate: "2026-03-06", status: "rejected", reason: "Wedding ceremony", appliedDate: "2026-02-20" },
  { id: "leave-008", employeeId: "emp-018", employeeName: "Sandra Okoro", type: "annual", startDate: "2026-04-15", endDate: "2026-04-25", status: "pending", reason: "Travel abroad", appliedDate: "2026-02-23" },
  { id: "leave-009", employeeId: "emp-021", employeeName: "Victor Emenike", type: "paternity", startDate: "2026-03-15", endDate: "2026-03-29", status: "pending", reason: "Paternity leave", appliedDate: "2026-02-22" },
  { id: "leave-010", employeeId: "emp-006", employeeName: "Fatima Ibrahim", type: "sick", startDate: "2026-02-10", endDate: "2026-02-12", status: "approved", reason: "Doctor appointment and recovery", appliedDate: "2026-02-10" },
];
