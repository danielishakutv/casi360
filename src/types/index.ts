export type UserRole = "super_admin" | "admin" | "manager" | "staff";

export interface User {
  id: string;
  name: string;
  email: string;
  role: UserRole;
  avatar?: string;
  department?: string;
  phone?: string;
  status: "active" | "inactive";
  createdAt: string;
}

export interface Employee {
  id: string;
  name: string;
  email: string;
  phone: string;
  department: string;
  position: string;
  status: "active" | "on_leave" | "terminated";
  joinDate: string;
  avatar?: string;
  salary?: number;
  manager?: string;
}

export interface Department {
  id: string;
  name: string;
  head: string;
  employeeCount: number;
  description: string;
  color: string;
}

export interface LeaveRequest {
  id: string;
  employeeId: string;
  employeeName: string;
  type: "annual" | "sick" | "personal" | "maternity" | "paternity";
  startDate: string;
  endDate: string;
  status: "pending" | "approved" | "rejected";
  reason: string;
  appliedDate: string;
}

export interface Approval {
  id: string;
  type: string;
  requestedBy: string;
  description: string;
  status: "pending" | "approved" | "rejected";
  date: string;
  priority: "low" | "medium" | "high";
}

export interface Message {
  id: string;
  type: "email" | "sms";
  to: string;
  from: string;
  subject?: string;
  body: string;
  status: "sent" | "draft" | "failed";
  date: string;
  isGroup: boolean;
  read: boolean;
}

export interface Notification {
  id: string;
  title: string;
  message: string;
  type: "info" | "success" | "warning" | "error";
  read: boolean;
  date: string;
  link?: string;
}

export interface Activity {
  id: string;
  user: string;
  action: string;
  target: string;
  date: string;
  type: "create" | "update" | "delete" | "login" | "approval";
}

export interface FAQ {
  id: string;
  question: string;
  answer: string;
  category: string;
}

export interface ModuleConfig {
  id: string;
  name: string;
  description: string;
  icon: string;
  enabled: boolean;
  routes: ModuleRoute[];
  permissions: UserRole[];
}

export interface ModuleRoute {
  path: string;
  label: string;
  icon?: string;
}

export interface SidebarItem {
  title: string;
  href: string;
  icon: string;
  children?: SidebarItem[];
  badge?: number;
  roles?: UserRole[];
}

export interface Designation {
  id: string;
  title: string;
  department: string;
  level: "junior" | "mid" | "senior" | "lead" | "executive";
  employeeCount: number;
  description: string;
  createdAt: string;
}

export interface PayrollRecord {
  id: string;
  employeeId: string;
  employeeName: string;
  department: string;
  baseSalary: number;
  allowances: number;
  deductions: number;
  netPay: number;
  payPeriod: string;
  status: "paid" | "pending" | "processing";
  payDate: string;
}

export interface Note {
  id: string;
  title: string;
  content: string;
  category: "general" | "meeting" | "policy" | "reminder" | "personal";
  author: string;
  isPinned: boolean;
  createdAt: string;
  updatedAt: string;
}

export interface ChartData {
  name: string;
  value: number;
  fill?: string;
}
