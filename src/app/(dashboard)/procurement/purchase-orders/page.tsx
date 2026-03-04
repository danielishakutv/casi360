"use client";

import * as React from "react";
import Link from "next/link";
import {
  ClipboardList,
  Search,
  Filter,
  Plus,
  Eye,
  ChevronRight,
  ArrowUpDown,
  Clock,
  CheckCircle2,
  XCircle,
  Truck,
  Package,
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
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from "@/components/ui/table";
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select";
import { mockPurchaseOrders } from "@/mock/purchase-orders";
import { format } from "date-fns";

export default function PurchaseOrdersPage() {
  const [search, setSearch] = React.useState("");
  const [statusFilter, setStatusFilter] = React.useState("all");

  const filtered = mockPurchaseOrders.filter((po) => {
    const matchesSearch =
      po.title.toLowerCase().includes(search.toLowerCase()) ||
      po.vendorName.toLowerCase().includes(search.toLowerCase()) ||
      po.id.toLowerCase().includes(search.toLowerCase());
    const matchesStatus = statusFilter === "all" || po.status === statusFilter;
    return matchesSearch && matchesStatus;
  });

  const statusColor: Record<string, string> = {
    draft: "bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300",
    pending: "bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400",
    approved: "bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400",
    ordered: "bg-violet-100 text-violet-700 dark:bg-violet-900/30 dark:text-violet-400",
    delivered: "bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400",
    cancelled: "bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400",
  };

  const priorityColor: Record<string, string> = {
    low: "bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400",
    medium: "bg-blue-100 text-blue-600 dark:bg-blue-900/30 dark:text-blue-400",
    high: "bg-orange-100 text-orange-600 dark:bg-orange-900/30 dark:text-orange-400",
    urgent: "bg-red-100 text-red-600 dark:bg-red-900/30 dark:text-red-400",
  };

  const statusIcon: Record<string, React.ElementType> = {
    draft: ClipboardList,
    pending: Clock,
    approved: CheckCircle2,
    ordered: Truck,
    delivered: Package,
    cancelled: XCircle,
  };

  const stats = [
    { label: "Total", value: mockPurchaseOrders.length, color: "text-foreground" },
    { label: "Pending", value: mockPurchaseOrders.filter((p) => p.status === "pending").length, color: "text-amber-600" },
    { label: "Approved", value: mockPurchaseOrders.filter((p) => p.status === "approved").length, color: "text-blue-600" },
    { label: "Delivered", value: mockPurchaseOrders.filter((p) => p.status === "delivered").length, color: "text-emerald-600" },
  ];

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-2xl font-bold tracking-tight">Purchase Orders</h1>
          <p className="text-sm text-muted-foreground">
            Track and manage all purchase orders
          </p>
        </div>
        <Button>
          <Plus className="mr-2 h-4 w-4" />
          New Order
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
      <Card>
        <CardContent className="p-4">
          <div className="flex flex-col gap-4 sm:flex-row sm:items-center">
            <div className="relative flex-1">
              <Search className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
              <Input
                placeholder="Search orders by title, vendor, or ID..."
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
                <SelectItem value="pending">Pending</SelectItem>
                <SelectItem value="approved">Approved</SelectItem>
                <SelectItem value="ordered">Ordered</SelectItem>
                <SelectItem value="delivered">Delivered</SelectItem>
                <SelectItem value="cancelled">Cancelled</SelectItem>
              </SelectContent>
            </Select>
          </div>
        </CardContent>
      </Card>

      {/* Table */}
      <Card>
        <CardContent className="p-0">
          <Table>
            <TableHeader>
              <TableRow>
                <TableHead className="w-[100px]">PO #</TableHead>
                <TableHead>Title</TableHead>
                <TableHead>Vendor</TableHead>
                <TableHead>Amount</TableHead>
                <TableHead>Priority</TableHead>
                <TableHead>Status</TableHead>
                <TableHead>Date</TableHead>
                <TableHead className="text-right">Delivery</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              {filtered.length === 0 && (
                <TableRow>
                  <TableCell colSpan={8} className="h-24 text-center text-muted-foreground">
                    No purchase orders found.
                  </TableCell>
                </TableRow>
              )}
              {filtered.map((po) => {
                const StatusIcon = statusIcon[po.status] || ClipboardList;
                return (
                  <TableRow key={po.id} className="cursor-pointer hover:bg-accent/50">
                    <TableCell className="font-mono text-xs">{po.id}</TableCell>
                    <TableCell>
                      <div>
                        <p className="font-medium text-sm">{po.title}</p>
                        <p className="text-xs text-muted-foreground">
                          {po.items.length} item{po.items.length > 1 ? "s" : ""}
                        </p>
                      </div>
                    </TableCell>
                    <TableCell className="text-sm">{po.vendorName}</TableCell>
                    <TableCell className="font-semibold text-sm">
                      ₦{po.totalAmount.toLocaleString()}
                    </TableCell>
                    <TableCell>
                      <Badge
                        variant="secondary"
                        className={`text-[10px] ${priorityColor[po.priority]}`}
                      >
                        {po.priority}
                      </Badge>
                    </TableCell>
                    <TableCell>
                      <Badge
                        variant="secondary"
                        className={`text-[10px] ${statusColor[po.status]}`}
                      >
                        <StatusIcon className="mr-1 h-3 w-3" />
                        {po.status}
                      </Badge>
                    </TableCell>
                    <TableCell className="text-xs text-muted-foreground">
                      {format(new Date(po.createdAt), "MMM d, yyyy")}
                    </TableCell>
                    <TableCell className="text-right text-xs text-muted-foreground">
                      {format(new Date(po.expectedDelivery), "MMM d, yyyy")}
                    </TableCell>
                  </TableRow>
                );
              })}
            </TableBody>
          </Table>
        </CardContent>
      </Card>
    </div>
  );
}
