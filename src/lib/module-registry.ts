import { ModuleConfig, UserRole } from "@/types";

export const moduleRegistry: ModuleConfig[] = [
  {
    id: "dashboard",
    name: "Dashboard",
    description: "Overview and analytics dashboard",
    icon: "LayoutDashboard",
    enabled: true,
    routes: [{ path: "/dashboard", label: "Dashboard" }],
    permissions: ["super_admin", "admin", "manager", "staff"],
  },
  {
    id: "hr",
    name: "HR Management",
    description: "Human resources management module",
    icon: "Users",
    enabled: true,
    routes: [
      { path: "/hr", label: "Overview", icon: "Users" },
      { path: "/hr/staff-list", label: "Staff List", icon: "UserCheck" },
      { path: "/hr/departments", label: "Departments", icon: "Building2" },
      { path: "/hr/leaves", label: "Leave Requests", icon: "CalendarOff" },
      { path: "/hr/payroll", label: "Payroll", icon: "Wallet" },
      { path: "/hr/designations", label: "Designations", icon: "Award" },
      { path: "/hr/notes", label: "Notes", icon: "StickyNote" },
      { path: "/hr/settings", label: "Settings", icon: "Settings" },
    ],
    permissions: ["super_admin", "admin", "manager", "staff"],
  },
  {
    id: "communication",
    name: "Communication",
    description: "Email and SMS communication center",
    icon: "MessageSquare",
    enabled: true,
    routes: [
      { path: "/communication", label: "Overview", icon: "MessageSquare" },
      { path: "/communication/send-email", label: "Send Email", icon: "Mail" },
      { path: "/communication/send-sms", label: "Send SMS", icon: "Smartphone" },
      { path: "/communication/send-notice", label: "Send Notice", icon: "Megaphone" },
    ],
    permissions: ["super_admin", "admin", "manager", "staff"],
  },
  {
    id: "settings",
    name: "Settings",
    description: "System settings and configuration",
    icon: "Settings",
    enabled: true,
    routes: [{ path: "/settings", label: "Settings" }],
    permissions: ["super_admin", "admin"],
  },
  {
    id: "profile",
    name: "Profile",
    description: "User profile management",
    icon: "UserCircle",
    enabled: true,
    routes: [{ path: "/profile", label: "Profile" }],
    permissions: ["super_admin", "admin", "manager", "staff"],
  },
  {
    id: "help",
    name: "Help Center",
    description: "Help and FAQ center",
    icon: "HelpCircle",
    enabled: true,
    routes: [{ path: "/help", label: "Help Center" }],
    permissions: ["super_admin", "admin", "manager", "staff"],
  },
];

export function getEnabledModules(enabledMap: Record<string, boolean>): ModuleConfig[] {
  return moduleRegistry.filter((m) => enabledMap[m.id] !== false);
}

export function getModulesForRole(
  modules: ModuleConfig[],
  role: UserRole
): ModuleConfig[] {
  return modules.filter((m) => m.permissions.includes(role));
}
