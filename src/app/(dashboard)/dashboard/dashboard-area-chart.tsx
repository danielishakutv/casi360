"use client";

import {
  Card,
  CardContent,
  CardDescription,
  CardHeader,
  CardTitle,
} from "@/components/ui/card";
import {
  AreaChart,
  Area,
  XAxis,
  YAxis,
  CartesianGrid,
  Tooltip,
  ResponsiveContainer,
} from "recharts";

const areaChartData = [
  { name: "Week 1", messages: 12, approvals: 3 },
  { name: "Week 2", messages: 19, approvals: 5 },
  { name: "Week 3", messages: 15, approvals: 2 },
  { name: "Week 4", messages: 22, approvals: 6 },
];

const tooltipStyle = {
  backgroundColor: "hsl(var(--card))",
  border: "1px solid hsl(var(--border))",
  borderRadius: "8px",
  fontSize: "12px",
};

export default function DashboardAreaChart() {
  return (
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
              <Tooltip contentStyle={tooltipStyle} />
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
  );
}
