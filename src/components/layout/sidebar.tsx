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
  CheckCircle,
  MessageSquare,
  Inbox,
  PenSquare,
  Send,
  FileText,
  Settings,
  UserCircle,
  HelpCircle,
  ChevronLeft,
  ChevronRight,
  ChevronDown,
  LogOut,
  Shield,
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
import { useModuleStore } from "@/store/module-store";
import { getEnabledModules, getModulesForRole } from "@/lib/module-registry";
import { Button } from "@/components/ui/button";
import { ScrollArea } from "@/components/ui/scroll-area";
import {
  Tooltip,
  TooltipContent,
  TooltipTrigger,
} from "@/components/ui/tooltip";
import { Separator } from "@/components/ui/separator";

const iconMap: Record<string, React.ElementType> = {
  LayoutDashboard,
  Users,
  UserCheck,
  Building2,
  CalendarOff,
  CheckCircle,
  MessageSquare,
  Inbox,
  PenSquare,
  Wallet,
  Award,
  StickyNote,
  Send,
  FileText,
  Settings,
  UserCircle,
  Mail,
  Smartphone,
  Megaphone,
  HelpCircle,
  Shield,
  ShoppingCart,
  ClipboardList,
  Store,
  Package,
  FileInput,
  Target,
  FolderKanban,
  Heart,
  BarChart3,
};

export function Sidebar() {
  const pathname = usePathname();
  const { user, logout } = useAuthStore();
  const { isCollapsed, toggleCollapsed } = useSidebarStore();
  const { enabledModules } = useModuleStore();
  const [openSubMenus, setOpenSubMenus] = React.useState<Record<string, boolean>>({});

  const allModules = getEnabledModules(enabledModules);
  const modules = (user
    ? getModulesForRole(allModules, user.role)
    : allModules
  ).filter((m) => m.id !== "profile");

  const toggleSubMenu = (id: string) => {
    setOpenSubMenus((prev) => ({ ...prev, [id]: !prev[id] }));
  };

  // Auto-open submenus based on current path
  React.useEffect(() => {
    modules.forEach((mod) => {
      if (mod.routes.length > 1) {
        const isActive = mod.routes.some(
          (r) => pathname === r.path || pathname.startsWith(r.path + "/")
        );
        if (isActive) {
          setOpenSubMenus((prev) => ({ ...prev, [mod.id]: true }));
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
          {modules.map((mod) => {
            const Icon = iconMap[mod.icon] || LayoutDashboard;
            const hasChildren = mod.routes.length > 1;
            const mainRoute = mod.routes[0];
            const isActive =
              pathname === mainRoute.path ||
              pathname.startsWith(mainRoute.path + "/") ||
              (hasChildren &&
                mod.routes.some(
                  (r) =>
                    pathname === r.path || pathname.startsWith(r.path + "/")
                ));
            const isOpen = openSubMenus[mod.id];

            if (hasChildren) {
              return (
                <div key={mod.id}>
                  {isCollapsed ? (
                    <Tooltip delayDuration={0}>
                      <TooltipTrigger asChild>
                        <Link
                          href={mainRoute.path}
                          className={cn(
                            "flex h-10 w-full items-center justify-center rounded-lg text-muted-foreground transition-colors hover:bg-accent hover:text-accent-foreground",
                            isActive && "bg-accent text-accent-foreground font-medium"
                          )}
                        >
                          <Icon className="h-5 w-5" />
                        </Link>
                      </TooltipTrigger>
                      <TooltipContent side="right" className="font-medium">
                        {mod.name}
                      </TooltipContent>
                    </Tooltip>
                  ) : (
                    <>
                      <button
                        onClick={() => toggleSubMenu(mod.id)}
                        className={cn(
                          "flex h-10 w-full items-center gap-3 rounded-lg px-3 text-muted-foreground transition-colors hover:bg-accent hover:text-accent-foreground",
                          isActive && "bg-accent text-accent-foreground font-medium"
                        )}
                      >
                        <Icon className="h-5 w-5 shrink-0" />
                        <span className="flex-1 text-left text-sm">{mod.name}</span>
                        <ChevronDown
                          className={cn(
                            "h-4 w-4 transition-transform",
                            isOpen && "rotate-180"
                          )}
                        />
                      </button>
                      {isOpen && (
                        <div className="ml-4 mt-1 flex flex-col gap-1 border-l pl-3">
                          {mod.routes.map((route) => {
                            const SubIcon = route.icon
                              ? iconMap[route.icon]
                              : null;
                            const isSubActive = pathname === route.path;
                            return (
                              <Link
                                key={route.path}
                                href={route.path}
                                className={cn(
                                  "flex h-9 items-center gap-2 rounded-lg px-3 text-sm text-muted-foreground transition-colors hover:bg-accent hover:text-accent-foreground",
                                  isSubActive &&
                                    "bg-accent text-accent-foreground font-medium"
                                )}
                              >
                                {SubIcon && <SubIcon className="h-4 w-4" />}
                                <span>{route.label}</span>
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
              <Tooltip key={mod.id} delayDuration={0}>
                <TooltipTrigger asChild>
                  <Link
                    href={mainRoute.path}
                    className={cn(
                      "flex h-10 w-full items-center justify-center rounded-lg text-muted-foreground transition-colors hover:bg-accent hover:text-accent-foreground",
                      isActive && "bg-accent text-accent-foreground font-medium"
                    )}
                  >
                    <Icon className="h-5 w-5" />
                  </Link>
                </TooltipTrigger>
                <TooltipContent side="right" className="font-medium">
                  {mod.name}
                </TooltipContent>
              </Tooltip>
            ) : (
              <Link
                key={mod.id}
                href={mainRoute.path}
                className={cn(
                  "flex h-10 items-center gap-3 rounded-lg px-3 text-muted-foreground transition-colors hover:bg-accent hover:text-accent-foreground",
                  isActive && "bg-accent text-accent-foreground font-medium"
                )}
              >
                <Icon className="h-5 w-5 shrink-0" />
                <span className="text-sm">{mod.name}</span>
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
