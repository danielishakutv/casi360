"use client";

import * as React from "react";
import { useTheme } from "next-themes";
import {
  Moon,
  Sun,
  Monitor,
  Shield,
  Users,
  ToggleLeft,
  Database,
  RefreshCcw,
  Trash2,
} from "lucide-react";
import {
  Card,
  CardContent,
  CardDescription,
  CardHeader,
  CardTitle,
} from "@/components/ui/card";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Label } from "@/components/ui/label";
import { Switch } from "@/components/ui/switch";
import { Separator } from "@/components/ui/separator";
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select";
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from "@/components/ui/table";
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
  DialogTrigger,
} from "@/components/ui/dialog";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs";
import { useModuleStore } from "@/store/module-store";
import { useAuthStore } from "@/store/auth-store";
import { moduleRegistry } from "@/lib/module-registry";
import { User, UserRole } from "@/types";
import { toast } from "sonner";

export default function SettingsPage() {
  const { theme, setTheme } = useTheme();
  const { enabledModules, toggleModule } = useModuleStore();
  const { user, switchRole, fetchUsers, updateUserRole, updateUserStatus, deleteUser } = useAuthStore();
  const [mounted, setMounted] = React.useState(false);
  const [users, setUsers] = React.useState<User[]>([]);
  const [loadingUsers, setLoadingUsers] = React.useState(false);

  React.useEffect(() => {
    setMounted(true);
  }, []);

  // Fetch users from API when users tab is opened (or on mount)
  const loadUsers = React.useCallback(async () => {
    setLoadingUsers(true);
    try {
      const { users: fetched } = await fetchUsers();
      setUsers(fetched);
    } catch {
      toast.error("Failed to load users");
    } finally {
      setLoadingUsers(false);
    }
  }, [fetchUsers]);

  React.useEffect(() => {
    loadUsers();
  }, [loadUsers]);

  const handleRoleChange = async (userId: string, role: UserRole) => {
    const result = await updateUserRole(userId, role);
    if (result.success) {
      setUsers((prev) =>
        prev.map((u) => (u.id === userId ? { ...u, role } : u))
      );
      if (user && user.id === userId) {
        switchRole(role);
      }
      toast.success("Role updated", {
        description: `User role has been changed to ${role.replace("_", " ")}.`,
      });
    } else {
      toast.error(result.error || "Failed to update role");
    }
  };

  const handleStatusToggle = async (userId: string, currentStatus: string) => {
    const newStatus = currentStatus === "active" ? "inactive" : "active";
    const result = await updateUserStatus(userId, newStatus);
    if (result.success) {
      setUsers((prev) =>
        prev.map((u) => (u.id === userId ? { ...u, status: newStatus as "active" | "inactive" } : u))
      );
      toast.success("Status updated", {
        description: `User status changed to ${newStatus}.`,
      });
    } else {
      toast.error(result.error || "Failed to update status");
    }
  };

  const handleDeleteUser = async (userId: string) => {
    const result = await deleteUser(userId);
    if (result.success) {
      setUsers((prev) => prev.filter((u) => u.id !== userId));
      toast.success("User deleted");
    } else {
      toast.error(result.error || "Failed to delete user");
    }
  };

  const handleResetData = () => {
    toast.success("Data reset", {
      description: "All mock data has been reset to defaults.",
    });
  };

  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-2xl font-bold tracking-tight">Settings</h1>
        <p className="text-sm text-muted-foreground">
          Manage system preferences and configurations
        </p>
      </div>

      <Tabs defaultValue="appearance" className="space-y-6">
        <TabsList className="grid w-full grid-cols-4 lg:w-[500px]">
          <TabsTrigger value="appearance">Appearance</TabsTrigger>
          <TabsTrigger value="modules">Modules</TabsTrigger>
          <TabsTrigger value="users">Users</TabsTrigger>
          <TabsTrigger value="data">Data</TabsTrigger>
        </TabsList>

        {/* Appearance Tab */}
        <TabsContent value="appearance" className="space-y-6">
          <Card>
            <CardHeader>
              <CardTitle className="text-base">Theme</CardTitle>
              <CardDescription>
                Select your preferred color scheme
              </CardDescription>
            </CardHeader>
            <CardContent>
              {mounted && (
                <div className="grid gap-4 sm:grid-cols-3">
                  {[
                    {
                      value: "light",
                      label: "Light",
                      icon: Sun,
                      desc: "Clean bright interface",
                    },
                    {
                      value: "dark",
                      label: "Dark",
                      icon: Moon,
                      desc: "Easy on the eyes",
                    },
                    {
                      value: "system",
                      label: "System",
                      icon: Monitor,
                      desc: "Follow OS settings",
                    },
                  ].map((t) => (
                    <button
                      key={t.value}
                      onClick={() => setTheme(t.value)}
                      className={`flex flex-col items-center gap-3 rounded-xl border-2 p-6 transition-all hover:border-primary/50 ${
                        theme === t.value
                          ? "border-primary bg-accent"
                          : "border-transparent bg-card"
                      }`}
                    >
                      <t.icon className="h-8 w-8" />
                      <div className="text-center">
                        <p className="text-sm font-medium">{t.label}</p>
                        <p className="text-xs text-muted-foreground">
                          {t.desc}
                        </p>
                      </div>
                    </button>
                  ))}
                </div>
              )}
            </CardContent>
          </Card>
        </TabsContent>

        {/* Modules Tab */}
        <TabsContent value="modules" className="space-y-6">
          <Card>
            <CardHeader>
              <CardTitle className="flex items-center gap-2 text-base">
                <ToggleLeft className="h-5 w-5" />
                Feature Toggles
              </CardTitle>
              <CardDescription>
                Enable or disable system modules
              </CardDescription>
            </CardHeader>
            <CardContent>
              <div className="space-y-4">
                {moduleRegistry.map((mod) => (
                  <div
                    key={mod.id}
                    className="flex items-center justify-between rounded-lg border p-4"
                  >
                    <div className="flex items-center gap-3">
                      <div>
                        <p className="text-sm font-medium">{mod.name}</p>
                        <p className="text-xs text-muted-foreground">
                          {mod.description}
                        </p>
                      </div>
                    </div>
                    <Switch
                      checked={enabledModules[mod.id] !== false}
                      onCheckedChange={() => {
                        if (mod.id === "dashboard") {
                          toast.error("Cannot disable dashboard");
                          return;
                        }
                        toggleModule(mod.id);
                        toast.success(
                          `${mod.name} ${
                            enabledModules[mod.id] !== false
                              ? "disabled"
                              : "enabled"
                          }`
                        );
                      }}
                      disabled={mod.id === "dashboard"}
                    />
                  </div>
                ))}
              </div>
            </CardContent>
          </Card>
        </TabsContent>

        {/* Users Tab */}
        <TabsContent value="users" className="space-y-6">
          <Card>
            <CardHeader>
              <CardTitle className="flex items-center gap-2 text-base">
                <Users className="h-5 w-5" />
                User Management
              </CardTitle>
              <CardDescription>
                Manage user accounts and role assignments
              </CardDescription>
            </CardHeader>
            <CardContent className="p-0">
              <Table>
                <TableHeader>
                  <TableRow>
                    <TableHead>User</TableHead>
                    <TableHead className="hidden sm:table-cell">
                      Department
                    </TableHead>
                    <TableHead>Role</TableHead>
                    <TableHead>Status</TableHead>
                    <TableHead className="text-right">Actions</TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  {loadingUsers ? (
                    <TableRow>
                      <TableCell colSpan={5} className="text-center py-8 text-sm text-muted-foreground">
                        Loading users...
                      </TableCell>
                    </TableRow>
                  ) : users.length === 0 ? (
                    <TableRow>
                      <TableCell colSpan={5} className="text-center py-8 text-sm text-muted-foreground">
                        No users found
                      </TableCell>
                    </TableRow>
                  ) : (
                    users.map((u) => (
                      <TableRow key={u.id}>
                        <TableCell>
                          <div className="flex items-center gap-3">
                            <div className="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-primary text-xs font-bold text-primary-foreground">
                              {u.name
                                .split(" ")
                                .map((n) => n[0])
                                .join("")}
                            </div>
                            <div>
                              <p className="text-sm font-medium">{u.name}</p>
                              <p className="text-xs text-muted-foreground">
                                {u.email}
                              </p>
                            </div>
                          </div>
                        </TableCell>
                        <TableCell className="hidden sm:table-cell text-sm">
                          {u.department}
                        </TableCell>
                        <TableCell>
                          <Select
                            value={u.role}
                            onValueChange={(v: UserRole) =>
                              handleRoleChange(u.id, v)
                            }
                          >
                            <SelectTrigger className="w-[140px] h-8 text-xs">
                              <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                              <SelectItem value="super_admin">
                                Super Admin
                              </SelectItem>
                              <SelectItem value="admin">Admin</SelectItem>
                              <SelectItem value="manager">Manager</SelectItem>
                              <SelectItem value="staff">Staff</SelectItem>
                            </SelectContent>
                          </Select>
                        </TableCell>
                        <TableCell>
                          <Badge
                            variant={
                              u.status === "active" ? "default" : "secondary"
                            }
                            className="text-xs capitalize cursor-pointer"
                            onClick={() => handleStatusToggle(u.id, u.status)}
                          >
                            {u.status}
                          </Badge>
                        </TableCell>
                        <TableCell className="text-right">
                          {u.id !== user?.id && (
                            <Button
                              variant="ghost"
                              size="icon"
                              className="h-8 w-8 text-destructive hover:text-destructive"
                              onClick={() => handleDeleteUser(u.id)}
                            >
                              <Trash2 className="h-4 w-4" />
                            </Button>
                          )}
                        </TableCell>
                      </TableRow>
                    ))
                  )}
                </TableBody>
              </Table>
            </CardContent>
          </Card>

          {/* Role Simulation - Super Admin Only */}
          {user && (user.role === "super_admin" || user.role === "admin") && (
            <Card className="border-dashed">
              <CardHeader>
                <CardTitle className="flex items-center gap-2 text-base">
                  <Shield className="h-5 w-5" />
                  Role Simulation (Demo)
                </CardTitle>
                <CardDescription>
                  Switch your role to test different permission levels
                </CardDescription>
              </CardHeader>
              <CardContent>
                <div className="grid gap-3 sm:grid-cols-4">
                  {(
                    ["super_admin", "admin", "manager", "staff"] as UserRole[]
                  ).map((role) => (
                    <Button
                      key={role}
                      variant={user.role === role ? "default" : "outline"}
                      className="capitalize"
                      onClick={() => {
                        switchRole(role);
                        toast.success(`Switched to ${role.replace("_", " ")}`);
                      }}
                    >
                      {role.replace("_", " ")}
                    </Button>
                  ))}
                </div>
              </CardContent>
            </Card>
          )}
        </TabsContent>

        {/* Data Tab */}
        <TabsContent value="data" className="space-y-6">
          <Card>
            <CardHeader>
              <CardTitle className="flex items-center gap-2 text-base">
                <Database className="h-5 w-5" />
                Data Management
              </CardTitle>
              <CardDescription>
                Manage demo data and system state
              </CardDescription>
            </CardHeader>
            <CardContent className="space-y-4">
              <div className="flex items-center justify-between rounded-lg border p-4">
                <div>
                  <p className="text-sm font-medium">Reset Mock Data</p>
                  <p className="text-xs text-muted-foreground">
                    Reset all data to original demo values
                  </p>
                </div>
                <Dialog>
                  <DialogTrigger asChild>
                    <Button variant="outline" size="sm">
                      <RefreshCcw className="mr-2 h-4 w-4" />
                      Reset
                    </Button>
                  </DialogTrigger>
                  <DialogContent>
                    <DialogHeader>
                      <DialogTitle>Reset All Data?</DialogTitle>
                      <DialogDescription>
                        This will reset all mock data to their original values.
                        Any changes you've made will be lost.
                      </DialogDescription>
                    </DialogHeader>
                    <DialogFooter>
                      <Button variant="outline">Cancel</Button>
                      <Button onClick={handleResetData}>Reset Data</Button>
                    </DialogFooter>
                  </DialogContent>
                </Dialog>
              </div>

              <div className="flex items-center justify-between rounded-lg border p-4">
                <div>
                  <p className="text-sm font-medium">Clear Local Storage</p>
                  <p className="text-xs text-muted-foreground">
                    Remove all persisted state from browser
                  </p>
                </div>
                <Button
                  variant="outline"
                  size="sm"
                  className="text-destructive"
                  onClick={() => {
                    localStorage.clear();
                    toast.success("Local storage cleared", {
                      description: "Please refresh the page.",
                    });
                  }}
                >
                  <Trash2 className="mr-2 h-4 w-4" />
                  Clear
                </Button>
              </div>

              <Separator />

              <div className="rounded-lg bg-muted/50 p-4">
                <h4 className="text-sm font-medium mb-3">Demo Data Summary</h4>
                <div className="grid gap-2 sm:grid-cols-3">
                  {[
                    { label: "Employees", count: 25 },
                    { label: "Departments", count: 10 },
                    { label: "Users", count: 5 },
                    { label: "Messages", count: 20 },
                    { label: "Notifications", count: 50 },
                    { label: "Leave Requests", count: 10 },
                  ].map((item) => (
                    <div
                      key={item.label}
                      className="flex items-center justify-between rounded-md bg-background p-3"
                    >
                      <span className="text-xs text-muted-foreground">
                        {item.label}
                      </span>
                      <span className="text-sm font-bold">{item.count}</span>
                    </div>
                  ))}
                </div>
              </div>
            </CardContent>
          </Card>
        </TabsContent>
      </Tabs>
    </div>
  );
}
