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
  forcePasswordChange?: boolean;
  lastLoginAt?: string;
  emailVerifiedAt?: string;
}

/**
 * Maps snake_case API user response to camelCase frontend User type.
 */
export function mapApiUser(apiUser: Record<string, unknown>): User {
  return {
    id: apiUser.id as string,
    name: apiUser.name as string,
    email: apiUser.email as string,
    role: apiUser.role as UserRole,
    avatar: (apiUser.avatar as string) || undefined,
    department: (apiUser.department as string) || undefined,
    phone: (apiUser.phone as string) || undefined,
    status: apiUser.status as "active" | "inactive",
    createdAt: (apiUser.created_at as string) || "",
    forcePasswordChange: !!apiUser.force_password_change,
    lastLoginAt: (apiUser.last_login_at as string) || undefined,
    emailVerifiedAt: (apiUser.email_verified_at as string) || undefined,
  };
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

// Procurement types
export interface Vendor {
  id: string;
  name: string;
  email: string;
  phone: string;
  address: string;
  category: "goods" | "services" | "works" | "consulting";
  status: "active" | "inactive" | "blacklisted";
  rating: number;
  totalOrders: number;
  createdAt: string;
}

export interface PurchaseOrder {
  id: string;
  title: string;
  vendorId: string;
  vendorName: string;
  items: PurchaseOrderItem[];
  totalAmount: number;
  status: "draft" | "pending" | "approved" | "ordered" | "delivered" | "cancelled";
  priority: "low" | "medium" | "high" | "urgent";
  requestedBy: string;
  approvedBy?: string;
  createdAt: string;
  expectedDelivery: string;
}

export interface PurchaseOrderItem {
  name: string;
  quantity: number;
  unitPrice: number;
  total: number;
}

export interface InventoryItem {
  id: string;
  name: string;
  category: string;
  quantity: number;
  unit: string;
  reorderLevel: number;
  unitCost: number;
  location: string;
  lastRestocked: string;
  status: "in_stock" | "low_stock" | "out_of_stock";
}

export interface Requisition {
  id: string;
  title: string;
  department: string;
  requestedBy: string;
  items: { name: string; quantity: number; estimatedCost: number }[];
  totalEstimate: number;
  status: "draft" | "submitted" | "approved" | "rejected" | "fulfilled";
  priority: "low" | "medium" | "high" | "urgent";
  justification: string;
  createdAt: string;
}

// Programs types
export interface Program {
  id: string;
  name: string;
  description: string;
  category: "education" | "health" | "livelihood" | "advocacy" | "emergency" | "capacity_building";
  status: "planning" | "active" | "completed" | "suspended" | "closed";
  startDate: string;
  endDate: string;
  budget: number;
  spent: number;
  manager: string;
  beneficiaryCount: number;
  location: string;
  progress: number;
}

export interface Project {
  id: string;
  programId: string;
  programName: string;
  name: string;
  description: string;
  status: "not_started" | "in_progress" | "completed" | "on_hold" | "cancelled";
  startDate: string;
  endDate: string;
  budget: number;
  spent: number;
  manager: string;
  team: string[];
  milestones: Milestone[];
  progress: number;
}

export interface Milestone {
  id: string;
  title: string;
  dueDate: string;
  completed: boolean;
}

export interface Beneficiary {
  id: string;
  name: string;
  age: number;
  gender: "male" | "female" | "other";
  location: string;
  programIds: string[];
  programNames: string[];
  registrationDate: string;
  status: "active" | "inactive" | "graduated";
  contact: string;
}

export interface ProgramReport {
  id: string;
  programId: string;
  programName: string;
  title: string;
  type: "monthly" | "quarterly" | "annual" | "impact" | "financial";
  period: string;
  author: string;
  status: "draft" | "submitted" | "approved";
  createdAt: string;
  summary: string;
}
