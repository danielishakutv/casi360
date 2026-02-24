import { create } from "zustand";
import { persist } from "zustand/middleware";
import { User, UserRole } from "@/types";
import { mockUsers } from "@/mock/users";

interface AuthState {
  user: User | null;
  isAuthenticated: boolean;
  isLoading: boolean;
  login: (email: string, password: string) => Promise<boolean>;
  logout: () => void;
  switchRole: (role: UserRole) => void;
}

export const useAuthStore = create<AuthState>()(
  persist(
    (set) => ({
      user: null,
      isAuthenticated: false,
      isLoading: false,
      login: async (email: string, _password: string) => {
        set({ isLoading: true });
        // Simulate API delay
        await new Promise((resolve) => setTimeout(resolve, 1000));
        const user = mockUsers.find((u) => u.email === email);
        if (user) {
          set({ user, isAuthenticated: true, isLoading: false });
          return true;
        }
        set({ isLoading: false });
        return false;
      },
      logout: () => {
        set({ user: null, isAuthenticated: false });
      },
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
