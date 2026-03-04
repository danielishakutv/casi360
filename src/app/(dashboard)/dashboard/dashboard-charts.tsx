"use client";

import {
  Card,
  CardContent,
  CardDescription,
  CardHeader,
  CardTitle,
} from "@/components/ui/card";
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

const tooltipStyle = {
  backgroundColor: "hsl(var(--card))",
  border: "1px solid hsl(var(--border))",
  borderRadius: "8px",
  fontSize: "12px",
};

export default function DashboardCharts() {
  return (
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
                <Tooltip contentStyle={tooltipStyle} />
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
                <Tooltip contentStyle={tooltipStyle} />
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
  );
}
