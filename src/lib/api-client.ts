/**
 * CASI360 API Client
 * 
 * Centralized HTTP client for communicating with the Laravel backend.
 * Uses cookie-based authentication via Sanctum (no token in localStorage).
 * 
 * USAGE:
 * - Import { apiClient } from "@/lib/api-client"
 * - apiClient.post("/auth/login", { email, password })
 * - apiClient.get("/auth/session")
 * 
 * All requests include credentials (cookies) automatically.
 * CSRF token is fetched before mutating requests.
 */

// Toggle this to switch between mock and real API
export const USE_REAL_API = true;

const API_BASE_URL = process.env.NEXT_PUBLIC_API_URL || "https://api.casi360.com/api/v1";
const SANCTUM_CSRF_URL = process.env.NEXT_PUBLIC_API_URL?.replace("/api/v1", "") || "https://api.casi360.com";

interface ApiResponse<T = unknown> {
  success: boolean;
  message: string;
  data?: T;
  errors?: Record<string, string[]>;
}

interface RequestOptions {
  headers?: Record<string, string>;
  /** Abort signal for request cancellation / timeout */
  signal?: AbortSignal;
}

class ApiClient {
  private baseUrl: string;
  private csrfInitialized = false;

  constructor(baseUrl: string) {
    this.baseUrl = baseUrl;
  }

  /**
   * Fetch CSRF cookie from Sanctum before mutating requests.
   * Required for POST/PATCH/PUT/DELETE when using cookie-based auth.
   */
  async initCsrf(): Promise<void> {
    if (this.csrfInitialized) return;

    await fetch(`${SANCTUM_CSRF_URL}/sanctum/csrf-cookie`, {
      method: "GET",
      credentials: "include",
    });

    this.csrfInitialized = true;
  }

  /**
   * Get XSRF token from cookies.
   */
  private getXsrfToken(): string | undefined {
    if (typeof document === "undefined") return undefined;
    const match = document.cookie.match(/XSRF-TOKEN=([^;]+)/);
    return match ? decodeURIComponent(match[1]) : undefined;
  }

  /**
   * Core request method.
   */
  private async request<T>(
    method: string,
    endpoint: string,
    body?: unknown,
    options?: RequestOptions
  ): Promise<ApiResponse<T>> {
    // Ensure CSRF for mutating requests
    if (["POST", "PATCH", "PUT", "DELETE"].includes(method)) {
      await this.initCsrf();
    }

    const headers: Record<string, string> = {
      "Content-Type": "application/json",
      Accept: "application/json",
      ...options?.headers,
    };

    const xsrfToken = this.getXsrfToken();
    if (xsrfToken) {
      headers["X-XSRF-TOKEN"] = xsrfToken;
    }

    const response = await fetch(`${this.baseUrl}${endpoint}`, {
      method,
      headers,
      body: body ? JSON.stringify(body) : undefined,
      credentials: "include", // Send cookies with every request
      signal: options?.signal,
    });

    // Handle non-JSON responses (e.g., 500 errors)
    const contentType = response.headers.get("content-type");
    if (!contentType || !contentType.includes("application/json")) {
      if (!response.ok) {
        return {
          success: false,
          message: `Server error (${response.status})`,
        };
      }
      return { success: true, message: "OK" };
    }

    const data: ApiResponse<T> = await response.json();

    // Handle 401 - redirect to login
    if (response.status === 401) {
      // Reset CSRF so it re-fetches on next request
      this.csrfInitialized = false;
    }

    return data;
  }

  // HTTP method shortcuts
  async get<T>(endpoint: string, options?: RequestOptions): Promise<ApiResponse<T>> {
    return this.request<T>("GET", endpoint, undefined, options);
  }

  async post<T>(endpoint: string, body?: unknown, options?: RequestOptions): Promise<ApiResponse<T>> {
    return this.request<T>("POST", endpoint, body, options);
  }

  async patch<T>(endpoint: string, body?: unknown, options?: RequestOptions): Promise<ApiResponse<T>> {
    return this.request<T>("PATCH", endpoint, body, options);
  }

  async put<T>(endpoint: string, body?: unknown, options?: RequestOptions): Promise<ApiResponse<T>> {
    return this.request<T>("PUT", endpoint, body, options);
  }

  async delete<T>(endpoint: string, options?: RequestOptions): Promise<ApiResponse<T>> {
    return this.request<T>("DELETE", endpoint, undefined, options);
  }
}

export const apiClient = new ApiClient(API_BASE_URL);
