"use client";

import * as React from "react";
import { useRouter } from "next/navigation";
import { Send, Users, User } from "lucide-react";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Textarea } from "@/components/ui/textarea";
import { Switch } from "@/components/ui/switch";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { toast } from "sonner";

export default function ComposePage() {
  const router = useRouter();
  const [type, setType] = React.useState<"email" | "sms">("email");
  const [isGroup, setIsGroup] = React.useState(false);
  const [sending, setSending] = React.useState(false);

  const handleSend = async (e: React.FormEvent<HTMLFormElement>) => {
    e.preventDefault();
    setSending(true);
    await new Promise((resolve) => setTimeout(resolve, 1500));
    setSending(false);
    toast.success("Message sent!", { description: `Your ${type} has been sent successfully.` });
    router.push("/communication/sent");
  };

  const handleSaveDraft = () => {
    toast.info("Draft saved", { description: "Your message has been saved as a draft." });
    router.push("/communication/drafts");
  };

  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-2xl font-bold tracking-tight">Compose Message</h1>
        <p className="text-sm text-muted-foreground">Send an email or SMS message</p>
      </div>

      <Card className="max-w-2xl">
        <CardContent className="p-6">
          <form onSubmit={handleSend} className="space-y-6">
            <div className="flex items-center gap-6">
              <div className="flex items-center gap-2">
                <Label className="text-sm text-muted-foreground">Type:</Label>
                <Select value={type} onValueChange={(v: "email" | "sms") => setType(v)}>
                  <SelectTrigger className="w-[120px]"><SelectValue /></SelectTrigger>
                  <SelectContent>
                    <SelectItem value="email">Email</SelectItem>
                    <SelectItem value="sms">SMS</SelectItem>
                  </SelectContent>
                </Select>
              </div>
              <div className="flex items-center gap-2">
                <Switch id="group" checked={isGroup} onCheckedChange={setIsGroup} />
                <Label htmlFor="group" className="text-sm flex items-center gap-1">
                  {isGroup ? <Users className="h-4 w-4" /> : <User className="h-4 w-4" />}
                  {isGroup ? "Group" : "Individual"}
                </Label>
              </div>
            </div>

            <div className="space-y-2">
              <Label htmlFor="to">To</Label>
              <Input id="to" name="to" placeholder={isGroup ? "Select group or enter multiple recipients" : type === "email" ? "recipient@email.com" : "+234 801 000 0000"} required />
            </div>

            {type === "email" && (
              <div className="space-y-2">
                <Label htmlFor="subject">Subject</Label>
                <Input id="subject" name="subject" placeholder="Enter subject line" required />
              </div>
            )}

            <div className="space-y-2">
              <Label htmlFor="body">Message</Label>
              <Textarea id="body" name="body" placeholder="Type your message here..." className="min-h-[200px]" required />
            </div>

            {type === "email" && (
              <div className="space-y-2">
                <Label>Attachment (mock)</Label>
                <div className="flex items-center justify-center rounded-lg border-2 border-dashed p-8 text-center">
                  <div className="text-muted-foreground">
                    <p className="text-sm">Drag and drop files here or click to browse</p>
                    <p className="mt-1 text-xs">PDF, DOC, XLSX up to 10MB</p>
                  </div>
                </div>
              </div>
            )}

            <div className="flex items-center justify-between pt-2">
              <Button type="button" variant="outline" onClick={handleSaveDraft}>Save as Draft</Button>
              <Button type="submit" disabled={sending}>
                {sending ? "Sending..." : <><Send className="mr-2 h-4 w-4" />Send {type === "email" ? "Email" : "SMS"}</>}
              </Button>
            </div>
          </form>
        </CardContent>
      </Card>
    </div>
  );
}
