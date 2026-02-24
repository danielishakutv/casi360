"use client";

import * as React from "react";
import { useRouter } from "next/navigation";
import { Megaphone, Send, Clock, Eye, AlertTriangle, Info, CheckCircle } from "lucide-react";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Textarea } from "@/components/ui/textarea";
import { Switch } from "@/components/ui/switch";
import { Checkbox } from "@/components/ui/checkbox";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { mockDepartments } from "@/mock/departments";
import { toast } from "sonner";

const priorityConfig = {
  info: { label: "Informational", icon: Info, color: "text-blue-600 bg-blue-100 dark:bg-blue-900/30 dark:text-blue-400" },
  warning: { label: "Important", icon: AlertTriangle, color: "text-amber-600 bg-amber-100 dark:bg-amber-900/30 dark:text-amber-400" },
  urgent: { label: "Urgent", icon: Megaphone, color: "text-red-600 bg-red-100 dark:bg-red-900/30 dark:text-red-400" },
};

export default function SendNoticePage() {
  const router = useRouter();
  const [sending, setSending] = React.useState(false);
  const [priority, setPriority] = React.useState<"info" | "warning" | "urgent">("info");
  const [audience, setAudience] = React.useState("all");
  const [channels, setChannels] = React.useState({ email: true, sms: false, inApp: true });
  const [scheduleNotice, setScheduleNotice] = React.useState(false);
  const [preview, setPreview] = React.useState(false);
  const [formData, setFormData] = React.useState({ title: "", body: "", scheduleDate: "", scheduleTime: "" });

  const handleSend = async (e: React.FormEvent<HTMLFormElement>) => {
    e.preventDefault();
    setSending(true);
    await new Promise((resolve) => setTimeout(resolve, 2000));
    setSending(false);
    const channelList = Object.entries(channels).filter(([, v]) => v).map(([k]) => k).join(", ");
    toast.success(scheduleNotice ? "Notice scheduled!" : "Notice sent!", {
      description: `Delivered via ${channelList} to ${audience === "all" ? "all staff" : audience}.`,
    });
    router.push("/communication");
  };

  const PriorityIcon = priorityConfig[priority].icon;

  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-2xl font-bold tracking-tight">Send Notice</h1>
        <p className="text-sm text-muted-foreground">Broadcast a notice or announcement to staff</p>
      </div>

      <div className="grid gap-6 lg:grid-cols-3">
        <Card className="lg:col-span-2">
          <CardContent className="p-6">
            <form onSubmit={handleSend} className="space-y-5">
              {/* Priority */}
              <div className="space-y-2">
                <Label>Notice Priority</Label>
                <div className="flex gap-3">
                  {(Object.entries(priorityConfig) as [keyof typeof priorityConfig, typeof priorityConfig[keyof typeof priorityConfig]][]).map(([key, config]) => (
                    <button
                      key={key}
                      type="button"
                      onClick={() => setPriority(key)}
                      className={`flex items-center gap-2 rounded-lg border-2 px-4 py-2.5 text-sm transition-all ${priority === key ? "border-primary bg-primary/5 font-medium" : "border-transparent bg-muted hover:border-muted-foreground/20"}`}
                    >
                      <config.icon className={`h-4 w-4 ${priority === key ? "text-primary" : "text-muted-foreground"}`} />
                      {config.label}
                    </button>
                  ))}
                </div>
              </div>

              {/* Audience */}
              <div className="space-y-2">
                <Label>Audience</Label>
                <Select value={audience} onValueChange={setAudience}>
                  <SelectTrigger><SelectValue /></SelectTrigger>
                  <SelectContent>
                    <SelectItem value="all">All Staff</SelectItem>
                    <SelectItem value="managers">Managers Only</SelectItem>
                    {mockDepartments.map((d) => (
                      <SelectItem key={d.id} value={d.name}>{d.name} Department</SelectItem>
                    ))}
                  </SelectContent>
                </Select>
              </div>

              {/* Title */}
              <div className="space-y-2">
                <Label htmlFor="title">Notice Title</Label>
                <Input
                  id="title"
                  name="title"
                  placeholder="Enter notice title..."
                  value={formData.title}
                  onChange={(e) => setFormData({ ...formData, title: e.target.value })}
                  required
                />
              </div>

              {/* Body */}
              <div className="space-y-2">
                <Label htmlFor="body">Notice Content</Label>
                <Textarea
                  id="body"
                  name="body"
                  placeholder="Write the notice content here..."
                  className="min-h-[200px]"
                  value={formData.body}
                  onChange={(e) => setFormData({ ...formData, body: e.target.value })}
                  required
                />
              </div>

              {/* Delivery Channels */}
              <div className="space-y-3">
                <Label>Delivery Channels</Label>
                <div className="flex flex-wrap gap-4">
                  <div className="flex items-center gap-2">
                    <Checkbox id="ch-email" checked={channels.email} onCheckedChange={(v) => setChannels({ ...channels, email: !!v })} />
                    <Label htmlFor="ch-email" className="text-sm cursor-pointer">Email</Label>
                  </div>
                  <div className="flex items-center gap-2">
                    <Checkbox id="ch-sms" checked={channels.sms} onCheckedChange={(v) => setChannels({ ...channels, sms: !!v })} />
                    <Label htmlFor="ch-sms" className="text-sm cursor-pointer">SMS</Label>
                  </div>
                  <div className="flex items-center gap-2">
                    <Checkbox id="ch-inapp" checked={channels.inApp} onCheckedChange={(v) => setChannels({ ...channels, inApp: !!v })} />
                    <Label htmlFor="ch-inapp" className="text-sm cursor-pointer">In-App Notification</Label>
                  </div>
                </div>
              </div>

              {/* Schedule */}
              <div className="space-y-3">
                <div className="flex items-center gap-2">
                  <Switch id="schedule" checked={scheduleNotice} onCheckedChange={setScheduleNotice} />
                  <Label htmlFor="schedule" className="text-sm flex items-center gap-1.5 cursor-pointer">
                    <Clock className="h-4 w-4" />Schedule for later
                  </Label>
                </div>
                {scheduleNotice && (
                  <div className="grid gap-4 sm:grid-cols-2 pl-6">
                    <div className="space-y-2">
                      <Label htmlFor="scheduleDate">Date</Label>
                      <Input
                        id="scheduleDate"
                        type="date"
                        value={formData.scheduleDate}
                        onChange={(e) => setFormData({ ...formData, scheduleDate: e.target.value })}
                        required={scheduleNotice}
                      />
                    </div>
                    <div className="space-y-2">
                      <Label htmlFor="scheduleTime">Time</Label>
                      <Input
                        id="scheduleTime"
                        type="time"
                        value={formData.scheduleTime}
                        onChange={(e) => setFormData({ ...formData, scheduleTime: e.target.value })}
                        required={scheduleNotice}
                      />
                    </div>
                  </div>
                )}
              </div>

              {/* Actions */}
              <div className="flex items-center justify-between border-t pt-4">
                <div className="flex gap-2">
                  <Button type="button" variant="ghost" onClick={() => router.push("/communication")}>Cancel</Button>
                  <Button type="button" variant="outline" onClick={() => setPreview(!preview)}>
                    <Eye className="mr-2 h-4 w-4" />{preview ? "Hide Preview" : "Preview"}
                  </Button>
                </div>
                <Button type="submit" disabled={sending || (!channels.email && !channels.sms && !channels.inApp)} className="min-w-[160px]">
                  {sending ? "Sending..." : scheduleNotice ? <><Clock className="mr-2 h-4 w-4" />Schedule Notice</> : <><Megaphone className="mr-2 h-4 w-4" />Send Notice</>}
                </Button>
              </div>
            </form>
          </CardContent>
        </Card>

        {/* Preview / Info panel */}
        <div className="space-y-4">
          {preview && formData.title && (
            <Card className="border-2 border-dashed">
              <CardHeader className="pb-3">
                <div className="flex items-center gap-2">
                  <div className={`flex h-8 w-8 items-center justify-center rounded-lg ${priorityConfig[priority].color}`}>
                    <PriorityIcon className="h-4 w-4" />
                  </div>
                  <div>
                    <CardTitle className="text-sm">{formData.title || "Notice Title"}</CardTitle>
                    <CardDescription className="text-xs">To: {audience === "all" ? "All Staff" : audience}</CardDescription>
                  </div>
                </div>
              </CardHeader>
              <CardContent>
                <p className="text-sm text-muted-foreground whitespace-pre-wrap">{formData.body || "Notice content will appear here..."}</p>
                <div className="mt-4 flex flex-wrap gap-1.5">
                  <Badge variant="outline" className="text-[10px]">{priorityConfig[priority].label}</Badge>
                  {channels.email && <Badge variant="secondary" className="text-[10px]">Email</Badge>}
                  {channels.sms && <Badge variant="secondary" className="text-[10px]">SMS</Badge>}
                  {channels.inApp && <Badge variant="secondary" className="text-[10px]">In-App</Badge>}
                </div>
              </CardContent>
            </Card>
          )}

          <Card>
            <CardHeader>
              <CardTitle className="text-sm flex items-center gap-2"><Megaphone className="h-4 w-4" />About Notices</CardTitle>
            </CardHeader>
            <CardContent className="space-y-3 text-xs text-muted-foreground">
              <ul className="space-y-2">
                <li className="flex gap-2"><CheckCircle className="h-3.5 w-3.5 shrink-0 mt-0.5 text-emerald-500" /><span>Notices are broadcast to all selected recipients simultaneously</span></li>
                <li className="flex gap-2"><CheckCircle className="h-3.5 w-3.5 shrink-0 mt-0.5 text-emerald-500" /><span>Choose multiple delivery channels for maximum reach</span></li>
                <li className="flex gap-2"><CheckCircle className="h-3.5 w-3.5 shrink-0 mt-0.5 text-emerald-500" /><span>Schedule notices for optimal timing</span></li>
                <li className="flex gap-2"><CheckCircle className="h-3.5 w-3.5 shrink-0 mt-0.5 text-emerald-500" /><span>Urgent notices are highlighted for recipients</span></li>
              </ul>
              <div className="rounded-lg bg-muted p-3">
                <p className="text-xs font-medium text-foreground">Priority Levels</p>
                <ul className="mt-1.5 space-y-1 text-[11px]">
                  <li><span className="font-medium text-blue-600 dark:text-blue-400">Info:</span> General announcements</li>
                  <li><span className="font-medium text-amber-600 dark:text-amber-400">Important:</span> Requires attention</li>
                  <li><span className="font-medium text-red-600 dark:text-red-400">Urgent:</span> Immediate action needed</li>
                </ul>
              </div>
            </CardContent>
          </Card>
        </div>
      </div>
    </div>
  );
}
