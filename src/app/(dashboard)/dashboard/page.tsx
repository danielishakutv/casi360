"use client";

import * as React from "react";
import {
  Users,
  MessageSquare,
  CheckCircle,
  FileText,
  Clock,
  Calendar as CalendarIcon,
  TrendingUp,
  ArrowUpRight,
  ArrowDownRight,
  Activity,
} from "lucide-react";
import {
  Card,
  CardContent,
  CardDescription,
  CardHeader,
  CardTitle,
} from "@/components/ui/card";
import { Badge } from "@/components/ui/badge";
import { Separator } from "@/components/ui/separator";
import { Calendar } from "@/components/ui/calendar";
import { mockActivities } from "@/mock/activities";
import { mockEmployees } from "@/mock/employees";
import { mockMessages } from "@/mock/messages";
import { mockApprovals } from "@/mock/approvals";
import { mockLeaveRequests } from "@/mock/leaves";
import { useAuthStore } from "@/store/auth-store";
import { formatDistanceToNow, format } from "date-fns";
import {
  BarChart,
  Bar,
  XAxis,
  YAxis,
  CartesianGrid,
  Tooltip,
  ResponsiveContainer,
  PieChart,
  Pie,
  Cell,
  LineChart,
  Line,
  Area,
  AreaChart,
} from "recharts";

const barChartData = [
  { name: "Jan", employees: 18, requests: 5 },
  { name: "Feb", employees: 20, requests: 8 },
  { name: "Mar", employees: 22, requests: 3 },
  { name: "Apr", employees: 23, requests: 6 },
  { name: "May", employees: 24, requests: 4 },
  { name: "Jun", employees: 25, requests: 7 },
];

const pieChartData = [
  { name: "Administration", value: 3, color: "#6366F1" },
  { name: "Programs", value: 6, color: "#8B5CF6" },
  { name: "Finance", value: 4, color: "#EC4899" },
  { name: "IT", value: 4, color: "#14B8A6" },
  { name: "HR", value: 3, color: "#F97316" },
  { name: "Operations", value: 3, color: "#06B6D4" },
  { name: "Communications", value: 2, color: "#EAB308" },
];

const areaChartData = [
  { name: "Week 1", messages: 12, approvals: 3 },
  { name: "Week 2", messages: 19, approvals: 5 },
  { name: "Week 3", messages: 15, approvals: 2 },
  { name: "Week 4", messages: 22, approvals: 6 },
];

function LiveClock() {
  const [time, setTime] = React.useState(new Date());

  React.useEffect(() => {
    const timer = setInterval(() => setTime(new Date()), 1000);
    return () => clearInterval(timer);
  }, []);

  return (
    <div className="flex items-center gap-2 text-sm text-muted-foreground">
      <Clock className="h-4 w-4" />
      <span className="font-mono text-foreground">
        {time.toLocaleTimeString("en-US", {
          hour: "2-digit",
          minute: "2-digit",
          second: "2-digit",
        })}
      </span>
    </div>
  );
}

