import { FAQ } from "@/types";

export const mockFAQs: FAQ[] = [
  { id: "faq-001", question: "How do I submit a leave request?", answer: "Navigate to HR > Leave Requests and click 'New Request'. Fill in the required details including leave type, dates, and reason, then submit for approval.", category: "HR" },
  { id: "faq-002", question: "How do I reset my password?", answer: "Go to Profile > Change Password. Enter your current password and set a new one. Passwords must be at least 8 characters with one uppercase letter and one number.", category: "Account" },
  { id: "faq-003", question: "How do I send a group email?", answer: "Go to Communication > Compose. Toggle the 'Group Email' option, select the recipients or group, compose your message, and click Send.", category: "Communication" },
  { id: "faq-004", question: "How do I view my team's leave calendar?", answer: "Navigate to HR > Leave Requests and switch to the Calendar view. You can filter by department to see your team's schedule.", category: "HR" },
  { id: "faq-005", question: "How do I update my profile information?", answer: "Go to Profile page and click Edit. You can update your name, phone number, and other personal details. Some fields may require admin approval.", category: "Account" },
  { id: "faq-006", question: "How do I add a new employee?", answer: "Navigate to HR > Employees and click 'Add Employee'. Fill in the required information including personal details, department, and role assignment.", category: "HR" },
  { id: "faq-007", question: "How do I approve pending requests?", answer: "Check the Dashboard for pending approvals or navigate to the relevant module. Click on the request to review details and use the Approve/Reject buttons.", category: "Approvals" },
  { id: "faq-008", question: "How do I enable/disable modules?", answer: "Go to Settings > Feature Toggles. You can enable or disable modules using the toggle switches. Changes take effect immediately.", category: "Settings" },
  { id: "faq-009", question: "How do I export data?", answer: "Most data tables include an Export button in the toolbar. Click it to download data in CSV or PDF format.", category: "Data" },
  { id: "faq-010", question: "How do I contact support?", answer: "Visit the Help section and use the contact form, or email support@casi.org. Our team responds within 24 hours during business days.", category: "Support" },
  { id: "faq-011", question: "What are the system requirements?", answer: "CASI360 works on modern browsers including Chrome, Firefox, Safari, and Edge. A stable internet connection is recommended.", category: "Technical" },
  { id: "faq-012", question: "How do I manage user roles?", answer: "Go to Settings > User Management. Select a user and click Edit Role to assign or change roles. Only admins and super admins can manage roles.", category: "Settings" },
];
