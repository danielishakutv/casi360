"use client";

import * as React from "react";
import { Search, Plus, StickyNote, Pin, PinOff, Trash2, Edit, Clock } from "lucide-react";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Textarea } from "@/components/ui/textarea";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle, DialogTrigger, DialogFooter } from "@/components/ui/dialog";
import { mockNotes } from "@/mock/notes";
import { Note } from "@/types";
import { toast } from "sonner";
import { formatDistanceToNow } from "date-fns";

const categoryColors: Record<string, string> = {
  general: "bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300",
  meeting: "bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400",
  policy: "bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-400",
  reminder: "bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400",
  personal: "bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400",
};

const NoteCard = React.memo(function NoteCard({
  note,
  onTogglePin,
  onEdit,
  onDelete,
}: {
  note: Note;
  onTogglePin: (id: string) => void;
  onEdit: (note: Note) => void;
  onDelete: (id: string) => void;
}) {
  return (
    <Card className="group transition-shadow hover:shadow-md">
      <CardHeader className="pb-3">
        <div className="flex items-start justify-between gap-2">
          <div className="flex-1 min-w-0">
            <CardTitle className="text-sm font-semibold line-clamp-1">{note.title}</CardTitle>
            <CardDescription className="mt-1 flex items-center gap-2 text-xs">
              <span>{note.author}</span>
              <span>&middot;</span>
              <Clock className="h-3 w-3" />
              <span>{formatDistanceToNow(new Date(note.updatedAt), { addSuffix: true })}</span>
            </CardDescription>
          </div>
          <div className="flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
            <Button variant="ghost" size="icon" className="h-7 w-7" onClick={() => onTogglePin(note.id)}>
              {note.isPinned ? <PinOff className="h-3.5 w-3.5" /> : <Pin className="h-3.5 w-3.5" />}
            </Button>
            <Button variant="ghost" size="icon" className="h-7 w-7" onClick={() => onEdit(note)}>
              <Edit className="h-3.5 w-3.5" />
            </Button>
            <Button variant="ghost" size="icon" className="h-7 w-7 text-destructive" onClick={() => onDelete(note.id)}>
              <Trash2 className="h-3.5 w-3.5" />
            </Button>
          </div>
        </div>
      </CardHeader>
      <CardContent className="pb-4">
        <p className="text-sm text-muted-foreground line-clamp-3">{note.content}</p>
        <div className="mt-3 flex items-center gap-2">
          <Badge className={`text-xs ${categoryColors[note.category]}`} variant="secondary">{note.category}</Badge>
          {note.isPinned && (
            <Badge variant="outline" className="text-xs">
              <Pin className="mr-1 h-3 w-3" />Pinned
            </Badge>
          )}
        </div>
      </CardContent>
    </Card>
  );
});