export default function DashboardPage() {
  const { user } = useAuthStore();
  const [date, setDate] = React.useState<Date | undefined>(new Date());

  const summaryCards = [
    {
      title: "Total Employees",
      value: mockEmployees.length,
      change: "+2",
      trend: "up",
      icon: Users,
      color: "text-blue-600 bg-blue-100 dark:bg-blue-900/30 dark:text-blue-400",
    },
    {
      title: "Messages",
      value: mockMessages.filter((m) => m.status === "sent").length,
      change: "+5",
      trend: "up",
      icon: MessageSquare,
      color: "text-purple-600 bg-purple-100 dark:bg-purple-900/30 dark:text-purple-400",
    },
    {
      title: "Pending Approvals",
      value: mockApprovals.filter((a) => a.status === "pending").length,
      change: "-1",
      trend: "down",
      icon: CheckCircle,
      color: "text-amber-600 bg-amber-100 dark:bg-amber-900/30 dark:text-amber-400",
    },
    {
      title: "Leave Requests",
      value: mockLeaveRequests.filter((l) => l.status === "pending").length,
      change: "+3",
      trend: "up",
      icon: FileText,
      color: "text-emerald-600 bg-emerald-100 dark:bg-emerald-900/30 dark:text-emerald-400",
    },
  ];

  const getActivityIcon = (type: string) => {
    switch (type) {
      case "create":
        return "bg-green-100 text-green-600 dark:bg-green-900/30 dark:text-green-400";
      case "update":
        return "bg-blue-100 text-blue-600 dark:bg-blue-900/30 dark:text-blue-400";
      case "delete":
        return "bg-red-100 text-red-600 dark:bg-red-900/30 dark:text-red-400";
      case "login":
        return "bg-purple-100 text-purple-600 dark:bg-purple-900/30 dark:text-purple-400";
      case "approval":
        return "bg-amber-100 text-amber-600 dark:bg-amber-900/30 dark:text-amber-400";
      default:
        return "bg-gray-100 text-gray-600 dark:bg-gray-900/30 dark:text-gray-400";
    }
  };

  return (
    <div className="space-y-6">
      {/* Page Header */}
      <div className="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
        <div>
          <h1 className="text-2xl font-bold tracking-tight">Dashboard</h1>
          <p className="text-sm text-muted-foreground">
            Welcome back, {user?.name?.split(" ")[0]}! Here&apos;s what&apos;s happening today.
          </p>
        </div>
        <div className="flex items-center gap-4">
          <div className="text-sm text-muted-foreground">
            {format(new Date(), "EEEE, MMMM d, yyyy")}
          </div>
          <LiveClock />
        </div>
      </div>

      {/* Summary Cards */}
      <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        {summaryCards.map((card) => (
          <Card key={card.title} className="relative overflow-hidden">
            <CardContent className="p-6">
              <div className="flex items-center justify-between">
                <div
                  className={`flex h-10 w-10 items-center justify-center rounded-xl ${card.color}`}
                >
                  <card.icon className="h-5 w-5" />
                </div>
                <div
                  className={`flex items-center gap-1 text-xs font-medium ${
                    card.trend === "up"
                      ? "text-green-600 dark:text-green-400"
                      : "text-red-600 dark:text-red-400"
                  }`}
                >
                  {card.change}
                  {card.trend === "up" ? (
                    <ArrowUpRight className="h-3 w-3" />
                  ) : (
                    <ArrowDownRight className="h-3 w-3" />
                  )}
                </div>
              </div>
              <div className="mt-4">
                <p className="text-2xl font-bold">{card.value}</p>
                <p className="text-xs text-muted-foreground">{card.title}</p>
              </div>
            </CardContent>
          </Card>
        ))}
      </div>

      {/* Charts Row */}
      <div className="grid gap-6 lg:grid-cols-2">
        {/* Bar Chart */}
        <Card>
          <CardHeader>
            <CardTitle className="text-base">Employee Growth</CardTitle>
            <CardDescription>Monthly employee count vs. requests</CardDescription>
          </CardHeader>
          <CardContent>
            <div className="h-[300px]">
              <ResponsiveContainer width="100%" height="100%">
                <BarChart data={barChartData}>
                  <CartesianGrid strokeDasharray="3 3" className="stroke-muted" />
                  <XAxis dataKey="name" className="text-xs" tick={{ fontSize: 12 }} />
                  <YAxis className="text-xs" tick={{ fontSize: 12 }} />
                  <Tooltip
                    contentStyle={{
                      backgroundColor: "hsl(var(--card))",
                      border: "1px solid hsl(var(--border))",
                      borderRadius: "8px",
                      fontSize: "12px",
                    }}
                  />
                  <Bar dataKey="employees" fill="#6366F1" radius={[4, 4, 0, 0]} />
                  <Bar dataKey="requests" fill="#A5B4FC" radius={[4, 4, 0, 0]} />
                </BarChart>
              </ResponsiveContainer>
            </div>
          </CardContent>
        </Card>

        {/* Pie Chart */}
        <Card>
          <CardHeader>
            <CardTitle className="text-base">Department Distribution</CardTitle>
            <CardDescription>Employees across departments</CardDescription>
          </CardHeader>
          <CardContent>
            <div className="h-[300px] flex items-center">
              <ResponsiveContainer width="100%" height="100%">
                <PieChart>
                  <Pie
                    data={pieChartData}
                    cx="50%"
                    cy="50%"
                    innerRadius={60}
                    outerRadius={100}
                    paddingAngle={4}
                    dataKey="value"
                  >
                    {pieChartData.map((entry, index) => (
                      <Cell key={`cell-${index}`} fill={entry.color} />
                    ))}
                  </Pie>
                  <Tooltip
                    contentStyle={{
                      backgroundColor: "hsl(var(--card))",
                      border: "1px solid hsl(var(--border))",
                      borderRadius: "8px",
                      fontSize: "12px",
                    }}
                  />
                </PieChart>
              </ResponsiveContainer>
              <div className="flex flex-col gap-2 min-w-[140px]">
                {pieChartData.map((item) => (
                  <div key={item.name} className="flex items-center gap-2 text-xs">
                    <div
                      className="h-3 w-3 rounded-full shrink-0"
                      style={{ backgroundColor: item.color }}
                    />
                    <span className="text-muted-foreground truncate">{item.name}</span>
                    <span className="ml-auto font-medium">{item.value}</span>
                  </div>
                ))}
              </div>
            </div>
          </CardContent>
        </Card>
      </div>

      {/* Activity Feed + Calendar */}
      <div className="grid gap-6 lg:grid-cols-3">
        {/* Activity Feed */}
        <Card className="lg:col-span-2">
          <CardHeader>
            <CardTitle className="flex items-center gap-2 text-base">
              <Activity className="h-4 w-4" />
              Recent Activity
            </CardTitle>
            <CardDescription>Latest actions across the system</CardDescription>
          </CardHeader>
          <CardContent>
            <div className="space-y-4">
              {mockActivities.slice(0, 8).map((activity) => (
                <div
                  key={activity.id}
                  className="flex items-start gap-3"
                >
                  <div
                    className={`mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-full ${getActivityIcon(
                      activity.type
                    )}`}
                  >
                    <Activity className="h-3.5 w-3.5" />
                  </div>
                  <div className="flex-1 min-w-0">
                    <p className="text-sm">
                      <span className="font-medium">{activity.user}</span>{" "}
                      <span className="text-muted-foreground">
                        {activity.action}
                      </span>{" "}
                      <span className="font-medium">{activity.target}</span>
                    </p>
                    <p className="text-xs text-muted-foreground">
                      {formatDistanceToNow(new Date(activity.date), {
                        addSuffix: true,
                      })}
                    </p>
                  </div>
                  <Badge variant="outline" className="shrink-0 capitalize text-xs">
                    {activity.type}
                  </Badge>
                </div>
              ))}
            </div>
          </CardContent>
        </Card>

        {/* Calendar */}
        <Card>
          <CardHeader>
            <CardTitle className="flex items-center gap-2 text-base">
              <CalendarIcon className="h-4 w-4" />
              Calendar
            </CardTitle>
          </CardHeader>
          <CardContent className="flex justify-center">
            <Calendar
              mode="single"
              selected={date}
              onSelect={setDate}
              className="rounded-md"
            />
          </CardContent>
        </Card>
      </div>

      {/* Area Chart */}
      <Card>
        <CardHeader>
          <CardTitle className="text-base">Weekly Activity Trend</CardTitle>
          <CardDescription>Messages and approvals by week</CardDescription>
        </CardHeader>
        <CardContent>
          <div className="h-[250px]">
            <ResponsiveContainer width="100%" height="100%">
              <AreaChart data={areaChartData}>
                <CartesianGrid strokeDasharray="3 3" className="stroke-muted" />
                <XAxis dataKey="name" tick={{ fontSize: 12 }} />
                <YAxis tick={{ fontSize: 12 }} />
                <Tooltip
                  contentStyle={{
                    backgroundColor: "hsl(var(--card))",
                    border: "1px solid hsl(var(--border))",
                    borderRadius: "8px",
                    fontSize: "12px",
                  }}
                />
                <Area
                  type="monotone"
                  dataKey="messages"
                  stackId="1"
                  stroke="#6366F1"
                  fill="#6366F1"
                  fillOpacity={0.2}
                />
                <Area
                  type="monotone"
                  dataKey="approvals"
                  stackId="1"
                  stroke="#14B8A6"
                  fill="#14B8A6"
                  fillOpacity={0.2}
                />
              </AreaChart>
            </ResponsiveContainer>
          </div>
        </CardContent>
      </Card>
    </div>
  );
}
