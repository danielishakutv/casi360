"use client";

import * as React from "react";
import Link from "next/link";
import { usePathname } from "next/navigation";
import {
  LayoutDashboard,
  Users,
  UserCheck,
  Building2,
  CalendarOff,
  MessageSquare,
  Settings,
  HelpCircle,
  ChevronLeft,
  ChevronRight,
  ChevronDown,
  LogOut,
  Wallet,
  Award,
  StickyNote,
  Mail,
  Smartphone,
  Megaphone,
  ShoppingCart,
  ClipboardList,
  Store,
  Package,
  FileInput,
  Target,
  FolderKanban,
  Heart,
  BarChart3,
} from "lucide-react";
import { cn } from "@/lib/utils";
import { useAuthStore } from "@/store/auth-store";
import { useSidebarStore } from "@/store/sidebar-store";
import { Button } from "@/components/ui/button";
import { ScrollArea } from "@/components/ui/scroll-area";
import {
  Tooltip,
  TooltipContent,
  TooltipTrigger,
} from "@/components/ui/tooltip";

/* ─── Static Navigation ──────────────────────────────────────────── */

interface NavChild {
  label: string;
  href: string;
  icon: React.ElementType;
}

interface NavItem {
  id: string;
  label: string;
  href: string;
  icon: React.ElementType;
  children?: NavChild[];
}

const navigation: NavItem[] = [
  {
    id: "dashboard",
    label: "Dashboard",
    href: "/dashboard",
    icon: LayoutDashboard,
  },
  {
    id: "hr",
    label: "HR Management",
    href: "/hr",
    icon: Users,
    children: [
      { label: "Overview", href: "/hr", icon: Users },
      { label: "Staff List", href: "/hr/staff-list", icon: UserCheck },
      { label: "Departments", href: "/hr/departments", icon: Building2 },
      { label: "Leave Requests", href: "/hr/leaves", icon: CalendarOff },
      { label: "Payroll", href: "/hr/payroll", icon: Wallet },
      { label: "Designations", href: "/hr/designations", icon: Award },
      { label: "Notes", href: "/hr/notes", icon: StickyNote },
      { label: "Settings", href: "/hr/settings", icon: Settings },
    ],
  },
  {
    id: "communication",
    label: "Communication",
    href: "/communication",
    icon: MessageSquare,
    children: [
      { label: "Overview", href: "/communication", icon: MessageSquare },
      { label: "Send Email", href: "/communication/send-email", icon: Mail },
      { label: "Send SMS", href: "/communication/send-sms", icon: Smartphone },
      { label: "Send Notice", href: "/communication/send-notice", icon: Megaphone },
    ],
  },
  {
    id: "procurement",
    label: "Procurement",
    href: "/procurement",
    icon: ShoppingCart,
    children: [
      { label: "Overview", href: "/procurement", icon: ShoppingCart },
      { label: "Purchase Orders", href: "/procurement/purchase-orders", icon: ClipboardList },
      { label: "Vendors", href: "/procurement/vendors", icon: Store },
      { label: "Inventory", href: "/procurement/inventory", icon: Package },
      { label: "Requisitions", href: "/procurement/requisitions", icon: FileInput },
    ],
  },
  {
    id: "programs",
    label: "Programs",
    href: "/programs",
    icon: Target,
    children: [
      { label: "Overview", href: "/programs", icon: Target },
      { label: "Projects", href: "/programs/projects", icon: FolderKanban },
      { label: "Beneficiaries", href: "/programs/beneficiaries", icon: Heart },
      { label: "Reports", href: "/programs/reports", icon: BarChart3 },
    ],
  },
  {
    id: "settings",
    label: "Settings",
    href: "/settings",
    icon: Settings,
  },
  {
    id: "help",
    label: "Help Center",
    href: "/help",
    icon: HelpCircle,
  },
];

/* ─── Sidebar Component ──────────────────────────────────────────── */

