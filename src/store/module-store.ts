import { create } from "zustand";
import { persist } from "zustand/middleware";

interface ModuleState {
  enabledModules: Record<string, boolean>;
  toggleModule: (moduleId: string) => void;
  isModuleEnabled: (moduleId: string) => boolean;
}

const defaultModules: Record<string, boolean> = {
  dashboard: true,
  hr: true,
  communication: true,
  settings: true,
  profile: true,
  help: true,
};

export const useModuleStore = create<ModuleState>()(
  persist(
    (set, get) => ({
      enabledModules: defaultModules,
      toggleModule: (moduleId: string) => {
        set((state) => ({
          enabledModules: {
            ...state.enabledModules,
            [moduleId]: !state.enabledModules[moduleId],
          },
        }));
      },
      isModuleEnabled: (moduleId: string) => {
        return get().enabledModules[moduleId] ?? false;
      },
    }),
    {
      name: "casi360-modules",
    }
  )
);
