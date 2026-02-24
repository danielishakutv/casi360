"use client";

import { Building2, Users } from "lucide-react";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { Badge } from "@/components/ui/badge";
import { mockDepartments } from "@/mock/departments";
import { mockEmployees } from "@/mock/employees";

export default function DepartmentsPage() {
  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-2xl font-bold tracking-tight">Departments</h1>
        <p className="text-sm text-muted-foreground">{mockDepartments.length} departments in the organization</p>
      </div>

      <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        {mockDepartments.map((dept) => {
          const deptEmployees = mockEmployees.filter((e) => e.department === dept.name);
          return (
            <Card key={dept.id} className="transition-shadow hover:shadow-md">
              <CardHeader className="pb-3">
                <div className="flex items-center justify-between">
                  <div className="flex h-10 w-10 items-center justify-center rounded-xl" style={{ backgroundColor: dept.color + "20", color: dept.color }}>
                    <Building2 className="h-5 w-5" />
                  </div>
                  <Badge variant="outline" className="text-xs">{deptEmployees.length} members</Badge>
                </div>
                <CardTitle className="mt-3 text-base">{dept.name}</CardTitle>
                <CardDescription className="text-xs">{dept.description}</CardDescription>
              </CardHeader>
              <CardContent>
                <div className="space-y-3">
                  <div className="flex items-center justify-between text-sm">
                    <span className="text-muted-foreground">Head</span>
                    <span className="font-medium">{dept.head}</span>
                  </div>
                  {deptEmployees.length > 0 && (
                    <div className="flex -space-x-2">
                      {deptEmployees.slice(0, 5).map((emp) => (
                        <div key={emp.id} className="flex h-7 w-7 items-center justify-center rounded-full border-2 border-background bg-primary text-[10px] font-bold text-primary-foreground" title={emp.name}>
                          {emp.name.split(" ").map((n) => n[0]).join("")}
                        </div>
                      ))}
                      {deptEmployees.length > 5 && (
                        <div className="flex h-7 w-7 items-center justify-center rounded-full border-2 border-background bg-muted text-[10px] font-medium">
                          +{deptEmployees.length - 5}
                        </div>
                      )}
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
