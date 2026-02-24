"use client";

import * as React from "react";
import { useRouter } from "next/navigation";
import { Send, Users, User, Mail, Paperclip, X } from "lucide-react";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Textarea } from "@/components/ui/textarea";
import { Switch } from "@/components/ui/switch";
import { Badge } from "@/components/ui/badge";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { mockDepartments } from "@/mock/departments";
import { toast } from "sonner";

export default function SendEmailPage() {
  const router = useRouter();
  const [isGroup, setIsGroup] = React.useState(false);
  const [sending, setSending] = React.useState(false);
  const [attachments, setAttachments] = React.useState<string[]>([]);
  const [priority, setPriority] = React.useState("normal");

  const handleSend = async (e: React.FormEvent<HTMLFormElement>) => {
    e.preventDefault();
    setSending(true);
    await new Promise((resolve) => setTimeout(resolve, 1500));
    setSending(false);
    toast.success("Email sent!", { description: "Your email has been delivered successfully." });
    router.push("/communication");
  };

  const addMockAttachment = () => {
    const files = ["Report_Q1_2026.pdf", "Budget_Review.xlsx", "Meeting_Notes.docx", "Presentation.pptx", "Staff_Photo.jpg"];
    const file = files[attachments.length % files.length];
    setAttachments([...attachments, file]);
  };

  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-2xl font-bold tracking-tight">Send Email</h1>
        <p className="text-sm text-muted-foreground">Compose and send an email message</p>
      </div>

      <Card className="max-w-3xl">
        <CardContent className="p-6">
          <form onSubmit={handleSend} className="space-y-5">
            {/* Options row */}
            <div className="flex flex-wrap items-center gap-6">
              <div className="flex items-center gap-2">
                <Switch id="group" checked={isGroup} onCheckedChange={setIsGroup} />
                <Label htmlFor="group" className="text-sm flex items-center gap-1.5 cursor-pointer">
                  {isGroup ? <Users className="h-4 w-4" /> : <User className="h-4 w-4" />}
                  {isGroup ? "Group Email" : "Individual"}
                </Label>
              </div>
              <div className="flex items-center gap-2">
                <Label className="text-sm text-muted-foreground">Priority:</Label>
                <Select value={priority} onValueChange={setPriority}>
                  <SelectTrigger className="w-[120px] h-8"><SelectValue /></SelectTrigger>
                  <SelectContent>
                    <SelectItem value="low">Low</SelectItem>
                    <SelectItem value="normal">Normal</SelectItem>
                    <SelectItem value="high">High</SelectItem>
                  </SelectContent>
                </Select>
              </div>
            </div>

            {/* To */}
            <div className="space-y-2">
              <Label htmlFor="to">To</Label>
              {isGroup ? (
                <Select>
                  <SelectTrigger><SelectValue placeholder="Select department or group..." /></SelectTrigger>
                  <SelectContent>
                    <SelectItem value="all">All Staff</SelectItem>
                    {mockDepartments.map((d) => (
                      <SelectItem key={d.id} value={d.name}>{d.name} Department</SelectItem>
                    ))}
                  </SelectContent>
                </Select>
              ) : (
                <Input id="to" name="to" type="email" placeholder="recipient@email.com" required />
              )}
            </div>

            {/* CC */}
            <div className="space-y-2">
              <Label htmlFor="cc">CC (optional)</Label>
              <Input id="cc" name="cc" type="email" placeholder="cc@email.com" />
            </div>

            {/* Subject */}
            <div className="space-y-2">
              <Label htmlFor="subject">Subject</Label>
              <Input id="subject" name="subject" placeholder="Enter email subject" required />
            </div>

            {/* Body */}
            <div className="space-y-2">
              <Label htmlFor="body">Message</Label>
              <Textarea id="body" name="body" placeholder="Compose your email message..." className="min-h-[240px]" required />
            </div>

            {/* Attachments */}
            <div className="space-y-3">
              <div className="flex items-center justify-between">
                <Label>Attachments</Label>
                <Button type="button" variant="outline" size="sm" onClick={addMockAttachment}>
                  <Paperclip className="mr-2 h-3.5 w-3.5" />Attach File
                </Button>
              </div>
              {attachments.length > 0 ? (
                <div className="flex flex-wrap gap-2">
                  {attachments.map((file, i) => (
                    <Badge key={i} variant="secondary" className="gap-1.5 py-1 pl-2.5 pr-1.5">
                      {file}
                      <button type="button" onClick={() => setAttachments(attachments.filter((_, idx) => idx !== i))} className="rounded-full p-0.5 hover:bg-muted-foreground/20">
                        <X className="h-3 w-3" />
                      </button>
                    </Badge>
                  ))}
                </div>
              ) : (
                <div className="flex items-center justify-center rounded-lg border-2 border-dashed p-6 text-center">
                  <div className="text-muted-foreground">
                    <Paperclip className="mx-auto mb-2 h-6 w-6 opacity-40" />
                    <p className="text-xs">Drag and drop files or click &quot;Attach File&quot;</p>
                    <p className="mt-0.5 text-[11px] opacity-60">PDF, DOC, XLSX, images up to 10MB</p>
                  </div>
                </div>
              )}
            </div>

            {/* Actions */}
            <div className="flex items-center justify-between border-t pt-4">
              <Button type="button" variant="ghost" onClick={() => router.push("/communication")}>Cancel</Button>
              <Button type="submit" disabled={sending} className="min-w-[140px]">
                {sending ? "Sending..." : <><Mail className="mr-2 h-4 w-4" />Send Email</>}
              </Button>
            </div>
          </form>
        </CardContent>
      </Card>
    </div>
  );
}
