"use client";

import * as React from "react";
import { Search, Download, Wallet, TrendingUp, Users, Clock, CheckCircle, AlertCircle } from "lucide-react";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/components/ui/table";
import { mockPayroll } from "@/mock/payroll";
import { PayrollRecord } from "@/types";
import { toast } from "sonner";

export default function PayrollPage() {
  const [records, setRecords] = React.useState<PayrollRecord[]>(mockPayroll);
  const [search, setSearch] = React.useState("");
  const [statusFilter, setStatusFilter] = React.useState("all");
  const [periodFilter, setPeriodFilter] = React.useState("all");

  const filtered = records.filter((rec) => {
    const matchSearch = rec.employeeName.toLowerCase().includes(search.toLowerCase()) || rec.department.toLowerCase().includes(search.toLowerCase());
    const matchStatus = statusFilter === "all" || rec.status === statusFilter;
    const matchPeriod = periodFilter === "all" || rec.payPeriod === periodFilter;
    return matchSearch && matchStatus && matchPeriod;
  });

  const totalPayroll = filtered.reduce((sum, r) => sum + r.netPay, 0);
  const paidCount = filtered.filter((r) => r.status === "paid").length;
  const pendingCount = filtered.filter((r) => r.status === "pending").length;
  const processingCount = filtered.filter((r) => r.status === "processing").length;

  const periods = [...new Set(records.map((r) => r.payPeriod))];

  const formatCurrency = (amount: number) =>
    new Intl.NumberFormat("en-NG", { style: "currency", currency: "NGN", minimumFractionDigits: 0 }).format(amount);

  const handleProcessPayroll = (id: string) => {
    setRecords((prev) =>
      prev.map((r) => (r.id === id ? { ...r, status: "processing" as const } : r))
    );
    toast.success("Payroll processing", { description: "Payment is being processed." });
  };

  const handleMarkPaid = (id: string) => {
    setRecords((prev) =>
      prev.map((r) => (r.id === id ? { ...r, status: "paid" as const } : r))
    );
    toast.success("Payment completed", { description: "Payroll marked as paid." });
  };

  return (
    <div className="space-y-6">
      <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
          <h1 className="text-2xl font-bold tracking-tight">Payroll</h1>
          <p className="text-sm text-muted-foreground">Manage employee salaries and payments</p>
        </div>
        <Button onClick={() => toast.info("Export", { description: "Payroll report exported." })}>
          <Download className="mr-2 h-4 w-4" />Export Report
        </Button>
      </div>

      {/* Summary Cards */}
      <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <Card>
          <CardContent className="p-6">
            <div className="flex items-center gap-3">
              <div className="flex h-10 w-10 items-center justify-center rounded-xl bg-blue-100 text-blue-600 dark:bg-blue-900/30 dark:text-blue-400">
                <Wallet className="h-5 w-5" />
              </div>
              <div>
                <p className="text-2xl font-bold">{formatCurrency(totalPayroll)}</p>
                <p className="text-xs text-muted-foreground">Total Payroll</p>
              </div>
            </div>
          </CardContent>
        </Card>
        <Card>
          <CardContent className="p-6">
            <div className="flex items-center gap-3">
              <div className="flex h-10 w-10 items-center justify-center rounded-xl bg-emerald-100 text-emerald-600 dark:bg-emerald-900/30 dark:text-emerald-400">
                <CheckCircle className="h-5 w-5" />
              </div>
              <div>
                <p className="text-2xl font-bold">{paidCount}</p>
                <p className="text-xs text-muted-foreground">Paid</p>
              </div>
            </div>
          </CardContent>
        </Card>
        <Card>
          <CardContent className="p-6">
            <div className="flex items-center gap-3">
              <div className="flex h-10 w-10 items-center justify-center rounded-xl bg-amber-100 text-amber-600 dark:bg-amber-900/30 dark:text-amber-400">
                <Clock className="h-5 w-5" />
              </div>
              <div>
                <p className="text-2xl font-bold">{processingCount}</p>
                <p className="text-xs text-muted-foreground">Processing</p>
              </div>
            </div>
          </CardContent>
        </Card>
        <Card>
          <CardContent className="p-6">
            <div className="flex items-center gap-3">
              <div className="flex h-10 w-10 items-center justify-center rounded-xl bg-red-100 text-red-600 dark:bg-red-900/30 dark:text-red-400">
                <AlertCircle className="h-5 w-5" />
              </div>
              <div>
                <p className="text-2xl font-bold">{pendingCount}</p>
                <p className="text-xs text-muted-foreground">Pending</p>
              </div>
            </div>
          </CardContent>
        </Card>
      </div>

      {/* Filters */}
      <Card>
        <CardContent className="p-4">
          <div className="flex flex-col gap-4 sm:flex-row sm:items-center">
            <div className="relative flex-1">
              <Search className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
              <Input placeholder="Search by name or department..." value={search} onChange={(e) => setSearch(e.target.value)} className="pl-9" />
            </div>
            <Select value={periodFilter} onValueChange={setPeriodFilter}>
              <SelectTrigger className="w-[180px]"><SelectValue placeholder="Pay Period" /></SelectTrigger>
              <SelectContent>
                <SelectItem value="all">All Periods</SelectItem>
                {periods.map((p) => (<SelectItem key={p} value={p}>{p}</SelectItem>))}
              </SelectContent>
            </Select>
            <Select value={statusFilter} onValueChange={setStatusFilter}>
              <SelectTrigger className="w-[140px]"><SelectValue placeholder="Status" /></SelectTrigger>
              <SelectContent>
                <SelectItem value="all">All Status</SelectItem>
                <SelectItem value="paid">Paid</SelectItem>
                <SelectItem value="processing">Processing</SelectItem>
                <SelectItem value="pending">Pending</SelectItem>
              </SelectContent>
            </Select>
          </div>
        </CardContent>
      </Card>

      {/* Payroll Table */}
      <Card>
        <CardContent className="p-0">
          <Table>
            <TableHeader>
              <TableRow>
                <TableHead>Employee</TableHead>
                <TableHead className="hidden md:table-cell">Department</TableHead>
                <TableHead className="hidden lg:table-cell">Base Salary</TableHead>
                <TableHead className="hidden lg:table-cell">Allowances</TableHead>
                <TableHead className="hidden lg:table-cell">Deductions</TableHead>
                <TableHead>Net Pay</TableHead>
                <TableHead className="hidden sm:table-cell">Period</TableHead>
                <TableHead>Status</TableHead>
                <TableHead className="w-[100px]">Action</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              {filtered.length === 0 ? (
                <TableRow>
                  <TableCell colSpan={9} className="h-32 text-center text-muted-foreground">No payroll records found</TableCell>
                </TableRow>
              ) : (
                filtered.map((rec) => (
                  <TableRow key={rec.id}>
                    <TableCell>
                      <div className="flex items-center gap-3">
                        <div className="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-primary text-xs font-bold text-primary-foreground">
                          {rec.employeeName.split(" ").map((n) => n[0]).join("")}
                        </div>
                        <p className="font-medium text-sm">{rec.employeeName}</p>
                      </div>
                    </TableCell>
                    <TableCell className="hidden md:table-cell text-sm">{rec.department}</TableCell>
                    <TableCell className="hidden lg:table-cell text-sm">{formatCurrency(rec.baseSalary)}</TableCell>
                    <TableCell className="hidden lg:table-cell text-sm text-emerald-600">+{formatCurrency(rec.allowances)}</TableCell>
                    <TableCell className="hidden lg:table-cell text-sm text-red-600">-{formatCurrency(rec.deductions)}</TableCell>
                    <TableCell className="font-semibold text-sm">{formatCurrency(rec.netPay)}</TableCell>
                    <TableCell className="hidden sm:table-cell text-sm text-muted-foreground">{rec.payPeriod}</TableCell>
                    <TableCell>
                      <Badge variant={rec.status === "paid" ? "default" : rec.status === "processing" ? "secondary" : "outline"} className="capitalize text-xs">
                        {rec.status}
                      </Badge>
                    </TableCell>
                    <TableCell>
                      {rec.status === "pending" && (
                        <Button size="sm" variant="outline" onClick={() => handleProcessPayroll(rec.id)}>Process</Button>
                      )}
                      {rec.status === "processing" && (
                        <Button size="sm" onClick={() => handleMarkPaid(rec.id)}>Mark Paid</Button>
                      )}
                      {rec.status === "paid" && (
                        <span className="text-xs text-muted-foreground">Completed</span>
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
