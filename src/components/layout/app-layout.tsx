"use client";

import * as React from "react";
import { usePathname, useRouter } from "next/navigation";
import { useSidebarStore } from "@/store/sidebar-store";
import { useAuthStore } from "@/store/auth-store";
import { Sidebar } from "./sidebar";
import { Header } from "./header";
import { Sheet, SheetContent } from "@/components/ui/sheet";
import { cn } from "@/lib/utils";

export function AppLayout({ children }: { children: React.ReactNode }) {
  const { isCollapsed, isMobileOpen, setMobileOpen } = useSidebarStore();
  const { isAuthenticated, isLoading, checkSession } = useAuthStore();
  const pathname = usePathname();
  const router = useRouter();
  const sessionChecked = React.useRef(false);

  React.useEffect(() => {
    setMobileOpen(false);
  }, [pathname]);

  // Check session once on mount — fire-and-forget so it never blocks rendering.
  // The persisted zustand state keeps the user logged in while the check runs
  // in the background; if the server says "not authenticated" zustand will update
  // and the redirect effect below will kick in.
  React.useEffect(() => {
    if (!sessionChecked.current) {
      sessionChecked.current = true;
      // intentionally not awaited — runs in background
      checkSession();
    }
  }, [checkSession]);

  React.useEffect(() => {
    if (!isLoading && !isAuthenticated && pathname !== "/login") {
      router.push("/login/");
    }
  }, [isAuthenticated, isLoading, pathname, router]);

  // Trust the persisted auth state — don't blank the screen while checkSession
  // is in-flight; only redirect if we're definitely unauthenticated.
  if (!isAuthenticated) {
    return null;
  }

  return (
    <div className="min-h-screen bg-background">
      {/* Desktop Sidebar */}
      <div className="hidden lg:block">
        <Sidebar />
      </div>

      {/* Mobile Sidebar */}
      <Sheet open={isMobileOpen} onOpenChange={setMobileOpen}>
        <SheetContent side="left" className="w-[260px] p-0">
          <Sidebar />
        </SheetContent>
      </Sheet>

      {/* Main Content */}
      <div
        className={cn(
          "transition-[padding-left] duration-300 will-change-[padding-left]",
          isCollapsed ? "lg:pl-[68px]" : "lg:pl-[260px]"
        )}
      >
        <Header />
        <main className="p-4 lg:p-6">{children}</main>
      </div>
    </div>
  );
}
