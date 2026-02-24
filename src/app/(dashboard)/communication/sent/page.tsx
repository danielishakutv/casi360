"use client";

import { Send, Mail, MessageCircle } from "lucide-react";
import { Card, CardContent } from "@/components/ui/card";
import { Badge } from "@/components/ui/badge";
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/components/ui/table";
import { mockMessages } from "@/mock/messages";
import { formatDistanceToNow } from "date-fns";

export default function SentPage() {
  const sentMessages = mockMessages.filter((m) => m.status === "sent");

  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-2xl font-bold tracking-tight">Sent Messages</h1>
        <p className="text-sm text-muted-foreground">{sentMessages.length} sent messages</p>
      </div>

      <Card>
        <CardContent className="p-0">
          <Table>
            <TableHeader>
              <TableRow>
                <TableHead>Type</TableHead>
                <TableHead>To</TableHead>
                <TableHead className="hidden md:table-cell">Subject / Message</TableHead>
                <TableHead className="hidden sm:table-cell">Date</TableHead>
                <TableHead>Status</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              {sentMessages.map((msg) => (
                <TableRow key={msg.id}>
                  <TableCell>
                    <div className="flex items-center gap-2">
                      {msg.type === "email" ? <Mail className="h-4 w-4 text-purple-500" /> : <MessageCircle className="h-4 w-4 text-emerald-500" />}
                      <span className="text-xs uppercase">{msg.type}</span>
                    </div>
                  </TableCell>
                  <TableCell>
                    <div>
                      <p className="text-sm font-medium truncate max-w-[200px]">{msg.to}</p>
                      {msg.isGroup && <Badge variant="outline" className="text-[10px] mt-0.5">Group</Badge>}
                    </div>
                  </TableCell>
                  <TableCell className="hidden md:table-cell">
                    <p className="text-sm truncate max-w-[300px]">{msg.subject || msg.body}</p>
                  </TableCell>
                  <TableCell className="hidden sm:table-cell text-sm text-muted-foreground">{formatDistanceToNow(new Date(msg.date), { addSuffix: true })}</TableCell>
                  <TableCell><Badge className="text-xs capitalize bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400 hover:bg-green-100">Sent</Badge></TableCell>
                </TableRow>
              ))}
            </TableBody>
          </Table>
        </CardContent>
      </Card>
    </div>
  );
}
