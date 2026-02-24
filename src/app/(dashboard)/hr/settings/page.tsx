"use client";

import * as React from "react";
import { Settings, Bell, Shield, Clock, Mail, Globe, Save, RotateCcw } from "lucide-react";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Switch } from "@/components/ui/switch";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs";
import { Separator } from "@/components/ui/separator";
import { toast } from "sonner";

export default function HRSettingsPage() {
  const [leaveSettings, setLeaveSettings] = React.useState({
    annualLeaveDays: 21,
    sickLeaveDays: 10,
    personalLeaveDays: 5,
    maternityLeaveDays: 90,
    paternityLeaveDays: 14,
    carryOverLimit: 5,
    minAdvanceNotice: 14,
    autoApprove: false,
    requireDocument: true,
  });

  const [payrollSettings, setPayrollSettings] = React.useState({
    payDay: 28,
    currency: "NGN",
    taxRate: 10,
    pensionRate: 8,
    nhfRate: 2.5,
    autoProcess: false,
    sendPayslip: true,
  });

  const [notificationSettings, setNotificationSettings] = React.useState({
    leaveRequests: true,
    payrollProcessed: true,
    newEmployee: true,
    birthdayReminder: true,
    contractExpiry: true,
    performanceReview: true,
    emailNotifications: true,
    inAppNotifications: true,
  });

  const [workSettings, setWorkSettings] = React.useState({
    workDaysPerWeek: 5,
    workHoursPerDay: 8,
    startTime: "09:00",
    endTime: "17:00",
    probationMonths: 3,
    retirementAge: 60,
  });

  const handleSave = (section: string) => {
    toast.success("Settings saved", { description: `${section} settings updated successfully.` });
  };

  const handleReset = (section: string) => {
    toast.info("Settings reset", { description: `${section} settings restored to defaults.` });
  };

  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-2xl font-bold tracking-tight">HR Settings</h1>
        <p className="text-sm text-muted-foreground">Configure HR module preferences and policies</p>
      </div>

      <Tabs defaultValue="leave" className="space-y-6">
        <TabsList className="grid w-full grid-cols-2 lg:grid-cols-4">
          <TabsTrigger value="leave">Leave Policy</TabsTrigger>
          <TabsTrigger value="payroll">Payroll</TabsTrigger>
          <TabsTrigger value="notifications">Notifications</TabsTrigger>
          <TabsTrigger value="work">Work Schedule</TabsTrigger>
        </TabsList>

        {/* Leave Policy Tab */}
        <TabsContent value="leave" className="space-y-6">
          <Card>
            <CardHeader>
              <CardTitle className="text-base flex items-center gap-2"><Clock className="h-4 w-4" />Leave Allowances</CardTitle>
              <CardDescription>Set the number of leave days per category per year</CardDescription>
            </CardHeader>
            <CardContent className="space-y-4">
              <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <div className="space-y-2">
                  <Label>Annual Leave (days)</Label>
                  <Input type="number" value={leaveSettings.annualLeaveDays} onChange={(e) => setLeaveSettings({ ...leaveSettings, annualLeaveDays: Number(e.target.value) })} />
                </div>
                <div className="space-y-2">
                  <Label>Sick Leave (days)</Label>
                  <Input type="number" value={leaveSettings.sickLeaveDays} onChange={(e) => setLeaveSettings({ ...leaveSettings, sickLeaveDays: Number(e.target.value) })} />
                </div>
                <div className="space-y-2">
                  <Label>Personal Leave (days)</Label>
                  <Input type="number" value={leaveSettings.personalLeaveDays} onChange={(e) => setLeaveSettings({ ...leaveSettings, personalLeaveDays: Number(e.target.value) })} />
                </div>
                <div className="space-y-2">
                  <Label>Maternity Leave (days)</Label>
                  <Input type="number" value={leaveSettings.maternityLeaveDays} onChange={(e) => setLeaveSettings({ ...leaveSettings, maternityLeaveDays: Number(e.target.value) })} />
                </div>
                <div className="space-y-2">
                  <Label>Paternity Leave (days)</Label>
                  <Input type="number" value={leaveSettings.paternityLeaveDays} onChange={(e) => setLeaveSettings({ ...leaveSettings, paternityLeaveDays: Number(e.target.value) })} />
                </div>
                <div className="space-y-2">
                  <Label>Carry Over Limit (days)</Label>
                  <Input type="number" value={leaveSettings.carryOverLimit} onChange={(e) => setLeaveSettings({ ...leaveSettings, carryOverLimit: Number(e.target.value) })} />
                </div>
              </div>
              <Separator />
              <div className="space-y-4">
                <div className="space-y-2">
                  <Label>Minimum Advance Notice (days)</Label>
                  <Input type="number" value={leaveSettings.minAdvanceNotice} onChange={(e) => setLeaveSettings({ ...leaveSettings, minAdvanceNotice: Number(e.target.value) })} className="max-w-[200px]" />
                </div>
                <div className="flex items-center justify-between">
                  <div>
                    <p className="text-sm font-medium">Auto-approve Leave Requests</p>
                    <p className="text-xs text-muted-foreground">Automatically approve leave requests within allowance</p>
                  </div>
                  <Switch checked={leaveSettings.autoApprove} onCheckedChange={(v) => setLeaveSettings({ ...leaveSettings, autoApprove: v })} />
                </div>
                <div className="flex items-center justify-between">
                  <div>
                    <p className="text-sm font-medium">Require Supporting Documents</p>
                    <p className="text-xs text-muted-foreground">Require document upload for sick and extended leave</p>
                  </div>
                  <Switch checked={leaveSettings.requireDocument} onCheckedChange={(v) => setLeaveSettings({ ...leaveSettings, requireDocument: v })} />
                </div>
              </div>
              <div className="flex gap-2 pt-4">
                <Button onClick={() => handleSave("Leave Policy")}><Save className="mr-2 h-4 w-4" />Save Changes</Button>
                <Button variant="outline" onClick={() => handleReset("Leave Policy")}><RotateCcw className="mr-2 h-4 w-4" />Reset</Button>
              </div>
            </CardContent>
          </Card>
        </TabsContent>

        {/* Payroll Tab */}
        <TabsContent value="payroll" className="space-y-6">
          <Card>
            <CardHeader>
              <CardTitle className="text-base flex items-center gap-2"><Settings className="h-4 w-4" />Payroll Configuration</CardTitle>
              <CardDescription>Configure payroll processing settings</CardDescription>
            </CardHeader>
            <CardContent className="space-y-4">
              <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <div className="space-y-2">
                  <Label>Pay Day (day of month)</Label>
                  <Input type="number" min={1} max={31} value={payrollSettings.payDay} onChange={(e) => setPayrollSettings({ ...payrollSettings, payDay: Number(e.target.value) })} />
                </div>
                <div className="space-y-2">
                  <Label>Currency</Label>
                  <Input value={payrollSettings.currency} onChange={(e) => setPayrollSettings({ ...payrollSettings, currency: e.target.value })} />
                </div>
                <div className="space-y-2">
                  <Label>Tax Rate (%)</Label>
                  <Input type="number" step="0.5" value={payrollSettings.taxRate} onChange={(e) => setPayrollSettings({ ...payrollSettings, taxRate: Number(e.target.value) })} />
                </div>
                <div className="space-y-2">
                  <Label>Pension Rate (%)</Label>
                  <Input type="number" step="0.5" value={payrollSettings.pensionRate} onChange={(e) => setPayrollSettings({ ...payrollSettings, pensionRate: Number(e.target.value) })} />
                </div>
                <div className="space-y-2">
                  <Label>NHF Rate (%)</Label>
                  <Input type="number" step="0.5" value={payrollSettings.nhfRate} onChange={(e) => setPayrollSettings({ ...payrollSettings, nhfRate: Number(e.target.value) })} />
                </div>
              </div>
              <Separator />
              <div className="space-y-4">
                <div className="flex items-center justify-between">
                  <div>
                    <p className="text-sm font-medium">Auto-process Payroll</p>
                    <p className="text-xs text-muted-foreground">Automatically process payroll on pay day</p>
                  </div>
                  <Switch checked={payrollSettings.autoProcess} onCheckedChange={(v) => setPayrollSettings({ ...payrollSettings, autoProcess: v })} />
                </div>
                <div className="flex items-center justify-between">
                  <div>
                    <p className="text-sm font-medium">Send Pay Slips</p>
                    <p className="text-xs text-muted-foreground">Email pay slips to employees after processing</p>
                  </div>
                  <Switch checked={payrollSettings.sendPayslip} onCheckedChange={(v) => setPayrollSettings({ ...payrollSettings, sendPayslip: v })} />
                </div>
              </div>
              <div className="flex gap-2 pt-4">
                <Button onClick={() => handleSave("Payroll")}><Save className="mr-2 h-4 w-4" />Save Changes</Button>
                <Button variant="outline" onClick={() => handleReset("Payroll")}><RotateCcw className="mr-2 h-4 w-4" />Reset</Button>
              </div>
            </CardContent>
          </Card>
        </TabsContent>

        {/* Notifications Tab */}
        <TabsContent value="notifications" className="space-y-6">
          <Card>
            <CardHeader>
              <CardTitle className="text-base flex items-center gap-2"><Bell className="h-4 w-4" />Notification Preferences</CardTitle>
              <CardDescription>Choose which HR events trigger notifications</CardDescription>
            </CardHeader>
            <CardContent className="space-y-4">
              <div className="space-y-4">
                {[
                  { key: "leaveRequests", label: "Leave Requests", desc: "Notify when a leave request is submitted or updated" },
                  { key: "payrollProcessed", label: "Payroll Processed", desc: "Notify when payroll is processed" },
                  { key: "newEmployee", label: "New Employee", desc: "Notify when a new employee is added" },
                  { key: "birthdayReminder", label: "Birthday Reminders", desc: "Remind about upcoming employee birthdays" },
                  { key: "contractExpiry", label: "Contract Expiry", desc: "Alert when employee contracts are nearing expiry" },
                  { key: "performanceReview", label: "Performance Reviews", desc: "Notify when performance reviews are due" },
                ].map((item) => (
                  <div key={item.key} className="flex items-center justify-between">
                    <div>
                      <p className="text-sm font-medium">{item.label}</p>
                      <p className="text-xs text-muted-foreground">{item.desc}</p>
                    </div>
                    <Switch
                      checked={notificationSettings[item.key as keyof typeof notificationSettings]}
                      onCheckedChange={(v) => setNotificationSettings({ ...notificationSettings, [item.key]: v })}
                    />
                  </div>
                ))}
              </div>
              <Separator />
              <h3 className="text-sm font-semibold">Delivery Channels</h3>
              <div className="space-y-4">
                <div className="flex items-center justify-between">
                  <div className="flex items-center gap-2">
                    <Mail className="h-4 w-4 text-muted-foreground" />
                    <div>
                      <p className="text-sm font-medium">Email Notifications</p>
                      <p className="text-xs text-muted-foreground">Send notifications via email</p>
                    </div>
                  </div>
                  <Switch checked={notificationSettings.emailNotifications} onCheckedChange={(v) => setNotificationSettings({ ...notificationSettings, emailNotifications: v })} />
                </div>
                <div className="flex items-center justify-between">
                  <div className="flex items-center gap-2">
                    <Globe className="h-4 w-4 text-muted-foreground" />
                    <div>
                      <p className="text-sm font-medium">In-App Notifications</p>
                      <p className="text-xs text-muted-foreground">Show notifications within the application</p>
                    </div>
                  </div>
                  <Switch checked={notificationSettings.inAppNotifications} onCheckedChange={(v) => setNotificationSettings({ ...notificationSettings, inAppNotifications: v })} />
                </div>
              </div>
              <div className="flex gap-2 pt-4">
                <Button onClick={() => handleSave("Notifications")}><Save className="mr-2 h-4 w-4" />Save Changes</Button>
                <Button variant="outline" onClick={() => handleReset("Notifications")}><RotateCcw className="mr-2 h-4 w-4" />Reset</Button>
              </div>
            </CardContent>
          </Card>
        </TabsContent>

        {/* Work Schedule Tab */}
        <TabsContent value="work" className="space-y-6">
          <Card>
            <CardHeader>
              <CardTitle className="text-base flex items-center gap-2"><Clock className="h-4 w-4" />Work Schedule</CardTitle>
              <CardDescription>Configure default work schedule and employment settings</CardDescription>
            </CardHeader>
            <CardContent className="space-y-4">
              <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <div className="space-y-2">
                  <Label>Work Days Per Week</Label>
                  <Input type="number" min={1} max={7} value={workSettings.workDaysPerWeek} onChange={(e) => setWorkSettings({ ...workSettings, workDaysPerWeek: Number(e.target.value) })} />
                </div>
                <div className="space-y-2">
                  <Label>Work Hours Per Day</Label>
                  <Input type="number" min={1} max={24} value={workSettings.workHoursPerDay} onChange={(e) => setWorkSettings({ ...workSettings, workHoursPerDay: Number(e.target.value) })} />
                </div>
                <div className="space-y-2">
                  <Label>Start Time</Label>
                  <Input type="time" value={workSettings.startTime} onChange={(e) => setWorkSettings({ ...workSettings, startTime: e.target.value })} />
                </div>
                <div className="space-y-2">
                  <Label>End Time</Label>
                  <Input type="time" value={workSettings.endTime} onChange={(e) => setWorkSettings({ ...workSettings, endTime: e.target.value })} />
                </div>
                <div className="space-y-2">
                  <Label>Probation Period (months)</Label>
                  <Input type="number" min={0} value={workSettings.probationMonths} onChange={(e) => setWorkSettings({ ...workSettings, probationMonths: Number(e.target.value) })} />
                </div>
                <div className="space-y-2">
                  <Label>Retirement Age</Label>
                  <Input type="number" min={50} max={70} value={workSettings.retirementAge} onChange={(e) => setWorkSettings({ ...workSettings, retirementAge: Number(e.target.value) })} />
                </div>
              </div>
              <div className="flex gap-2 pt-4">
                <Button onClick={() => handleSave("Work Schedule")}><Save className="mr-2 h-4 w-4" />Save Changes</Button>
                <Button variant="outline" onClick={() => handleReset("Work Schedule")}><RotateCcw className="mr-2 h-4 w-4" />Reset</Button>
              </div>
            </CardContent>
          </Card>
        </TabsContent>
      </Tabs>
    </div>
  );
}
