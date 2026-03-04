import { create } from "zustand";
import { persist } from "zustand/middleware";
import { User, UserRole, mapApiUser } from "@/types";
import { mockUsers } from "@/mock/users";
import { apiClient, USE_REAL_API } from "@/lib/api-client";

interface AuthState {
  user: User | null;
  isAuthenticated: boolean;
  isLoading: boolean;
  forcePasswordChange: boolean;

  // Auth actions
  login: (email: string, password: string) => Promise<{ success: boolean; error?: string }>;
  logout: () => Promise<void>;
  checkSession: () => Promise<void>;

  // Profile actions
  updateProfile: (data: { name?: string; phone?: string; department?: string }) => Promise<{ success: boolean; error?: string }>;
  changePassword: (currentPassword: string, newPassword: string, newPasswordConfirmation: string) => Promise<{ success: boolean; error?: string }>;
  deleteAccount: () => Promise<{ success: boolean; error?: string }>;

  // Admin actions
  fetchUsers: (params?: Record<string, string>) => Promise<{ users: User[]; meta?: Record<string, number> }>;
  registerUser: (data: { name: string; email: string; password: string; role?: string; department?: string; phone?: string }) => Promise<{ success: boolean; user?: User; error?: string }>;
  updateUser: (id: string, data: Record<string, string>) => Promise<{ success: boolean; user?: User; error?: string }>;
  updateUserRole: (id: string, role: string) => Promise<{ success: boolean; error?: string }>;
  updateUserStatus: (id: string, status: string) => Promise<{ success: boolean; error?: string }>;
  deleteUser: (id: string) => Promise<{ success: boolean; error?: string }>;

  // Utility
  switchRole: (role: UserRole) => void;
}

