"use client";

import * as React from "react";
import Link from "next/link";
import { Inbox, Send, FileText, PenSquare, Mail, MessageCircle, Search } from "lucide-react";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs";
import { mockMessages } from "@/mock/messages";
import { cn } from "@/lib/utils";
import { formatDistanceToNow } from "date-fns";

export default function CommunicationPage() {
  const [search, setSearch] = React.useState("");
  const [selectedType, setSelectedType] = React.useState("all");

  const allMessages = mockMessages.filter(m => m.status === "sent");
  const emails = allMessages.filter(m => m.type === "email");
  const sms = allMessages.filter(m => m.type === "sms");
  const drafts = mockMessages.filter(m => m.status === "draft");

  const displayMessages = selectedType === "all" ? allMessages : selectedType === "email" ? emails : sms;
  const filtered = displayMessages.filter(m =>
    m.to.toLowerCase().includes(search.toLowerCase()) ||
    (m.subject || "").toLowerCase().includes(search.toLowerCase()) ||
    m.body.toLowerCase().includes(search.toLowerCase()) ||
    m.from.toLowerCase().includes(search.toLowerCase())
  );

  return (
    <div className="space-y-6">
      <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
          <h1 className="text-2xl font-bold tracking-tight">Communication</h1>
          <p className="text-sm text-muted-foreground">Email and SMS messaging center</p>
        </div>
        <Button asChild>
          <Link href="/communication/compose"><PenSquare className="mr-2 h-4 w-4" />Compose</Link>
        </Button>
      </div>

      <div className="grid gap-4 sm:grid-cols-4">
        <Card className="cursor-pointer transition-shadow hover:shadow-md">
          <CardContent className="p-4 flex items-center gap-3">
            <div className="flex h-10 w-10 items-center justify-center rounded-xl bg-blue-100 text-blue-600 dark:bg-blue-900/30 dark:text-blue-400">
              <Inbox className="h-5 w-5" />
            </div>
            <div><p className="text-lg font-bold">{allMessages.length}</p><p className="text-xs text-muted-foreground">Total Messages</p></div>
          </CardContent>
        </Card>
        <Card className="cursor-pointer transition-shadow hover:shadow-md">
          <CardContent className="p-4 flex items-center gap-3">
            <div className="flex h-10 w-10 items-center justify-center rounded-xl bg-purple-100 text-purple-600 dark:bg-purple-900/30 dark:text-purple-400">
              <Mail className="h-5 w-5" />
            </div>
            <div><p className="text-lg font-bold">{emails.length}</p><p className="text-xs text-muted-foreground">Emails</p></div>
          </CardContent>
        </Card>
        <Card className="cursor-pointer transition-shadow hover:shadow-md">
          <CardContent className="p-4 flex items-center gap-3">
            <div className="flex h-10 w-10 items-center justify-center rounded-xl bg-emerald-100 text-emerald-600 dark:bg-emerald-900/30 dark:text-emerald-400">
              <MessageCircle className="h-5 w-5" />
            </div>
            <div><p className="text-lg font-bold">{sms.length}</p><p className="text-xs text-muted-foreground">SMS</p></div>
          </CardContent>
        </Card>
        <Card className="cursor-pointer transition-shadow hover:shadow-md">
          <CardContent className="p-4 flex items-center gap-3">
            <div className="flex h-10 w-10 items-center justify-center rounded-xl bg-amber-100 text-amber-600 dark:bg-amber-900/30 dark:text-amber-400">
              <FileText className="h-5 w-5" />
            </div>
            <div><p className="text-lg font-bold">{drafts.length}</p><p className="text-xs text-muted-foreground">Drafts</p></div>
          </CardContent>
        </Card>
      </div>

      <Card>
        <CardContent className="p-4">
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
        </CardContent>
      </Card>

      <div className="space-y-2">
        {filtered.length === 0 ? (
          <Card><CardContent className="flex flex-col items-center justify-center py-16 text-muted-foreground"><Inbox className="mb-3 h-10 w-10" /><p className="text-sm">No messages found</p></CardContent></Card>
        ) : (
          filtered.map((msg) => (
            <Card key={msg.id} className={cn("transition-shadow hover:shadow-md cursor-pointer", !msg.read && "border-l-4 border-l-primary")}>
              <CardContent className="p-4">
                <div className="flex items-start justify-between gap-4">
                  <div className="flex items-start gap-3 min-w-0">
                    <div className={cn("mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-xs", msg.type === "email" ? "bg-purple-100 text-purple-600 dark:bg-purple-900/30 dark:text-purple-400" : "bg-emerald-100 text-emerald-600 dark:bg-emerald-900/30 dark:text-emerald-400")}>
                      {msg.type === "email" ? <Mail className="h-4 w-4" /> : <MessageCircle className="h-4 w-4" />}
                    </div>
                    <div className="min-w-0">
                      <div className="flex items-center gap-2">
                        <p className={cn("text-sm truncate", !msg.read && "font-semibold")}>{msg.subject || "SMS Message"}</p>
                        {msg.isGroup && <Badge variant="outline" className="text-[10px] shrink-0">Group</Badge>}
                      </div>
                      <p className="text-xs text-muted-foreground">From: {msg.from} → To: {msg.to}</p>
                      <p className="mt-1 text-xs text-muted-foreground line-clamp-1">{msg.body}</p>
                    </div>
                  </div>
                  <div className="shrink-0 text-right">
                    <p className="text-xs text-muted-foreground">{formatDistanceToNow(new Date(msg.date), { addSuffix: true })}</p>
                    <Badge variant="outline" className="mt-1 text-[10px] uppercase">{msg.type}</Badge>
                  </div>
                </div>
              </CardContent>
            </Card>
          ))
        )}
      </div>
    </div>
  );
}
