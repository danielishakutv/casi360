"use client";

import * as React from "react";
import Link from "next/link";
import { Mail, Smartphone, Megaphone, MessageSquare, Send, Search, Clock } from "lucide-react";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { Badge } from "@/components/ui/badge";
import { Input } from "@/components/ui/input";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs";
import { mockMessages } from "@/mock/messages";
import { cn } from "@/lib/utils";
import { formatDistanceToNow } from "date-fns";

export default function CommunicationPage() {
  const [search, setSearch] = React.useState("");
  const [selectedType, setSelectedType] = React.useState("all");

  const allMessages = mockMessages;
  const emails = React.useMemo(() => allMessages.filter((m) => m.type === "email"), [allMessages]);
  const sms = React.useMemo(() => allMessages.filter((m) => m.type === "sms"), [allMessages]);
  const sent = React.useMemo(() => allMessages.filter((m) => m.status === "sent"), [allMessages]);

  const displayMessages = selectedType === "all" ? allMessages : selectedType === "email" ? emails : sms;
  const filtered = React.useMemo(
    () =>
      search === ""
        ? displayMessages
        : displayMessages.filter(
            (m) =>
              m.to.toLowerCase().includes(search.toLowerCase()) ||
              (m.subject || "").toLowerCase().includes(search.toLowerCase()) ||
              m.body.toLowerCase().includes(search.toLowerCase()) ||
              m.from.toLowerCase().includes(search.toLowerCase())
          ),
    [displayMessages, search]
  );

  const quickActions = [
    { title: "Send Email", description: "Compose and send an email message", icon: Mail, color: "text-blue-600 bg-blue-100 dark:bg-blue-900/30 dark:text-blue-400", href: "/communication/send-email" },
    { title: "Send SMS", description: "Send an SMS text message", icon: Smartphone, color: "text-emerald-600 bg-emerald-100 dark:bg-emerald-900/30 dark:text-emerald-400", href: "/communication/send-sms" },
    { title: "Send Notice", description: "Broadcast a notice to staff", icon: Megaphone, color: "text-amber-600 bg-amber-100 dark:bg-amber-900/30 dark:text-amber-400", href: "/communication/send-notice" },
  ];

  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-2xl font-bold tracking-tight">Communication</h1>
        <p className="text-sm text-muted-foreground">Send emails, SMS, and notices to staff</p>
      </div>

      {/* Quick Actions */}
      <div className="grid gap-4 sm:grid-cols-3">
        {quickActions.map((action) => (
          <Link key={action.title} href={action.href}>
            <Card className="transition-all hover:shadow-md hover:-translate-y-0.5 cursor-pointer h-full">
              <CardContent className="p-6">
                <div className={`flex h-12 w-12 items-center justify-center rounded-xl ${action.color} mb-4`}>
                  <action.icon className="h-6 w-6" />
                </div>
                <h3 className="font-semibold text-sm">{action.title}</h3>
                <p className="text-xs text-muted-foreground mt-1">{action.description}</p>
              </CardContent>
            </Card>
          </Link>
        ))}
      </div>

      {/* Stats */}
      <div className="grid gap-4 sm:grid-cols-3">
        <Card>
          <CardContent className="p-4 flex items-center gap-3">
            <div className="flex h-10 w-10 items-center justify-center rounded-xl bg-blue-100 text-blue-600 dark:bg-blue-900/30 dark:text-blue-400">
              <Mail className="h-5 w-5" />
            </div>
            <div><p className="text-lg font-bold">{emails.length}</p><p className="text-xs text-muted-foreground">Emails Sent</p></div>
          </CardContent>
        </Card>
        <Card>
          <CardContent className="p-4 flex items-center gap-3">
            <div className="flex h-10 w-10 items-center justify-center rounded-xl bg-emerald-100 text-emerald-600 dark:bg-emerald-900/30 dark:text-emerald-400">
              <Smartphone className="h-5 w-5" />
            </div>
            <div><p className="text-lg font-bold">{sms.length}</p><p className="text-xs text-muted-foreground">SMS Sent</p></div>
          </CardContent>
        </Card>
        <Card>
          <CardContent className="p-4 flex items-center gap-3">
            <div className="flex h-10 w-10 items-center justify-center rounded-xl bg-purple-100 text-purple-600 dark:bg-purple-900/30 dark:text-purple-400">
              <Send className="h-5 w-5" />
            </div>
            <div><p className="text-lg font-bold">{sent.length}</p><p className="text-xs text-muted-foreground">Total Sent</p></div>
          </CardContent>
        </Card>
      </div>

      {/* Recent Messages */}
      <Card>
        <CardHeader>
          <CardTitle className="text-base">Recent Messages</CardTitle>
          <CardDescription>Your latest sent communications</CardDescription>
        </CardHeader>
        <CardContent className="space-y-4">
          <div className="flex flex-col gap-4 sm:flex-row sm:items-center">
            <div className="relative flex-1">
              <Search className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
              <Input placeholder="Search messages..." value={search} onChange={(e) => setSearch(e.target.value)} className="pl-9" />
            </div>
            <Tabs value={selectedType} onValueChange={setSelectedType} className="w-auto">
              <TabsList>
                <TabsTrigger value="all">All</TabsTrigger>
                <TabsTrigger value="email">Email</TabsTrigger>
                <TabsTrigger value="sms">SMS</TabsTrigger>
              </TabsList>
            </Tabs>
          </div>

          <div className="space-y-2">
            {filtered.length === 0 ? (
              <div className="flex flex-col items-center justify-center py-12 text-muted-foreground">
                <MessageSquare className="mb-3 h-10 w-10 opacity-40" />
                <p className="text-sm">No messages found</p>
              </div>
            ) : (
              filtered.slice(0, 10).map((msg) => (
                <div key={msg.id} className={cn("flex items-start gap-3 rounded-lg border p-3 transition-colors hover:bg-muted/50", !msg.read && "border-l-4 border-l-primary")}>
                  <div className={cn("mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-xs", msg.type === "email" ? "bg-blue-100 text-blue-600 dark:bg-blue-900/30 dark:text-blue-400" : "bg-emerald-100 text-emerald-600 dark:bg-emerald-900/30 dark:text-emerald-400")}>
                    {msg.type === "email" ? <Mail className="h-4 w-4" /> : <Smartphone className="h-4 w-4" />}
                  </div>
                  <div className="min-w-0 flex-1">
                    <div className="flex items-center gap-2">
                      <p className={cn("text-sm truncate", !msg.read && "font-semibold")}>{msg.subject || "SMS Message"}</p>
                      {msg.isGroup && <Badge variant="outline" className="text-[10px] shrink-0">Group</Badge>}
                    </div>
                    <p className="text-xs text-muted-foreground">To: {msg.to}</p>
                    <p className="mt-1 text-xs text-muted-foreground line-clamp-1">{msg.body}</p>
                  </div>
                  <div className="shrink-0 text-right">
                    <p className="text-xs text-muted-foreground flex items-center gap-1"><Clock className="h-3 w-3" />{formatDistanceToNow(new Date(msg.date), { addSuffix: true })}</p>
                    <Badge variant={msg.status === "sent" ? "default" : msg.status === "draft" ? "secondary" : "destructive"} className="mt-1 text-[10px] uppercase">{msg.status}</Badge>
                  </div>
                </div>
              ))
            )}
          </div>
        </CardContent>
      </Card>
    </div>
  );
}
