"use client";

import * as React from "react";
import { Search, Plus, Award, Users, Building2, MoreHorizontal } from "lucide-react";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/components/ui/table";
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle, DialogTrigger, DialogFooter } from "@/components/ui/dialog";
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from "@/components/ui/dropdown-menu";
import { Textarea } from "@/components/ui/textarea";
import { mockDesignations } from "@/mock/designations";
import { mockDepartments } from "@/mock/departments";
import { Designation } from "@/types";
import { toast } from "sonner";

const levelColors: Record<string, string> = {
  junior: "bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300",
  mid: "bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400",
  senior: "bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-400",
  lead: "bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400",
  executive: "bg-rose-100 text-rose-700 dark:bg-rose-900/30 dark:text-rose-400",
};

export default function DesignationsPage() {
  const [designations, setDesignations] = React.useState<Designation[]>(mockDesignations);
  const [search, setSearch] = React.useState("");
  const [levelFilter, setLevelFilter] = React.useState("all");
  const [deptFilter, setDeptFilter] = React.useState("all");
  const [dialogOpen, setDialogOpen] = React.useState(false);

  const filtered = designations.filter((d) => {
    const matchSearch = d.title.toLowerCase().includes(search.toLowerCase()) || d.department.toLowerCase().includes(search.toLowerCase());
    const matchLevel = levelFilter === "all" || d.level === levelFilter;
    const matchDept = deptFilter === "all" || d.department === deptFilter;
    return matchSearch && matchLevel && matchDept;
  });

  const totalPositions = designations.reduce((sum, d) => sum + d.employeeCount, 0);
  const departments = [...new Set(designations.map((d) => d.department))];

  const handleAddDesignation = (e: React.FormEvent<HTMLFormElement>) => {
    e.preventDefault();
    const formData = new FormData(e.currentTarget);
    const newDes: Designation = {
      id: `des-${String(designations.length + 1).padStart(3, "0")}`,
      title: formData.get("title") as string,
      department: formData.get("department") as string,
      level: formData.get("level") as Designation["level"],
      employeeCount: 0,
      description: formData.get("description") as string,
      createdAt: new Date().toISOString().split("T")[0],
    };
    setDesignations([newDes, ...designations]);
    setDialogOpen(false);
    toast.success("Designation created", { description: `${newDes.title} has been added.` });
  };

  return (
    <div className="space-y-6">
      <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
          <h1 className="text-2xl font-bold tracking-tight">Designations</h1>
          <p className="text-sm text-muted-foreground">Manage job titles and organizational positions</p>
        </div>
        <Dialog open={dialogOpen} onOpenChange={setDialogOpen}>
          <DialogTrigger asChild>
            <Button><Plus className="mr-2 h-4 w-4" />Add Designation</Button>
          </DialogTrigger>
          <DialogContent className="sm:max-w-md">
            <DialogHeader>
              <DialogTitle>Add New Designation</DialogTitle>
              <DialogDescription>Create a new job title and assign it to a department.</DialogDescription>
            </DialogHeader>
            <form onSubmit={handleAddDesignation} className="space-y-4">
              <div className="grid gap-4 sm:grid-cols-2">
                <div className="space-y-2">
                  <Label htmlFor="title">Title</Label>
                  <Input id="title" name="title" placeholder="e.g. Senior Analyst" required />
                </div>
                <div className="space-y-2">
                  <Label htmlFor="level">Level</Label>
                  <select name="level" id="level" className="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm" required>
                    <option value="junior">Junior</option>
                    <option value="mid">Mid</option>
                    <option value="senior">Senior</option>
                    <option value="lead">Lead</option>
                    <option value="executive">Executive</option>
                  </select>
                </div>
              </div>
              <div className="space-y-2">
                <Label htmlFor="department">Department</Label>
                <select name="department" id="department" className="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm" required>
                  {mockDepartments.map((d) => (<option key={d.id} value={d.name}>{d.name}</option>))}
                </select>
              </div>
              <div className="space-y-2">
                <Label htmlFor="description">Description</Label>
                <Textarea id="description" name="description" placeholder="Brief description of this role..." rows={3} required />
              </div>
              <DialogFooter>
                <Button type="button" variant="outline" onClick={() => setDialogOpen(false)}>Cancel</Button>
                <Button type="submit">Create Designation</Button>
              </DialogFooter>
            </form>
          </DialogContent>
        </Dialog>
      </div>

      {/* Summary */}
      <div className="grid gap-4 sm:grid-cols-3">
        <Card>
          <CardContent className="p-6">
            <div className="flex items-center gap-3">
              <div className="flex h-10 w-10 items-center justify-center rounded-xl bg-purple-100 text-purple-600 dark:bg-purple-900/30 dark:text-purple-400"><Award className="h-5 w-5" /></div>
              <div>
                <p className="text-2xl font-bold">{designations.length}</p>
                <p className="text-xs text-muted-foreground">Total Designations</p>
              </div>
            </div>
          </CardContent>
        </Card>
        <Card>
          <CardContent className="p-6">
            <div className="flex items-center gap-3">
              <div className="flex h-10 w-10 items-center justify-center rounded-xl bg-blue-100 text-blue-600 dark:bg-blue-900/30 dark:text-blue-400"><Users className="h-5 w-5" /></div>
              <div>
                <p className="text-2xl font-bold">{totalPositions}</p>
                <p className="text-xs text-muted-foreground">Filled Positions</p>
              </div>
            </div>
          </CardContent>
        </Card>
        <Card>
          <CardContent className="p-6">
            <div className="flex items-center gap-3">
              <div className="flex h-10 w-10 items-center justify-center rounded-xl bg-emerald-100 text-emerald-600 dark:bg-emerald-900/30 dark:text-emerald-400"><Building2 className="h-5 w-5" /></div>
              <div>
                <p className="text-2xl font-bold">{departments.length}</p>
                <p className="text-xs text-muted-foreground">Departments</p>
              </div>
            </div>
          </CardContent>
        </Card>
      </div>

      {/* Filters */}
      <Card>
        <CardContent className="p-4">
          <div className="flex flex-col gap-4 sm:flex-row sm:items-center">
            <div className="relative flex-1">
              <Search className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
              <Input placeholder="Search designations..." value={search} onChange={(e) => setSearch(e.target.value)} className="pl-9" />
            </div>
            <Select value={deptFilter} onValueChange={setDeptFilter}>
              <SelectTrigger className="w-[180px]"><SelectValue placeholder="Department" /></SelectTrigger>
              <SelectContent>
                <SelectItem value="all">All Departments</SelectItem>
                {departments.map((d) => (<SelectItem key={d} value={d}>{d}</SelectItem>))}
              </SelectContent>
            </Select>
            <Select value={levelFilter} onValueChange={setLevelFilter}>
              <SelectTrigger className="w-[140px]"><SelectValue placeholder="Level" /></SelectTrigger>
              <SelectContent>
                <SelectItem value="all">All Levels</SelectItem>
                <SelectItem value="junior">Junior</SelectItem>
                <SelectItem value="mid">Mid</SelectItem>
                <SelectItem value="senior">Senior</SelectItem>
                <SelectItem value="lead">Lead</SelectItem>
                <SelectItem value="executive">Executive</SelectItem>
              </SelectContent>
            </Select>
          </div>
        </CardContent>
      </Card>

      {/* Table */}
      <Card>
        <CardContent className="p-0">
          <Table>
            <TableHeader>
              <TableRow>
                <TableHead>Designation</TableHead>
                <TableHead className="hidden md:table-cell">Department</TableHead>
                <TableHead>Level</TableHead>
                <TableHead className="hidden sm:table-cell">Staff Count</TableHead>
                <TableHead className="hidden lg:table-cell">Created</TableHead>
                <TableHead className="w-[50px]"></TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              {filtered.length === 0 ? (
                <TableRow><TableCell colSpan={6} className="h-32 text-center text-muted-foreground">No designations found</TableCell></TableRow>
              ) : (
                filtered.map((des) => (
                  <TableRow key={des.id}>
                    <TableCell>
                      <div>
                        <p className="font-medium text-sm">{des.title}</p>
                        <p className="text-xs text-muted-foreground line-clamp-1">{des.description}</p>
                      </div>
                    </TableCell>
                    <TableCell className="hidden md:table-cell text-sm">{des.department}</TableCell>
                    <TableCell>
                      <Badge className={`capitalize text-xs ${levelColors[des.level]}`} variant="secondary">{des.level}</Badge>
                    </TableCell>
                    <TableCell className="hidden sm:table-cell text-sm">{des.employeeCount}</TableCell>
                    <TableCell className="hidden lg:table-cell text-sm text-muted-foreground">{des.createdAt}</TableCell>
                    <TableCell>
                      <DropdownMenu>
                        <DropdownMenuTrigger asChild><Button variant="ghost" size="icon" className="h-8 w-8"><MoreHorizontal className="h-4 w-4" /></Button></DropdownMenuTrigger>
                        <DropdownMenuContent align="end">
                          <DropdownMenuItem onClick={() => toast.info("View details", { description: des.title })}>View Details</DropdownMenuItem>
                          <DropdownMenuItem onClick={() => toast.info("Edit designation", { description: des.title })}>Edit</DropdownMenuItem>
                          <DropdownMenuItem className="text-destructive" onClick={() => { setDesignations((prev) => prev.filter((d) => d.id !== des.id)); toast.success("Deleted", { description: `${des.title} removed.` }); }}>Delete</DropdownMenuItem>
                        </DropdownMenuContent>
                      </DropdownMenu>
                    </TableCell>
                  </TableRow>
                ))
              )}
            </TableBody>
          </Table>
        </CardContent>
      </Card>
    </div>
  );
}
