"use client";

import * as React from "react";
import Link from "next/link";
import {
  Target,
  FolderKanban,
  Heart,
  BarChart3,
  ArrowUpRight,
  TrendingUp,
  DollarSign,
  Users,
  MapPin,
} from "lucide-react";
import {
  Card,
  CardContent,
  CardDescription,
  CardHeader,
  CardTitle,
} from "@/components/ui/card";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import {
  mockPrograms,
  mockProjects,
  mockBeneficiaries,
  mockProgramReports,
} from "@/mock/programs";

export default function ProgramsPage() {
  const activePrograms = mockPrograms.filter((p) => p.status === "active").length;
  const totalBudget = mockPrograms.reduce((s, p) => s + p.budget, 0);
  const totalSpent = mockPrograms.reduce((s, p) => s + p.spent, 0);
  const totalBeneficiaries = mockPrograms.reduce(
    (s, p) => s + p.beneficiaryCount,
    0
  );

  const cards = [
    {
      title: "Active Programs",
      value: activePrograms,
      subtitle: `${mockPrograms.length} total programs`,
      icon: Target,
      color:
        "text-emerald-600 bg-emerald-100 dark:bg-emerald-900/30 dark:text-emerald-400",
      href: "/programs",
    },
    {
      title: "Projects",
      value: mockProjects.length,
      subtitle: `${mockProjects.filter((p) => p.status === "in_progress").length} in progress`,
      icon: FolderKanban,
      color:
        "text-blue-600 bg-blue-100 dark:bg-blue-900/30 dark:text-blue-400",
      href: "/programs/projects",
    },
    {
      title: "Beneficiaries",
      value: totalBeneficiaries.toLocaleString(),
      subtitle: `${mockBeneficiaries.filter((b) => b.status === "active").length} actively registered`,
      icon: Heart,
      color:
        "text-rose-600 bg-rose-100 dark:bg-rose-900/30 dark:text-rose-400",
      href: "/programs/beneficiaries",
    },
    {
      title: "Reports",
      value: mockProgramReports.length,
      subtitle: `${mockProgramReports.filter((r) => r.status === "draft").length} drafts pending`,
      icon: BarChart3,
      color:
        "text-violet-600 bg-violet-100 dark:bg-violet-900/30 dark:text-violet-400",
      href: "/programs/reports",
    },
  ];

  const statusColor: Record<string, string> = {
    planning: "bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300",
    active:
      "bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400",
    completed:
      "bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400",
    suspended:
      "bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400",
    closed: "bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400",
  };

  const categoryColor: Record<string, string> = {
    education:
      "bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400",
    health:
      "bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400",
    livelihood:
      "bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400",
    advocacy:
      "bg-violet-100 text-violet-700 dark:bg-violet-900/30 dark:text-violet-400",
    emergency:
      "bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400",
    capacity_building:
      "bg-cyan-100 text-cyan-700 dark:bg-cyan-900/30 dark:text-cyan-400",
  };

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-2xl font-bold tracking-tight">Programs</h1>
          <p className="text-sm text-muted-foreground">
            Manage programs, projects, beneficiaries, and impact reporting
          </p>
        </div>
        <Button>
          <Target className="mr-2 h-4 w-4" />
          New Program
        </Button>
      </div>

      {/* Budget Overview */}
      <Card className="border-dashed">
        <CardContent className="flex flex-col gap-4 p-4 sm:flex-row sm:items-center">
          <div className="flex items-center gap-4">
            <div className="flex h-12 w-12 items-center justify-center rounded-xl bg-blue-100 text-blue-600 dark:bg-blue-900/30 dark:text-blue-400">
              <DollarSign className="h-6 w-6" />
            </div>
            <div>
              <p className="text-sm text-muted-foreground">Total Budget</p>
              <p className="text-2xl font-bold">
                ₦{(totalBudget / 1000000).toFixed(0)}M
              </p>
            </div>
          </div>
          <div className="h-10 w-px bg-border hidden sm:block" />
          <div>
            <p className="text-sm text-muted-foreground">Total Spent</p>
            <p className="text-xl font-bold text-amber-600">
              ₦{(totalSpent / 1000000).toFixed(0)}M
            </p>
          </div>
          <div className="h-10 w-px bg-border hidden sm:block" />
          <div>
            <p className="text-sm text-muted-foreground">Utilization</p>
            <p className="text-xl font-bold">
              {((totalSpent / totalBudget) * 100).toFixed(0)}%
            </p>
          </div>
          <div className="sm:ml-auto flex items-center gap-1 text-sm text-emerald-600">
            <TrendingUp className="h-4 w-4" />
            <span>{totalBeneficiaries.toLocaleString()} lives impacted</span>
          </div>
        </CardContent>
      </Card>

      {/* Module Cards */}
      <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        {cards.map((card) => (
          <Link key={card.title} href={card.href}>
            <Card className="transition-all hover:shadow-md hover:-translate-y-0.5 cursor-pointer h-full">
              <CardContent className="p-6">
                <div className="flex items-center justify-between">
                  <div
                    className={`flex h-10 w-10 items-center justify-center rounded-xl ${card.color}`}
                  >
                    <card.icon className="h-5 w-5" />
                  </div>
                  <ArrowUpRight className="h-4 w-4 text-muted-foreground" />
                </div>
                <div className="mt-4">
                  <p className="text-2xl font-bold">{card.value}</p>
                  <p className="text-xs font-medium">{card.title}</p>
                  <p className="mt-1 text-xs text-muted-foreground">
                    {card.subtitle}
                  </p>
                </div>
              </CardContent>
            </Card>
          </Link>
        ))}
      </div>

      {/* Program List */}
      <Card>
        <CardHeader className="flex flex-row items-center justify-between">
          <div>
            <CardTitle className="text-base">All Programs</CardTitle>
            <CardDescription>
              Overview of all organizational programs
            </CardDescription>
          </div>
        </CardHeader>
        <CardContent>
          <div className="space-y-4">
            {mockPrograms.map((program) => {
              const progressColor =
                program.progress === 100
                  ? "bg-emerald-500"
                  : program.progress >= 50
                    ? "bg-blue-500"
                    : "bg-amber-500";

              return (
                <div
                  key={program.id}
                  className="rounded-xl border p-4 transition-all hover:shadow-md hover:bg-accent/30"
                >
                  <div className="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                    <div className="flex-1 min-w-0">
                      <div className="flex items-center gap-2 flex-wrap">
                        <h3 className="font-semibold">{program.name}</h3>
                        <Badge
                          variant="secondary"
                          className={`text-[10px] ${statusColor[program.status]}`}
                        >
                          {program.status}
                        </Badge>
                        <Badge
                          variant="secondary"
                          className={`text-[10px] ${categoryColor[program.category]}`}
                        >
                          {program.category.replace("_", " ")}
                        </Badge>
                      </div>
                      <p className="mt-1 text-sm text-muted-foreground line-clamp-2">
                        {program.description}
                      </p>
                      <div className="mt-3 flex flex-wrap gap-4 text-xs text-muted-foreground">
                        <span className="flex items-center gap-1">
                          <Users className="h-3 w-3" />
                          {program.beneficiaryCount.toLocaleString()} beneficiaries
                        </span>
                        <span className="flex items-center gap-1">
                          <MapPin className="h-3 w-3" />
                          {program.location}
                        </span>
                        <span>
                          <strong>Manager:</strong> {program.manager}
                        </span>
                      </div>
                    </div>
                    <div className="text-right shrink-0 space-y-1">
                      <p className="text-sm font-semibold">
                        ₦{(program.budget / 1000000).toFixed(0)}M budget
                      </p>
                      <p className="text-xs text-muted-foreground">
                        ₦{(program.spent / 1000000).toFixed(1)}M spent
                      </p>
                    </div>
                  </div>

                  {/* Progress Bar */}
                  <div className="mt-4">
                    <div className="flex items-center justify-between text-xs mb-1">
                      <span className="text-muted-foreground">Progress</span>
                      <span className="font-medium">{program.progress}%</span>
                    </div>
                    <div className="h-2 w-full rounded-full bg-muted">
                      <div
                        className={`h-2 rounded-full transition-all ${progressColor}`}
                        style={{ width: `${program.progress}%` }}
                      />
                    </div>
                  </div>
                </div>
              );
            })}
          </div>
        </CardContent>
      </Card>
    </div>
  );
}
