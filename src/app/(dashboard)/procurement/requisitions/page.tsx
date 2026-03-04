"use client";

import * as React from "react";
import {
  FileInput,
  Search,
  Plus,
  Clock,
  CheckCircle2,
  XCircle,
  Send,
  Archive,
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
import { Input } from "@/components/ui/input";
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select";
import { mockRequisitions } from "@/mock/requisitions";
import { format } from "date-fns";

export default function RequisitionsPage() {
  const [search, setSearch] = React.useState("");
  const [statusFilter, setStatusFilter] = React.useState("all");

  const filtered = mockRequisitions.filter((r) => {
    const matchesSearch =
      r.title.toLowerCase().includes(search.toLowerCase()) ||
      r.department.toLowerCase().includes(search.toLowerCase()) ||
      r.requestedBy.toLowerCase().includes(search.toLowerCase());
    const matchesStatus = statusFilter === "all" || r.status === statusFilter;
    return matchesSearch && matchesStatus;
  });

  const statusColor: Record<string, string> = {
    draft: "bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300",
    submitted: "bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400",
    approved: "bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400",
    rejected: "bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400",
    fulfilled: "bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400",
  };

  const priorityColor: Record<string, string> = {
    low: "bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400",
    medium: "bg-blue-100 text-blue-600 dark:bg-blue-900/30 dark:text-blue-400",
    high: "bg-orange-100 text-orange-600 dark:bg-orange-900/30 dark:text-orange-400",
    urgent: "bg-red-100 text-red-600 dark:bg-red-900/30 dark:text-red-400",
  };

  const statusIcon: Record<string, React.ElementType> = {
    draft: Archive,
    submitted: Send,
    approved: CheckCircle2,
    rejected: XCircle,
    fulfilled: CheckCircle2,
  };

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-2xl font-bold tracking-tight">Requisitions</h1>
          <p className="text-sm text-muted-foreground">
            Submit and track procurement requisitions
          </p>
        </div>
        <Button>
          <Plus className="mr-2 h-4 w-4" />
          New Requisition
        </Button>
      </div>

      {/* Stats */}
      <div className="grid grid-cols-2 gap-4 sm:grid-cols-5">
        {[
          { label: "Total", value: mockRequisitions.length, color: "text-foreground" },
          { label: "Draft", value: mockRequisitions.filter((r) => r.status === "draft").length, color: "text-gray-600" },
          { label: "Submitted", value: mockRequisitions.filter((r) => r.status === "submitted").length, color: "text-amber-600" },
          { label: "Approved", value: mockRequisitions.filter((r) => r.status === "approved").length, color: "text-emerald-600" },
          { label: "Rejected", value: mockRequisitions.filter((r) => r.status === "rejected").length, color: "text-red-600" },
        ].map((s) => (
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
            placeholder="Search requisitions..."
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
            <SelectItem value="draft">Draft</SelectItem>
            <SelectItem value="submitted">Submitted</SelectItem>
            <SelectItem value="approved">Approved</SelectItem>
            <SelectItem value="rejected">Rejected</SelectItem>
            <SelectItem value="fulfilled">Fulfilled</SelectItem>
          </SelectContent>
        </Select>
      </div>

      {/* Requisition Cards */}
      <div className="space-y-4">
        {filtered.length === 0 && (
          <Card>
            <CardContent className="p-8 text-center text-muted-foreground">
              No requisitions found.
            </CardContent>
          </Card>
        )}
        {filtered.map((req) => {
          const StatusIcon = statusIcon[req.status] || FileInput;
          return (
            <Card
              key={req.id}
              className="transition-all hover:shadow-md cursor-pointer"
            >
              <CardContent className="p-6">
                <div className="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                  <div className="flex-1">
                    <div className="flex items-center gap-2 flex-wrap">
                      <h3 className="font-semibold">{req.title}</h3>
                      <Badge
                        variant="secondary"
                        className={`text-[10px] ${statusColor[req.status]}`}
                      >
                        <StatusIcon className="mr-1 h-3 w-3" />
                        {req.status}
                      </Badge>
                      <Badge
                        variant="secondary"
                        className={`text-[10px] ${priorityColor[req.priority]}`}
                      >
                        {req.priority}
                      </Badge>
                    </div>
                    <p className="mt-1 text-sm text-muted-foreground">
                      {req.justification}
                    </p>
                    <div className="mt-3 flex flex-wrap gap-4 text-xs text-muted-foreground">
                      <span>
                        <strong>Dept:</strong> {req.department}
                      </span>
                      <span>
                        <strong>By:</strong> {req.requestedBy}
                      </span>
                      <span>
                        <strong>Date:</strong>{" "}
                        {format(new Date(req.createdAt), "MMM d, yyyy")}
                      </span>
                      <span>
                        <strong>Items:</strong> {req.items.length}
                      </span>
                    </div>
                  </div>
                  <div className="text-right shrink-0">
                    <p className="text-lg font-bold">
                      ₦{req.totalEstimate.toLocaleString()}
                    </p>
                    <p className="text-xs text-muted-foreground">Estimated Total</p>
                  </div>
                </div>

                {/* Items preview */}
                <div className="mt-4 rounded-lg bg-muted/50 p-3">
                  <div className="grid gap-2">
                    {req.items.map((item, idx) => (
                      <div
                        key={idx}
                        className="flex items-center justify-between text-sm"
                      >
                        <span className="text-muted-foreground">
                          {item.name} × {item.quantity}
                        </span>
                        <span className="font-medium">
                          ₦{item.estimatedCost.toLocaleString()}
                        </span>
                      </div>
                    ))}
                  </div>
                </div>
              </CardContent>
            </Card>
          );
        })}
      </div>
    </div>
  );
}