export function Sidebar() {
  const pathname = usePathname();
  const { user, logout } = useAuthStore();
  const { isCollapsed, toggleCollapsed } = useSidebarStore();
  const [openSubMenus, setOpenSubMenus] = React.useState<Record<string, boolean>>({});

  const toggleSubMenu = (id: string) => {
    setOpenSubMenus((prev) => ({ ...prev, [id]: !prev[id] }));
  };

  // Auto-open submenus based on current path
  React.useEffect(() => {
    navigation.forEach((item) => {
      if (item.children) {
        const isActive = item.children.some(
          (c) => pathname === c.href || pathname.startsWith(c.href + "/")
        );
        if (isActive) {
          setOpenSubMenus((prev) => ({ ...prev, [item.id]: true }));
        }
      }
    });
  }, [pathname]);

  return (
    <aside
      className={cn(
        "fixed left-0 top-0 z-40 h-screen border-r bg-card transition-[width] duration-300 will-change-[width] flex flex-col",
        isCollapsed ? "w-[68px]" : "w-[260px]"
      )}
    >
      {/* Logo */}
      <div className="flex h-16 items-center justify-between border-b px-4">
        {!isCollapsed && (
          <Link href="/dashboard" className="flex items-center gap-2">
            <div className="flex h-8 w-8 items-center justify-center rounded-lg bg-primary">
              <span className="text-sm font-bold text-primary-foreground">C</span>
            </div>
            <span className="text-lg font-bold">CASI360</span>
          </Link>
        )}
        {isCollapsed && (
          <Link href="/dashboard" className="mx-auto">
            <div className="flex h-8 w-8 items-center justify-center rounded-lg bg-primary">
              <span className="text-sm font-bold text-primary-foreground">C</span>
            </div>
          </Link>
        )}
      </div>

      {/* Navigation */}
      <ScrollArea className="flex-1 px-3 py-4">
        <nav className="flex flex-col gap-1">
          {navigation.map((item) => {
            const Icon = item.icon;
            const hasChildren = !!item.children;
            const isActive =
              pathname === item.href ||
              pathname.startsWith(item.href + "/") ||
              (hasChildren &&
                item.children!.some(
                  (c) => pathname === c.href || pathname.startsWith(c.href + "/")
                ));
            const isOpen = openSubMenus[item.id];

            if (hasChildren) {
              return (
                <div key={item.id}>
                  {isCollapsed ? (
                    <Tooltip delayDuration={0}>
                      <TooltipTrigger asChild>
                        <Link
                          href={item.href}
                          className={cn(
                            "flex h-10 w-full items-center justify-center rounded-lg text-muted-foreground transition-colors hover:bg-accent hover:text-accent-foreground",
                            isActive && "bg-accent text-accent-foreground font-medium"
                          )}
                        >
                          <Icon className="h-5 w-5" />
                        </Link>
                      </TooltipTrigger>
                      <TooltipContent side="right" className="font-medium">
                        {item.label}
                      </TooltipContent>
                    </Tooltip>
                  ) : (
                    <>
                      <button
                        onClick={() => toggleSubMenu(item.id)}
                        className={cn(
                          "flex h-10 w-full items-center gap-3 rounded-lg px-3 text-muted-foreground transition-colors hover:bg-accent hover:text-accent-foreground",
                          isActive && "bg-accent text-accent-foreground font-medium"
                        )}
                      >
                        <Icon className="h-5 w-5 shrink-0" />
                        <span className="flex-1 text-left text-sm">{item.label}</span>
                        <ChevronDown
                          className={cn(
                            "h-4 w-4 transition-transform",
                            isOpen && "rotate-180"
                          )}
                        />
                      </button>
                      {isOpen && (
                        <div className="ml-4 mt-1 flex flex-col gap-1 border-l pl-3">
                          {item.children!.map((child) => {
                            const SubIcon = child.icon;
                            const isSubActive = pathname === child.href;
                            return (
                              <Link
                                key={child.href}
                                href={child.href}
                                className={cn(
                                  "flex h-9 items-center gap-2 rounded-lg px-3 text-sm text-muted-foreground transition-colors hover:bg-accent hover:text-accent-foreground",
                                  isSubActive &&
                                    "bg-accent text-accent-foreground font-medium"
                                )}
                              >
                                <SubIcon className="h-4 w-4" />
                                <span>{child.label}</span>
                              </Link>
                            );
                          })}
                        </div>
                      )}
                    </>
                  )}
                </div>
              );
            }

            return isCollapsed ? (
              <Tooltip key={item.id} delayDuration={0}>
                <TooltipTrigger asChild>
                  <Link
                    href={item.href}
                    className={cn(
                      "flex h-10 w-full items-center justify-center rounded-lg text-muted-foreground transition-colors hover:bg-accent hover:text-accent-foreground",
                      isActive && "bg-accent text-accent-foreground font-medium"
                    )}
                  >
                    <Icon className="h-5 w-5" />
                  </Link>
                </TooltipTrigger>
                <TooltipContent side="right" className="font-medium">
                  {item.label}
                </TooltipContent>
              </Tooltip>
            ) : (
              <Link
                key={item.id}
                href={item.href}
                className={cn(
                  "flex h-10 items-center gap-3 rounded-lg px-3 text-muted-foreground transition-colors hover:bg-accent hover:text-accent-foreground",
                  isActive && "bg-accent text-accent-foreground font-medium"
                )}
              >
                <Icon className="h-5 w-5 shrink-0" />
                <span className="text-sm">{item.label}</span>
              </Link>
            );
          })}
        </nav>
      </ScrollArea>

      {/* Footer */}
      <div className="border-t p-3">
        {!isCollapsed && user && (
          <Link
            href="/profile"
            className="mb-3 flex items-center gap-3 rounded-lg bg-accent/50 p-2 transition-colors hover:bg-accent cursor-pointer"
          >
            <div className="flex h-8 w-8 items-center justify-center rounded-full bg-primary text-xs font-bold text-primary-foreground">
              {user.name
                .split(" ")
                .map((n) => n[0])
                .join("")}
            </div>
            <div className="flex-1 min-w-0">
              <p className="truncate text-sm font-medium">{user.name}</p>
              <p className="truncate text-xs text-muted-foreground capitalize">
                {user.role.replace("_", " ")}
              </p>
            </div>
          </Link>
        )}
        <div className="flex items-center gap-2">
          {isCollapsed ? (
            <Tooltip delayDuration={0}>
              <TooltipTrigger asChild>
                <Button
                  variant="ghost"
                  size="icon"
                  className="h-10 w-full"
                  onClick={logout}
                >
                  <LogOut className="h-5 w-5" />
                </Button>
              </TooltipTrigger>
              <TooltipContent side="right">Logout</TooltipContent>
            </Tooltip>
          ) : (
            <Button
              variant="ghost"
              className="h-10 w-full justify-start gap-3 text-muted-foreground"
              onClick={logout}
            >
              <LogOut className="h-5 w-5" />
              <span className="text-sm">Logout</span>
            </Button>
          )}
        </div>
      </div>

      {/* Collapse button */}
      <button
        onClick={toggleCollapsed}
        className="absolute -right-3 top-20 z-50 flex h-6 w-6 items-center justify-center rounded-full border bg-background shadow-md hover:bg-accent transition-colors"
      >
        {isCollapsed ? (
          <ChevronRight className="h-3 w-3" />
        ) : (
          <ChevronLeft className="h-3 w-3" />
        )}
      </button>
    </aside>
  );
}
