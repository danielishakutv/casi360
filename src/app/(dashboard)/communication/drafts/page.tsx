"use client";

import Link from "next/link";
import { FileText, PenSquare, Trash2 } from "lucide-react";
import { Card, CardContent } from "@/components/ui/card";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { mockMessages } from "@/mock/messages";
import { formatDistanceToNow } from "date-fns";
import { toast } from "sonner";

export default function DraftsPage() {
  const drafts = mockMessages.filter((m) => m.status === "draft");

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-2xl font-bold tracking-tight">Drafts</h1>
          <p className="text-sm text-muted-foreground">{drafts.length} saved drafts</p>
        </div>
        <Button asChild>
          <Link href="/communication/compose"><PenSquare className="mr-2 h-4 w-4" />New Message</Link>
        </Button>
      </div>

      {drafts.length === 0 ? (
        <Card>
          <CardContent className="flex flex-col items-center justify-center py-16 text-muted-foreground">
            <FileText className="mb-3 h-10 w-10" />
            <p className="text-sm">No drafts</p>
            <p className="text-xs">Your draft messages will appear here</p>
          </CardContent>
        </Card>
      ) : (
        <div className="space-y-3">
          {drafts.map((draft) => (
            <Card key={draft.id} className="transition-shadow hover:shadow-md">
              <CardContent className="p-4">
                <div className="flex items-start justify-between gap-4">
                  <div className="flex items-start gap-3 min-w-0">
                    <div className="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-amber-100 text-amber-600 dark:bg-amber-900/30 dark:text-amber-400">
                      <FileText className="h-4 w-4" />
                    </div>
                    <div className="min-w-0">
                      <p className="text-sm font-medium truncate">{draft.subject || "No subject"}</p>
                      <p className="text-xs text-muted-foreground">To: {draft.to}</p>
                      <p className="mt-1 text-xs text-muted-foreground line-clamp-2">{draft.body}</p>
                    </div>
                  </div>
                  <div className="flex items-center gap-2 shrink-0">
                    <span className="text-xs text-muted-foreground">{formatDistanceToNow(new Date(draft.date), { addSuffix: true })}</span>
                    <Button variant="ghost" size="icon" className="h-8 w-8" onClick={() => toast.info("Edit draft")}>
                      <PenSquare className="h-4 w-4" />
                    </Button>
                    <Button variant="ghost" size="icon" className="h-8 w-8 text-destructive" onClick={() => toast.error("Draft deleted")}>
                      <Trash2 className="h-4 w-4" />
                    </Button>
                  </div>
                </div>
              </CardContent>
            </Card>
          ))}
        </div>
      )}
    </div>
  );
}
