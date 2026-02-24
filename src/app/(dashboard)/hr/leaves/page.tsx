"use client";

import * as React from "react";
import { CalendarOff, Search } from "lucide-react";
import { Card, CardContent } from "@/components/ui/card";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/components/ui/table";
import { mockLeaveRequests } from "@/mock/leaves";
import { toast } from "sonner";

export default function LeavesPage() {
  const [search, setSearch] = React.useState("");
  const [statusFilter, setStatusFilter] = React.useState("all");
  const [leaves, setLeaves] = React.useState(mockLeaveRequests);

  const filtered = leaves.filter((l) => {
    const matchSearch = l.employeeName.toLowerCase().includes(search.toLowerCase());
    const matchStatus = statusFilter === "all" || l.status === statusFilter;
    return matchSearch && matchStatus;
  });

  const handleAction = (id: string, action: "approved" | "rejected") => {
    setLeaves((prev) => prev.map((l) => (l.id === id ? { ...l, status: action } : l)));
    toast.success(`Leave request ${action}`, { description: `The leave request has been ${action}.` });
  };

  const getStatusColor = (status: string) => {
    switch (status) {
      case "approved": return "default";
      case "rejected": return "destructive";
      default: return "secondary";
    }
  };

  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-2xl font-bold tracking-tight">Leave Requests</h1>
        <p className="text-sm text-muted-foreground">Manage employee leave requests</p>
      </div>

      <Card>
        <CardContent className="p-4">
          <div className="flex flex-col gap-4 sm:flex-row sm:items-center">
            <div className="relative flex-1">
              <Search className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
              <Input placeholder="Search by employee name..." value={search} onChange={(e) => setSearch(e.target.value)} className="pl-9" />
            </div>
            <Select value={statusFilter} onValueChange={setStatusFilter}>
              <SelectTrigger className="w-[140px]"><SelectValue placeholder="Status" /></SelectTrigger>
              <SelectContent>
                <SelectItem value="all">All</SelectItem>
                <SelectItem value="pending">Pending</SelectItem>
                <SelectItem value="approved">Approved</SelectItem>
                <SelectItem value="rejected">Rejected</SelectItem>
              </SelectContent>
            </Select>
          </div>
        </CardContent>
      </Card>

      <Card>
        <CardContent className="p-0">
          <Table>
            <TableHeader>
              <TableRow>
                <TableHead>Employee</TableHead>
                <TableHead className="hidden sm:table-cell">Type</TableHead>
                <TableHead className="hidden md:table-cell">Start Date</TableHead>
                <TableHead className="hidden md:table-cell">End Date</TableHead>
                <TableHead>Status</TableHead>
                <TableHead className="text-right">Actions</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              {filtered.length === 0 ? (
                <TableRow><TableCell colSpan={6} className="h-32 text-center text-muted-foreground">No leave requests found</TableCell></TableRow>
              ) : (
                filtered.map((leave) => (
                  <TableRow key={leave.id}>
                    <TableCell>
                      <div>
                        <p className="font-medium text-sm">{leave.employeeName}</p>
                        <p className="text-xs text-muted-foreground">{leave.reason}</p>
                      </div>
                    </TableCell>
                    <TableCell className="hidden sm:table-cell"><Badge variant="outline" className="capitalize text-xs">{leave.type}</Badge></TableCell>
                    <TableCell className="hidden md:table-cell text-sm">{leave.startDate}</TableCell>
                    <TableCell className="hidden md:table-cell text-sm">{leave.endDate}</TableCell>
                    <TableCell><Badge variant={getStatusColor(leave.status) as any} className="capitalize text-xs">{leave.status}</Badge></TableCell>
                    <TableCell className="text-right">
                      {leave.status === "pending" && (
                        <div className="flex justify-end gap-2">
                          <Button size="sm" variant="outline" className="h-7 text-xs" onClick={() => handleAction(leave.id, "approved")}>Approve</Button>
                          <Button size="sm" variant="outline" className="h-7 text-xs text-destructive" onClick={() => handleAction(leave.id, "rejected")}>Reject</Button>
                        </div>
                      )}
                    </TableCell>
                  </TableRow>
                ))
              )}
            </TableBody>
          </Table>
        </CardContent>
      </Card>
    </div>
  );
}