export default function NotesPage() {
  const [notes, setNotes] = React.useState<Note[]>(mockNotes);
  const [search, setSearch] = React.useState("");
  const [categoryFilter, setCategoryFilter] = React.useState("all");
  const [dialogOpen, setDialogOpen] = React.useState(false);

  const filtered = notes.filter((note) => {
    const matchSearch =
      note.title.toLowerCase().includes(search.toLowerCase()) ||
      note.content.toLowerCase().includes(search.toLowerCase()) ||
      note.author.toLowerCase().includes(search.toLowerCase());
    const matchCategory = categoryFilter === "all" || note.category === categoryFilter;
    return matchSearch && matchCategory;
  });

  const pinnedNotes = filtered.filter((n) => n.isPinned);
  const unpinnedNotes = filtered.filter((n) => !n.isPinned);

  const togglePin = (id: string) => {
    setNotes((prev) =>
      prev.map((n) =>
        n.id === id ? { ...n, isPinned: !n.isPinned, updatedAt: new Date().toISOString() } : n
      )
    );
  };

  const deleteNote = (id: string) => {
    setNotes((prev) => prev.filter((n) => n.id !== id));
    toast.success("Note deleted");
  };

  const handleAddNote = (e: React.FormEvent<HTMLFormElement>) => {
    e.preventDefault();
    const formData = new FormData(e.currentTarget);
    const now = new Date().toISOString();
    const newNote: Note = {
      id: `note-${String(notes.length + 1).padStart(3, "0")}`,
      title: formData.get("title") as string,
      content: formData.get("content") as string,
      category: formData.get("category") as Note["category"],
      author: "You",
      isPinned: false,
      createdAt: now,
      updatedAt: now,
    };
    setNotes([newNote, ...notes]);
    setDialogOpen(false);
    toast.success("Note created", { description: newNote.title });
  };

  const handleEdit = React.useCallback((note: Note) => {
    toast.info("Edit note", { description: note.title });
  }, []);

  return (
    <div className="space-y-6">
      <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
          <h1 className="text-2xl font-bold tracking-tight">Notes</h1>
          <p className="text-sm text-muted-foreground">HR notes, memos, and reminders</p>
        </div>
        <Dialog open={dialogOpen} onOpenChange={setDialogOpen}>
          <DialogTrigger asChild>
            <Button><Plus className="mr-2 h-4 w-4" />New Note</Button>
          </DialogTrigger>
          <DialogContent className="sm:max-w-lg">
            <DialogHeader>
              <DialogTitle>Create Note</DialogTitle>
              <DialogDescription>Add a new HR note or memo.</DialogDescription>
            </DialogHeader>
            <form onSubmit={handleAddNote} className="space-y-4">
              <div className="space-y-2">
                <Label htmlFor="title">Title</Label>
                <Input id="title" name="title" placeholder="Note title..." required />
              </div>
              <div className="space-y-2">
                <Label htmlFor="category">Category</Label>
                <select name="category" id="category" className="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm" required>
                  <option value="general">General</option>
                  <option value="meeting">Meeting</option>
                  <option value="policy">Policy</option>
                  <option value="reminder">Reminder</option>
                  <option value="personal">Personal</option>
                </select>
              </div>
              <div className="space-y-2">
                <Label htmlFor="content">Content</Label>
                <Textarea id="content" name="content" placeholder="Write your note here..." rows={5} required />
              </div>
              <DialogFooter>
                <Button type="button" variant="outline" onClick={() => setDialogOpen(false)}>Cancel</Button>
                <Button type="submit">Create Note</Button>
              </DialogFooter>
            </form>
          </DialogContent>
        </Dialog>
      </div>

      {/* Filters */}
      <Card>
        <CardContent className="p-4">
          <div className="flex flex-col gap-4 sm:flex-row sm:items-center">
            <div className="relative flex-1">
              <Search className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
              <Input placeholder="Search notes..." value={search} onChange={(e) => setSearch(e.target.value)} className="pl-9" />
            </div>
            <Select value={categoryFilter} onValueChange={setCategoryFilter}>
              <SelectTrigger className="w-[160px]"><SelectValue placeholder="Category" /></SelectTrigger>
              <SelectContent>
                <SelectItem value="all">All Categories</SelectItem>
                <SelectItem value="general">General</SelectItem>
                <SelectItem value="meeting">Meeting</SelectItem>
                <SelectItem value="policy">Policy</SelectItem>
                <SelectItem value="reminder">Reminder</SelectItem>
                <SelectItem value="personal">Personal</SelectItem>
              </SelectContent>
            </Select>
          </div>
        </CardContent>
      </Card>

      {/* Summary */}
      <div className="flex items-center gap-4 text-sm text-muted-foreground">
        <span>{filtered.length} notes</span>
        <span>·</span>
        <span>{pinnedNotes.length} pinned</span>
      </div>

      {/* Pinned Notes */}
      {pinnedNotes.length > 0 && (
        <div className="space-y-3">
          <h2 className="text-sm font-semibold flex items-center gap-2"><Pin className="h-4 w-4" />Pinned</h2>
          <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            {pinnedNotes.map((note) => (<NoteCard key={note.id} note={note} onTogglePin={togglePin} onEdit={handleEdit} onDelete={deleteNote} />))}
          </div>
        </div>
      )}

      {/* All Notes */}
      <div className="space-y-3">
        {pinnedNotes.length > 0 && <h2 className="text-sm font-semibold">All Notes</h2>}
        {unpinnedNotes.length === 0 && pinnedNotes.length === 0 ? (
          <Card>
            <CardContent className="flex flex-col items-center justify-center py-12 text-center">
              <StickyNote className="h-12 w-12 text-muted-foreground/40 mb-4" />
              <p className="text-muted-foreground">No notes found</p>
              <p className="text-xs text-muted-foreground mt-1">Create your first note to get started.</p>
            </CardContent>
          </Card>
        ) : (
          <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            {unpinnedNotes.map((note) => (<NoteCard key={note.id} note={note} onTogglePin={togglePin} onEdit={handleEdit} onDelete={deleteNote} />))}
          </div>
        )}
      </div>
    </div>
  );
}
