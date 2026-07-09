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

  // Simple client-mount flag. Once useEffect fires we know:
  // 1) We are on the client (not static HTML)
  // 2) Zustand has already rehydrated from localStorage
  const [mounted, setMounted] = React.useState(false);
  React.useEffect(() => {
    setMounted(true);
  }, []);

  React.useEffect(() => {
    setMobileOpen(false);
  }, [pathname]);

  // Check session once after mount — runs in background.
  React.useEffect(() => {
    if (mounted && !sessionChecked.current) {
      sessionChecked.current = true;
      checkSession();
    }
  }, [mounted, checkSession]);

  React.useEffect(() => {
    if (mounted && !isLoading && !isAuthenticated && pathname !== "/login") {
      router.push("/login/");
    }
  }, [mounted, isAuthenticated, isLoading, pathname, router]);

  // Before client mount, show a brief loading state.
  // This prevents the default isAuthenticated=false from triggering
  // a redirect to /login before localStorage has been read.
  if (!mounted) {
    return (
      <div className="flex min-h-screen items-center justify-center bg-background">
        <div className="flex flex-col items-center gap-3">
          <div className="h-10 w-10 animate-spin rounded-full border-4 border-primary border-t-transparent" />
          <p className="text-sm text-muted-foreground">Loading...</p>
        </div>
      </div>
    );
  }

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
