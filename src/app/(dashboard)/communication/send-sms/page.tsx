"use client";

import * as React from "react";
import { useRouter } from "next/navigation";
import { Send, Users, User, Smartphone, MessageSquare } from "lucide-react";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Textarea } from "@/components/ui/textarea";
import { Switch } from "@/components/ui/switch";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { mockDepartments } from "@/mock/departments";
import { toast } from "sonner";

export default function SendSMSPage() {
  const router = useRouter();
  const [isGroup, setIsGroup] = React.useState(false);
  const [sending, setSending] = React.useState(false);
  const [charCount, setCharCount] = React.useState(0);
  const maxChars = 160;

  const handleSend = async (e: React.FormEvent<HTMLFormElement>) => {
    e.preventDefault();
    setSending(true);
    await new Promise((resolve) => setTimeout(resolve, 1500));
    setSending(false);
    toast.success("SMS sent!", { description: "Your text message has been delivered." });
    router.push("/communication");
  };

  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-2xl font-bold tracking-tight">Send SMS</h1>
        <p className="text-sm text-muted-foreground">Send a text message to staff members</p>
      </div>

      <div className="grid gap-6 lg:grid-cols-3">
        <Card className="lg:col-span-2">
          <CardContent className="p-6">
            <form onSubmit={handleSend} className="space-y-5">
              {/* Options row */}
              <div className="flex items-center gap-4">
                <div className="flex items-center gap-2">
                  <Switch id="group" checked={isGroup} onCheckedChange={setIsGroup} />
                  <Label htmlFor="group" className="text-sm flex items-center gap-1.5 cursor-pointer">
                    {isGroup ? <Users className="h-4 w-4" /> : <User className="h-4 w-4" />}
                    {isGroup ? "Group SMS" : "Individual"}
                  </Label>
                </div>
              </div>

              {/* Recipient */}
              <div className="space-y-2">
                <Label htmlFor="to">Recipient</Label>
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
                  <Input id="to" name="to" type="tel" placeholder="+234 801 000 0000" required />
                )}
              </div>

              {/* Message */}
              <div className="space-y-2">
                <div className="flex items-center justify-between">
                  <Label htmlFor="body">Message</Label>
                  <span className={`text-xs ${charCount > maxChars ? "text-destructive font-medium" : "text-muted-foreground"}`}>
                    {charCount}/{maxChars} characters {charCount > maxChars && `(${Math.ceil(charCount / maxChars)} SMS)`}
                  </span>
                </div>
                <Textarea
                  id="body"
                  name="body"
                  placeholder="Type your SMS message..."
                  className="min-h-[160px] resize-none"
                  onChange={(e) => setCharCount(e.target.value.length)}
                  required
                />
                {charCount > maxChars && (
                  <p className="text-xs text-amber-600 dark:text-amber-400">
                    Message exceeds 160 characters and will be split into {Math.ceil(charCount / maxChars)} SMS messages.
                  </p>
                )}
              </div>

              {/* Actions */}
              <div className="flex items-center justify-between border-t pt-4">
                <Button type="button" variant="ghost" onClick={() => router.push("/communication")}>Cancel</Button>
                <Button type="submit" disabled={sending} className="min-w-[140px]">
                  {sending ? "Sending..." : <><Smartphone className="mr-2 h-4 w-4" />Send SMS</>}
                </Button>
              </div>
            </form>
          </CardContent>
        </Card>

        {/* Tips panel */}
        <Card className="h-fit">
          <CardHeader>
            <CardTitle className="text-sm flex items-center gap-2">
              <MessageSquare className="h-4 w-4" />SMS Tips
            </CardTitle>
          </CardHeader>
          <CardContent className="space-y-3">
            <div className="space-y-2 text-xs text-muted-foreground">
              <p className="font-medium text-foreground text-sm">Best Practices</p>
              <ul className="space-y-1.5 list-disc pl-4">
                <li>Keep messages under 160 characters for a single SMS</li>
                <li>Include your name or organization for identification</li>
                <li>Avoid sending SMS outside work hours</li>
                <li>Use SMS for urgent, time-sensitive updates</li>
                <li>Double-check phone numbers before sending</li>
              </ul>
            </div>
            <div className="rounded-lg bg-muted p-3">
              <p className="text-xs font-medium">Delivery Note</p>
              <p className="mt-1 text-[11px] text-muted-foreground">
                SMS delivery depends on the recipient&apos;s network availability. Group messages are sent individually to each member.
              </p>
            </div>
          </CardContent>
        </Card>
      </div>
    </div>
  );
}
