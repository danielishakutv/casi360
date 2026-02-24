"use client";

import * as React from "react";
import Link from "next/link";
import { Users, Building2, CalendarOff, Wallet, Award, StickyNote, Settings, TrendingUp, UserPlus } from "lucide-react";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { mockEmployees } from "@/mock/employees";
import { mockDepartments } from "@/mock/departments";
import { mockLeaveRequests } from "@/mock/leaves";
import { mockPayroll } from "@/mock/payroll";
import { mockDesignations } from "@/mock/designations";
import { mockNotes } from "@/mock/notes";

export default function HRPage() {
  const activeEmployees = mockEmployees.filter((e) => e.status === "active").length;
  const onLeave = mockEmployees.filter((e) => e.status === "on_leave").length;
  const pendingLeaves = mockLeaveRequests.filter((l) => l.status === "pending").length;
  const pendingPayroll = mockPayroll.filter((p) => p.status === "pending").length;
  const pinnedNotes = mockNotes.filter((n) => n.isPinned).length;

  const cards = [
    { title: "Staff List", value: mockEmployees.length, subtitle: `${activeEmployees} active, ${onLeave} on leave`, icon: Users, color: "text-blue-600 bg-blue-100 dark:bg-blue-900/30 dark:text-blue-400", href: "/hr/staff-list" },
    { title: "Departments", value: mockDepartments.length, subtitle: `${mockDepartments.filter(d => d.employeeCount > 0).length} active departments`, icon: Building2, color: "text-purple-600 bg-purple-100 dark:bg-purple-900/30 dark:text-purple-400", href: "/hr/departments" },
    { title: "Leave Requests", value: pendingLeaves, subtitle: `${onLeave} currently on leave`, icon: CalendarOff, color: "text-amber-600 bg-amber-100 dark:bg-amber-900/30 dark:text-amber-400", href: "/hr/leaves" },
    { title: "Payroll", value: `${pendingPayroll} pending`, subtitle: `${mockPayroll.filter(p => p.status === "paid").length} paid this period`, icon: Wallet, color: "text-emerald-600 bg-emerald-100 dark:bg-emerald-900/30 dark:text-emerald-400", href: "/hr/payroll" },
    { title: "Designations", value: mockDesignations.length, subtitle: `${mockDesignations.reduce((s, d) => s + d.employeeCount, 0)} positions filled`, icon: Award, color: "text-rose-600 bg-rose-100 dark:bg-rose-900/30 dark:text-rose-400", href: "/hr/designations" },
    { title: "Notes", value: mockNotes.length, subtitle: `${pinnedNotes} pinned`, icon: StickyNote, color: "text-cyan-600 bg-cyan-100 dark:bg-cyan-900/30 dark:text-cyan-400", href: "/hr/notes" },
  ];

  const recentHires = mockEmployees
    .sort((a, b) => new Date(b.joinDate).getTime() - new Date(a.joinDate).getTime())
    .slice(0, 5);

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-2xl font-bold tracking-tight">HR Management</h1>
          <p className="text-sm text-muted-foreground">Manage employees, departments, and HR operations</p>
        </div>
        <Button asChild>
          <Link href="/hr/staff-list"><UserPlus className="mr-2 h-4 w-4" />Add Staff</Link>
        </Button>
      </div>

      <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        {cards.map((card) => (
          <Link key={card.title} href={card.href}>
            <Card className="transition-shadow hover:shadow-md cursor-pointer">
              <CardContent className="p-6">
                <div className="flex items-center justify-between">
                  <div className={`flex h-10 w-10 items-center justify-center rounded-xl ${card.color}`}>
                    <card.icon className="h-5 w-5" />
                  </div>
                </div>
                <div className="mt-4">
                  <p className="text-2xl font-bold">{card.value}</p>
                  <p className="text-xs text-muted-foreground">{card.title}</p>
                  <p className="mt-1 text-xs text-muted-foreground">{card.subtitle}</p>
                </div>
              </CardContent>
            </Card>
          </Link>
        ))}
      </div>

      <Card>
        <CardHeader>
          <CardTitle className="text-base">Recent Hires</CardTitle>
          <CardDescription>Latest employees to join the organization</CardDescription>
        </CardHeader>
        <CardContent>
          <div className="space-y-4">
            {recentHires.map((emp) => (
              <div key={emp.id} className="flex items-center justify-between">
                <div className="flex items-center gap-3">
                  <div className="flex h-9 w-9 items-center justify-center rounded-full bg-primary text-xs font-bold text-primary-foreground">
                    {emp.name.split(" ").map((n) => n[0]).join("")}
                  </div>
                  <div>
                    <p className="text-sm font-medium">{emp.name}</p>
                    <p className="text-xs text-muted-foreground">{emp.position} • {emp.department}</p>
                  </div>
                </div>
                <div className="flex items-center gap-2">
                  <Badge variant={emp.status === "active" ? "default" : emp.status === "on_leave" ? "secondary" : "destructive"} className="capitalize text-xs">
                    {emp.status.replace("_", " ")}
                  </Badge>
                  <span className="text-xs text-muted-foreground">{emp.joinDate}</span>
                </div>
              </div>
            ))}
          </div>
        </CardContent>
      </Card>
    </div>
  );
}
