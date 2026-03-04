"use client";

import * as React from "react";
import {
  Search,
  Plus,
  Clock,
  CheckCircle2,
  Pause,
  XCircle,
  PlayCircle,
  Users,
  Calendar,
} from "lucide-react";
import {
  Card,
  CardContent,
} from "@/components/ui/card";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select";
import { mockProjects } from "@/mock/programs";
import { format } from "date-fns";

export default function ProjectsPage() {
  const [search, setSearch] = React.useState("");
  const [statusFilter, setStatusFilter] = React.useState("all");

  const filtered = mockProjects.filter((p) => {
    const matchesSearch =
      p.name.toLowerCase().includes(search.toLowerCase()) ||
      p.programName.toLowerCase().includes(search.toLowerCase()) ||
      p.manager.toLowerCase().includes(search.toLowerCase());
    const matchesStatus = statusFilter === "all" || p.status === statusFilter;
    return matchesSearch && matchesStatus;
  });

  const statusColor: Record<string, string> = {
    not_started: "bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300",
    in_progress: "bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400",
    completed: "bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400",
    on_hold: "bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400",
    cancelled: "bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400",
  };

  const statusIcon: Record<string, React.ElementType> = {
    not_started: Clock,
    in_progress: PlayCircle,
    completed: CheckCircle2,
    on_hold: Pause,
    cancelled: XCircle,
  };

  const stats = [
    { label: "Total Projects", value: mockProjects.length, color: "text-foreground" },
    {
      label: "In Progress",
      value: mockProjects.filter((p) => p.status === "in_progress").length,
      color: "text-blue-600",
    },
    {
      label: "Completed",
      value: mockProjects.filter((p) => p.status === "completed").length,
      color: "text-emerald-600",
    },
    {
      label: "Not Started",
      value: mockProjects.filter((p) => p.status === "not_started").length,
      color: "text-gray-600",
    },
  ];

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-2xl font-bold tracking-tight">Projects</h1>
          <p className="text-sm text-muted-foreground">
            Track project progress, milestones, and team assignments
          </p>
        </div>
        <Button>
          <Plus className="mr-2 h-4 w-4" />
          New Project
        </Button>
      </div>

      {/* Stats */}
      <div className="grid grid-cols-2 gap-4 sm:grid-cols-4">
        {stats.map((s) => (
          <Card key={s.label}>
            <CardContent className="p-4 text-center">
              <p className={`text-2xl font-bold ${s.color}`}>{s.value}</p>
              <p className="text-xs text-muted-foreground">{s.label}</p>
            </CardContent>
          </Card>
        ))}
      </div>

      {/* Filters */}
      <div className="flex flex-col gap-4 sm:flex-row sm:items-center">
        <div className="relative flex-1">
          <Search className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
          <Input
            placeholder="Search projects..."
            className="pl-9"
            value={search}
            onChange={(e) => setSearch(e.target.value)}
          />
        </div>
        <Select value={statusFilter} onValueChange={setStatusFilter}>
          <SelectTrigger className="w-[180px]">
            <SelectValue placeholder="Filter by status" />
          </SelectTrigger>
          <SelectContent>
            <SelectItem value="all">All Statuses</SelectItem>
            <SelectItem value="not_started">Not Started</SelectItem>
            <SelectItem value="in_progress">In Progress</SelectItem>
            <SelectItem value="completed">Completed</SelectItem>
            <SelectItem value="on_hold">On Hold</SelectItem>
            <SelectItem value="cancelled">Cancelled</SelectItem>
          </SelectContent>
        </Select>
      </div>

      {/* Project Cards */}
      <div className="space-y-4">
        {filtered.length === 0 && (
          <Card>
            <CardContent className="p-8 text-center text-muted-foreground">
              No projects found.
            </CardContent>
          </Card>
        )}
        {filtered.map((project) => {
          const StatusIcon = statusIcon[project.status] || Clock;
          const completedMilestones = project.milestones.filter(
            (m) => m.completed
          ).length;
          const progressColor =
            project.progress === 100
              ? "bg-emerald-500"
              : project.progress >= 50
                ? "bg-blue-500"
                : "bg-amber-500";

          return (
            <Card
              key={project.id}
              className="transition-all hover:shadow-md cursor-pointer"
            >
              <CardContent className="p-6">
                <div className="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                  <div className="flex-1 min-w-0">
                    <div className="flex items-center gap-2 flex-wrap">
                      <h3 className="font-semibold">{project.name}</h3>
                      <Badge
                        variant="secondary"
                        className={`text-[10px] ${statusColor[project.status]}`}
                      >
                        <StatusIcon className="mr-1 h-3 w-3" />
                        {project.status.replace("_", " ")}
                      </Badge>
                    </div>
                    <p className="mt-1 text-xs text-muted-foreground">
                      Program: <strong>{project.programName}</strong>
                    </p>
                    <p className="mt-1 text-sm text-muted-foreground line-clamp-2">
                      {project.description}
                    </p>
                    <div className="mt-3 flex flex-wrap gap-4 text-xs text-muted-foreground">
                      <span className="flex items-center gap-1">
                        <Users className="h-3 w-3" />
                        {project.team.length} team members
                      </span>
                      <span className="flex items-center gap-1">
                        <Calendar className="h-3 w-3" />
                        {format(new Date(project.startDate), "MMM yyyy")} -{" "}
                        {format(new Date(project.endDate), "MMM yyyy")}
                      </span>
                      <span>
                        <strong>Manager:</strong> {project.manager}
                      </span>
                    </div>
                  </div>
                  <div className="text-right shrink-0 space-y-1">
                    <p className="text-sm font-semibold">
                      ₦{(project.budget / 1000000).toFixed(0)}M
                    </p>
                    <p className="text-xs text-muted-foreground">
                      ₦{(project.spent / 1000000).toFixed(1)}M spent
                    </p>
                  </div>
                </div>

                {/* Progress Bar */}
                <div className="mt-4">
                  <div className="flex items-center justify-between text-xs mb-1">
                    <span className="text-muted-foreground">Progress</span>
                    <span className="font-medium">{project.progress}%</span>
                  </div>
                  <div className="h-2 w-full rounded-full bg-muted">
                    <div
                      className={`h-2 rounded-full transition-all ${progressColor}`}
                      style={{ width: `${project.progress}%` }}
                    />
                  </div>
                </div>

                {/* Milestones */}
                <div className="mt-4">
                  <p className="text-xs font-medium mb-2">
                    Milestones ({completedMilestones}/{project.milestones.length})
                  </p>
                  <div className="flex flex-wrap gap-2">
                    {project.milestones.map((m) => (
                      <Badge
                        key={m.id}
                        variant="outline"
                        className={`text-[10px] ${
                          m.completed
                            ? "bg-emerald-50 border-emerald-200 text-emerald-700 dark:bg-emerald-900/20 dark:border-emerald-800 dark:text-emerald-400"
                            : "bg-gray-50 border-gray-200 text-gray-600 dark:bg-gray-900/20 dark:border-gray-700 dark:text-gray-400"
                        }`}
                      >
                        {m.completed && (
                          <CheckCircle2 className="mr-1 h-3 w-3" />
                        )}
                        {m.title}
                      </Badge>
                    ))}
                  </div>
                </div>

                {/* Team */}
                <div className="mt-4 flex items-center gap-1">
                  {project.team.slice(0, 4).map((member, i) => (
                    <div
                      key={i}
                      className="flex h-7 w-7 items-center justify-center rounded-full bg-primary text-[10px] font-bold text-primary-foreground ring-2 ring-background -ml-1 first:ml-0"
                      title={member}
                    >
                      {member
                        .split(" ")
                        .map((n) => n[0])
                        .join("")}
                    </div>
                  ))}
                  {project.team.length > 4 && (
                    <div className="flex h-7 w-7 items-center justify-center rounded-full bg-muted text-[10px] font-medium -ml-1">
                      +{project.team.length - 4}
                    </div>
                  )}
                </div>
              </CardContent>
            </Card>
          );
        })}
      </div>
    </div>
  );
}
