"use client";

import * as React from "react";
import { CheckCircle, XCircle, Clock, AlertTriangle } from "lucide-react";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { mockApprovals } from "@/mock/approvals";
import { toast } from "sonner";

export default function ApprovalsPage() {
  const [approvals, setApprovals] = React.useState(mockApprovals);

  const handleAction = (id: string, action: "approved" | "rejected") => {
    setApprovals((prev) => prev.map((a) => (a.id === id ? { ...a, status: action } : a)));
    toast.success(`Request ${action}`, { description: `The approval request has been ${action}.` });
  };

  const getPriorityColor = (priority: string) => {
    switch (priority) {
      case "high": return "destructive";
      case "medium": return "secondary";
      default: return "outline";
    }
  };

  const getStatusIcon = (status: string) => {
    switch (status) {
      case "approved": return <CheckCircle className="h-5 w-5 text-green-500" />;
      case "rejected": return <XCircle className="h-5 w-5 text-red-500" />;
      default: return <Clock className="h-5 w-5 text-amber-500" />;
    }
  };

  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-2xl font-bold tracking-tight">Approvals</h1>
        <p className="text-sm text-muted-foreground">Review and manage approval requests</p>
      </div>

      <div className="grid gap-4 sm:grid-cols-3">
        <Card><CardContent className="p-4 text-center"><p className="text-2xl font-bold text-amber-500">{approvals.filter((a) => a.status === "pending").length}</p><p className="text-xs text-muted-foreground">Pending</p></CardContent></Card>
        <Card><CardContent className="p-4 text-center"><p className="text-2xl font-bold text-green-500">{approvals.filter((a) => a.status === "approved").length}</p><p className="text-xs text-muted-foreground">Approved</p></CardContent></Card>
        <Card><CardContent className="p-4 text-center"><p className="text-2xl font-bold text-red-500">{approvals.filter((a) => a.status === "rejected").length}</p><p className="text-xs text-muted-foreground">Rejected</p></CardContent></Card>
      </div>

      <div className="space-y-4">
        {approvals.map((approval) => (
          <Card key={approval.id} className="transition-shadow hover:shadow-md">
            <CardContent className="p-6">
              <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div className="flex items-start gap-4">
                  {getStatusIcon(approval.status)}
                  <div>
                    <div className="flex items-center gap-2">
                      <h3 className="font-medium">{approval.type}</h3>
                      <Badge variant={getPriorityColor(approval.priority) as any} className="capitalize text-xs">{approval.priority}</Badge>
                    </div>
                    <p className="mt-1 text-sm text-muted-foreground">{approval.description}</p>
                    <p className="mt-1 text-xs text-muted-foreground">Requested by {approval.requestedBy} • {approval.date}</p>
                  </div>
                </div>
                {approval.status === "pending" && (
                  <div className="flex gap-2 shrink-0">
                    <Button size="sm" onClick={() => handleAction(approval.id, "approved")}>Approve</Button>
                    <Button size="sm" variant="outline" className="text-destructive" onClick={() => handleAction(approval.id, "rejected")}>Reject</Button>
                  </div>
                )}
                {approval.status !== "pending" && (
                  <Badge variant={approval.status === "approved" ? "default" : "destructive"} className="capitalize shrink-0">{approval.status}</Badge>
                )}
              </div>
            </CardContent>
          </Card>
        ))}
      </div>
    </div>
  );
}
