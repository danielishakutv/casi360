"use client";

import * as React from "react";
import Link from "next/link";
import {
  ShoppingCart,
  ClipboardList,
  Store,
  Package,
  FileInput,
  TrendingUp,
  TrendingDown,
  AlertTriangle,
  DollarSign,
  ArrowUpRight,
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
import { mockPurchaseOrders } from "@/mock/purchase-orders";
import { mockVendors } from "@/mock/vendors";
import { mockInventory } from "@/mock/inventory";
import { mockRequisitions } from "@/mock/requisitions";
import { formatDistanceToNow } from "date-fns";

export default function ProcurementPage() {
  const pendingPOs = mockPurchaseOrders.filter((p) => p.status === "pending").length;
  const approvedPOs = mockPurchaseOrders.filter((p) => p.status === "approved").length;
  const activeVendors = mockVendors.filter((v) => v.status === "active").length;
  const lowStockItems = mockInventory.filter((i) => i.status === "low_stock").length;
  const outOfStock = mockInventory.filter((i) => i.status === "out_of_stock").length;
  const pendingReqs = mockRequisitions.filter((r) => r.status === "submitted").length;

  const totalPOValue = mockPurchaseOrders.reduce((s, p) => s + p.totalAmount, 0);

  const cards = [
    {
      title: "Purchase Orders",
      value: mockPurchaseOrders.length,
      subtitle: `${pendingPOs} pending, ${approvedPOs} approved`,
      icon: ClipboardList,
      color: "text-blue-600 bg-blue-100 dark:bg-blue-900/30 dark:text-blue-400",
      href: "/procurement/purchase-orders",
    },
    {
      title: "Vendors",
      value: mockVendors.length,
      subtitle: `${activeVendors} active vendors`,
      icon: Store,
      color: "text-purple-600 bg-purple-100 dark:bg-purple-900/30 dark:text-purple-400",
      href: "/procurement/vendors",
    },
    {
      title: "Inventory",
      value: mockInventory.length,
      subtitle: `${lowStockItems} low stock, ${outOfStock} out of stock`,
      icon: Package,
      color: lowStockItems + outOfStock > 3
        ? "text-amber-600 bg-amber-100 dark:bg-amber-900/30 dark:text-amber-400"
        : "text-emerald-600 bg-emerald-100 dark:bg-emerald-900/30 dark:text-emerald-400",
      href: "/procurement/inventory",
    },
    {
      title: "Requisitions",
      value: mockRequisitions.length,
      subtitle: `${pendingReqs} awaiting approval`,
      icon: FileInput,
      color: "text-rose-600 bg-rose-100 dark:bg-rose-900/30 dark:text-rose-400",
      href: "/procurement/requisitions",
    },
  ];

  const recentOrders = React.useMemo(
    () => [...mockPurchaseOrders]
      .sort((a, b) => new Date(b.createdAt).getTime() - new Date(a.createdAt).getTime())
      .slice(0, 5),
    []
  );

  const statusColor: Record<string, string> = {
    draft: "bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300",
    pending: "bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400",
    approved: "bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400",
    ordered: "bg-violet-100 text-violet-700 dark:bg-violet-900/30 dark:text-violet-400",
    delivered: "bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400",
    cancelled: "bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400",
  };

  const alertItems = mockInventory.filter(
    (i) => i.status === "low_stock" || i.status === "out_of_stock"
  );

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-2xl font-bold tracking-tight">Procurement</h1>
          <p className="text-sm text-muted-foreground">
            Manage purchase orders, vendors, inventory, and requisitions
          </p>
        </div>
        <Button asChild>
          <Link href="/procurement/purchase-orders">
            <ClipboardList className="mr-2 h-4 w-4" />
            New Purchase Order
          </Link>
        </Button>
      </div>

      {/* Summary Stat */}
      <Card className="border-dashed">
        <CardContent className="flex items-center gap-4 p-4">
          <div className="flex h-12 w-12 items-center justify-center rounded-xl bg-emerald-100 text-emerald-600 dark:bg-emerald-900/30 dark:text-emerald-400">
            <DollarSign className="h-6 w-6" />
          </div>
          <div>
            <p className="text-sm text-muted-foreground">Total PO Value</p>
            <p className="text-2xl font-bold">
              ₦{(totalPOValue / 1000000).toFixed(1)}M
            </p>
          </div>
          <div className="ml-auto flex items-center gap-1 text-sm text-emerald-600">
            <TrendingUp className="h-4 w-4" />
            <span>Active procurement pipeline</span>
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

      <div className="grid gap-6 lg:grid-cols-2">
        {/* Recent Purchase Orders */}
        <Card>
          <CardHeader className="flex flex-row items-center justify-between">
            <div>
              <CardTitle className="text-base">Recent Purchase Orders</CardTitle>
              <CardDescription>Latest procurement activities</CardDescription>
            </div>
            <Button variant="outline" size="sm" asChild>
              <Link href="/procurement/purchase-orders">View All</Link>
            </Button>
          </CardHeader>
          <CardContent>
            <div className="space-y-4">
              {recentOrders.map((po) => (
                <div
                  key={po.id}
                  className="flex items-center justify-between rounded-lg border p-3 transition-colors hover:bg-accent/50"
                >
                  <div className="flex-1 min-w-0">
                    <div className="flex items-center gap-2">
                      <p className="font-medium text-sm truncate">{po.title}</p>
                      <Badge
                        variant="secondary"
                        className={`text-[10px] shrink-0 ${statusColor[po.status]}`}
                      >
                        {po.status}
                      </Badge>
                    </div>
                    <p className="text-xs text-muted-foreground mt-1">
                      {po.vendorName} · {formatDistanceToNow(new Date(po.createdAt), { addSuffix: true })}
                    </p>
                  </div>
                  <p className="text-sm font-semibold ml-4">
                    ₦{(po.totalAmount / 1000000).toFixed(2)}M
                  </p>
                </div>
              ))}
            </div>
          </CardContent>
        </Card>

        {/* Stock Alerts */}
        <Card>
          <CardHeader>
            <CardTitle className="text-base flex items-center gap-2">
              <AlertTriangle className="h-4 w-4 text-amber-500" />
              Stock Alerts
            </CardTitle>
            <CardDescription>
              Items that need restocking attention
            </CardDescription>
          </CardHeader>
          <CardContent>
            <div className="space-y-3">
              {alertItems.length === 0 && (
                <p className="text-sm text-muted-foreground py-4 text-center">
                  All inventory levels are healthy
                </p>
              )}
              {alertItems.map((item) => (
                <div
                  key={item.id}
                  className="flex items-center justify-between rounded-lg border p-3"
                >
                  <div className="flex items-center gap-3">
                    <div
                      className={`h-2 w-2 rounded-full ${
                        item.status === "out_of_stock"
                          ? "bg-red-500"
                          : "bg-amber-500"
                      }`}
                    />
                    <div>
                      <p className="text-sm font-medium">{item.name}</p>
                      <p className="text-xs text-muted-foreground">
                        {item.category} · {item.location}
                      </p>
                    </div>
                  </div>
                  <div className="text-right">
                    <p
                      className={`text-sm font-semibold ${
                        item.status === "out_of_stock"
                          ? "text-red-600"
                          : "text-amber-600"
                      }`}
                    >
                      {item.quantity} {item.unit}
                    </p>
                    <p className="text-[10px] text-muted-foreground">
                      Reorder at {item.reorderLevel}
                    </p>
                  </div>
                </div>
              ))}
            </div>
          </CardContent>
        </Card>
      </div>
    </div>
  );
}