export const useAuthStore = create<AuthState>()(
  persist(
    (set) => ({
      user: null,
      isAuthenticated: false,
      isLoading: false,
      forcePasswordChange: false,

      // ─── LOGIN ───────────────────────────────────────────
      login: async (email: string, password: string) => {
        set({ isLoading: true });

        if (!USE_REAL_API) {
          await new Promise((resolve) => setTimeout(resolve, 1000));
          const user = mockUsers.find((u) => u.email === email);
          if (user) {
            set({ user, isAuthenticated: true, isLoading: false });
            return { success: true };
          }
          set({ isLoading: false });
          return { success: false, error: "Invalid email or password" };
        }

        try {
          const res = await apiClient.post<{ user: Record<string, unknown> }>("/auth/login", {
            email,
            password,
          });

          if (res.success && res.data?.user) {
            const user = mapApiUser(res.data.user);
            set({
              user,
              isAuthenticated: true,
              isLoading: false,
              forcePasswordChange: user.forcePasswordChange || false,
            });
            return { success: true };
          }

          set({ isLoading: false });
          const errorMsg = res.errors?.email?.[0] || res.message || "Invalid credentials";
          return { success: false, error: errorMsg };
        } catch {
          set({ isLoading: false });
          return { success: false, error: "Network error. Please try again." };
        }
      },

      // ─── LOGOUT ──────────────────────────────────────────
      logout: async () => {
        if (USE_REAL_API) {
          try {
            await apiClient.post("/auth/logout");
          } catch {
            // Logout locally even if API call fails
          }
        }
        set({ user: null, isAuthenticated: false, forcePasswordChange: false });
      },

      // ─── CHECK SESSION ───────────────────────────────────
      checkSession: async () => {
        if (!USE_REAL_API) return;

        try {
          // Abort if the server doesn't respond within 5 seconds so the UI
          // never hangs waiting for a session check.
          const controller = new AbortController();
          const timeout = setTimeout(() => controller.abort(), 5000);

          const res = await apiClient.get<{ authenticated: boolean; user: Record<string, unknown> }>(
            "/auth/session",
            { signal: controller.signal }
          );
          clearTimeout(timeout);

          if (res.success && res.data?.authenticated) {
            const user = mapApiUser(res.data.user);
            set({
              user,
              isAuthenticated: true,
              forcePasswordChange: user.forcePasswordChange || false,
            });
          } else if (res.success && !res.data?.authenticated) {
            // Only clear auth if the server explicitly said "not authenticated"
            set({ user: null, isAuthenticated: false });
          }
          // On non-success (network/CORS errors), keep existing persisted state
        } catch {
          // Network failure — keep persisted auth state, don't log out
        }
      },

      // ─── UPDATE PROFILE ──────────────────────────────────
      updateProfile: async (data) => {
        if (!USE_REAL_API) {
          await new Promise((resolve) => setTimeout(resolve, 500));
          set((state) => ({
            user: state.user ? { ...state.user, ...data } : null,
          }));
          return { success: true };
        }

        try {
          const res = await apiClient.patch<{ user: Record<string, unknown> }>("/auth/profile", data);
          if (res.success && res.data?.user) {
            const user = mapApiUser(res.data.user);
            set({ user });
            return { success: true };
          }
          return { success: false, error: res.message || "Failed to update profile" };
        } catch {
          return { success: false, error: "Network error" };
        }
      },

      // ─── CHANGE PASSWORD ─────────────────────────────────
      changePassword: async (currentPassword, newPassword, newPasswordConfirmation) => {
        if (!USE_REAL_API) {
          await new Promise((resolve) => setTimeout(resolve, 500));
          return { success: true };
        }

        try {
          const res = await apiClient.post("/auth/change-password", {
            current_password: currentPassword,
            new_password: newPassword,
            new_password_confirmation: newPasswordConfirmation,
          });
          if (res.success) {
            set({ forcePasswordChange: false });
            return { success: true };
          }
          return { success: false, error: res.message || "Failed to change password" };
        } catch {
          return { success: false, error: "Network error" };
        }
      },

      // ─── DELETE ACCOUNT ──────────────────────────────────
      deleteAccount: async () => {
        if (!USE_REAL_API) {
          await new Promise((resolve) => setTimeout(resolve, 500));
          set({ user: null, isAuthenticated: false });
          return { success: true };
        }

        try {
          const res = await apiClient.delete("/auth/account");
          if (res.success) {
            set({ user: null, isAuthenticated: false, forcePasswordChange: false });
            return { success: true };
          }
          return { success: false, error: res.message || "Failed to delete account" };
        } catch {
          return { success: false, error: "Network error" };
        }
      },

      // ─── FETCH USERS (ADMIN) ─────────────────────────────
      fetchUsers: async (params) => {
        if (!USE_REAL_API) {
          return { users: mockUsers };
        }

        try {
          const queryStr = params ? "?" + new URLSearchParams(params).toString() : "";
          const res = await apiClient.get<{
            users: Record<string, unknown>[];
            meta?: Record<string, number>;
          }>(`/auth/users${queryStr}`);

          if (res.success && res.data?.users) {
            const users = res.data.users.map(mapApiUser);
            return { users, meta: res.data.meta };
          }
          return { users: [] };
        } catch {
          return { users: [] };
        }
      },

      // ─── REGISTER USER (ADMIN) ───────────────────────────
      registerUser: async (data) => {
        if (!USE_REAL_API) {
          await new Promise((resolve) => setTimeout(resolve, 500));
          return { success: true };
        }

        try {
          const res = await apiClient.post<{ user: Record<string, unknown> }>("/auth/register", data);
          if (res.success && res.data?.user) {
            return { success: true, user: mapApiUser(res.data.user) };
          }
          return { success: false, error: res.message || "Failed to create user" };
        } catch {
          return { success: false, error: "Network error" };
        }
      },

      // ─── UPDATE USER (ADMIN) ─────────────────────────────
      updateUser: async (id, data) => {
        if (!USE_REAL_API) {
          await new Promise((resolve) => setTimeout(resolve, 500));
          return { success: true };
        }

        try {
          const res = await apiClient.patch<{ user: Record<string, unknown> }>(`/auth/users/${id}`, data);
          if (res.success && res.data?.user) {
            return { success: true, user: mapApiUser(res.data.user) };
          }
          return { success: false, error: res.message || "Failed to update user" };
        } catch {
          return { success: false, error: "Network error" };
        }
      },

      // ─── UPDATE USER ROLE (ADMIN) ────────────────────────
      updateUserRole: async (id, role) => {
        if (!USE_REAL_API) {
          await new Promise((resolve) => setTimeout(resolve, 500));
          return { success: true };
        }

        try {
          const res = await apiClient.patch(`/auth/users/${id}/role`, { role });
          return res.success
            ? { success: true }
            : { success: false, error: res.message || "Failed to update role" };
        } catch {
          return { success: false, error: "Network error" };
        }
      },

      // ─── UPDATE USER STATUS (ADMIN) ──────────────────────
      updateUserStatus: async (id, status) => {
        if (!USE_REAL_API) {
          await new Promise((resolve) => setTimeout(resolve, 500));
          return { success: true };
        }

        try {
          const res = await apiClient.patch(`/auth/users/${id}/status`, { status });
          return res.success
            ? { success: true }
            : { success: false, error: res.message || "Failed to update status" };
        } catch {
          return { success: false, error: "Network error" };
        }
      },

      // ─── DELETE USER (ADMIN) ─────────────────────────────
      deleteUser: async (id) => {
        if (!USE_REAL_API) {
          await new Promise((resolve) => setTimeout(resolve, 500));
          return { success: true };
        }

        try {
          const res = await apiClient.delete(`/auth/users/${id}`);
          return res.success
            ? { success: true }
            : { success: false, error: res.message || "Failed to deactivate user" };
        } catch {
          return { success: false, error: "Network error" };
        }
      },

      // ─── SWITCH ROLE (DEV UTILITY) ───────────────────────
      switchRole: (role: UserRole) => {
        set((state) => {
          if (state.user) {
            return { user: { ...state.user, role } };
          }
          return state;
        });
      },
    }),
    {
      name: "casi360-auth",
    }
  )
);
