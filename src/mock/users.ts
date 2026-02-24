import { User } from "@/types";

export const mockUsers: User[] = [
  {
    id: "usr-001",
    name: "Daniel Okonkwo",
    email: "daniel@casi.org",
    role: "super_admin",
    department: "Administration",
    phone: "+234 801 234 5678",
    status: "active",
    avatar: "",
    createdAt: "2024-01-15",
  },
  {
    id: "usr-002",
    name: "Grace Adeyemi",
    email: "grace@casi.org",
    role: "admin",
    department: "Operations",
    phone: "+234 802 345 6789",
    status: "active",
    avatar: "",
    createdAt: "2024-02-20",
  },
  {
    id: "usr-003",
    name: "Samuel Eze",
    email: "samuel@casi.org",
    role: "manager",
    department: "Programs",
    phone: "+234 803 456 7890",
    status: "active",
    avatar: "",
    createdAt: "2024-03-10",
  },
  {
    id: "usr-004",
    name: "Amina Bello",
    email: "amina@casi.org",
    role: "staff",
    department: "HR",
    phone: "+234 804 567 8901",
    status: "active",
    avatar: "",
    createdAt: "2024-04-05",
  },
  {
    id: "usr-005",
    name: "Chidi Nnamdi",
    email: "chidi@casi.org",
    role: "staff",
    department: "Finance",
    phone: "+234 805 678 9012",
    status: "inactive",
    avatar: "",
    createdAt: "2024-05-12",
  },
];

export const defaultCredentials = {
  email: "daniel@casi.org",
  password: "demo123",
};
