"use client";

import React, { createContext, useContext, useState, useEffect, ReactNode } from "react";
import { useRouter } from "next/navigation";
import { Locale } from "./locales";

export interface User {
  id: number;
  name: string;
  email: string;
  avatar_url: string | null;
  plan: "free" | "paid";
  subscription_status: "active" | "inactive";
  language: Locale;
  role?: "user" | "admin";
}

interface AuthContextType {
  user: User | null;
  token: string | null;
  loading: boolean;
  locale: Locale;
  setLocale: (lang: Locale) => Promise<void>;
  login: (token: string, user: User) => void;
  logout: () => Promise<void>;
  refreshUser: () => Promise<void>;
  getGoogleAuthUrl: () => Promise<string>;
}

const AuthContext = createContext<AuthContextType | undefined>(undefined);

export function AuthProvider({ children }: { children: ReactNode }) {
  const [user, setUser] = useState<User | null>(() => {
    if (typeof window !== "undefined") {
      const stored = localStorage.getItem("auth_user");
      try {
        return stored ? JSON.parse(stored) : null;
      } catch {
        return null;
      }
    }
    return null;
  });
  const [token, setToken] = useState<string | null>(() => {
    if (typeof window !== "undefined") {
      return localStorage.getItem("auth_token");
    }
    return null;
  });
  
  const [locale, setLocaleState] = useState<Locale>(() => {
    if (typeof window !== "undefined") {
      const stored = localStorage.getItem("app_locale") as Locale;
      if (stored === "ru" || stored === "en") {
        return stored;
      }
    }
    return "ru"; // Russian is main
  });

  const [loading, setLoading] = useState(() => {
    if (typeof window !== "undefined") {
      return !!localStorage.getItem("auth_token");
    }
    return false;
  });
  const router = useRouter();

  const apiBaseUrl = process.env.NEXT_PUBLIC_API_BASE_URL || "http://localhost:8000/api";

  // Check and keep context synced with user's db language setting if authenticated
  useEffect(() => {
    if (user && user.language && user.language !== locale) {
      Promise.resolve().then(() => {
        setLocaleState(user.language);
        localStorage.setItem("app_locale", user.language);
      });
    }
  }, [user, locale]);

  useEffect(() => {
    const storedToken = token;

    if (storedToken) {
      // Verify token integrity with backend
      fetch(`${apiBaseUrl}/user`, {
        headers: {
          Authorization: `Bearer ${storedToken}`,
        },
      })
        .then((res) => {
          if (res.ok) {
            return res.json();
          }
          throw new Error("Invalid session token");
        })
        .then((data) => {
          if (data.user) {
            setUser(data.user);
            localStorage.setItem("auth_user", JSON.stringify(data.user));
            if (data.user.language) {
              setLocaleState(data.user.language);
              localStorage.setItem("app_locale", data.user.language);
            }
          }
        })
        .catch((err) => {
          console.error("Token verification failed:", err);
          // Token is invalid/expired; clear local session
          localStorage.removeItem("auth_token");
          localStorage.removeItem("auth_user");
          setToken(null);
          setUser(null);
        })
        .finally(() => {
          setLoading(false);
        });
    }
  }, [apiBaseUrl, token]);

  const login = (newToken: string, newUser: User) => {
    localStorage.setItem("auth_token", newToken);
    localStorage.setItem("auth_user", JSON.stringify(newUser));
    if (newUser.language) {
      localStorage.setItem("app_locale", newUser.language);
      setLocaleState(newUser.language);
    }
    setToken(newToken);
    setUser(newUser);
  };

  const logout = async () => {
    const currentToken = token || localStorage.getItem("auth_token");
    
    if (currentToken) {
      try {
        await fetch(`${apiBaseUrl}/auth/logout`, {
          method: "POST",
          headers: {
            Authorization: `Bearer ${currentToken}`,
          },
        });
      } catch (err) {
        console.error("Failed to invalidate session token on backend:", err);
      }
    }

    localStorage.removeItem("auth_token");
    localStorage.removeItem("auth_user");
    setToken(null);
    setUser(null);
    router.push("/");
  };

  const setLocale = async (lang: Locale) => {
    setLocaleState(lang);
    localStorage.setItem("app_locale", lang);

    // If logged in, persist to backend profile database
    if (token && user) {
      try {
        const res = await fetch(`${apiBaseUrl}/user/language`, {
          method: "PUT",
          headers: {
            "Content-Type": "application/json",
            Authorization: `Bearer ${token}`,
          },
          body: JSON.stringify({ language: lang }),
        });

        if (res.ok) {
          const updatedUser = { ...user, language: lang };
          setUser(updatedUser);
          localStorage.setItem("auth_user", JSON.stringify(updatedUser));
        }
      } catch (err) {
        console.error("Failed to persist language preference on backend:", err);
      }
    }
  };

  const getGoogleAuthUrl = async (): Promise<string> => {
    const res = await fetch(`${apiBaseUrl}/auth/google/url`);
    if (!res.ok) {
      throw new Error("Failed to fetch authorization URL from server.");
    }
    const data = await res.json();
    return data.url;
  };

  const refreshUser = async () => {
    const currentToken = token || localStorage.getItem("auth_token");

    if (!currentToken) {
      return;
    }

    const res = await fetch(`${apiBaseUrl}/user`, {
      headers: {
        Authorization: `Bearer ${currentToken}`,
      },
    });

    if (!res.ok) {
      return;
    }

    const data = await res.json();

    if (data.user) {
      setUser(data.user);
      localStorage.setItem("auth_user", JSON.stringify(data.user));
      if (data.user.language) {
        setLocaleState(data.user.language);
        localStorage.setItem("app_locale", data.user.language);
      }
    }
  };

  return (
    <AuthContext.Provider value={{ user, token, loading, locale, setLocale, login, logout, refreshUser, getGoogleAuthUrl }}>
      {children}
    </AuthContext.Provider>
  );
}

export function useAuth() {
  const context = useContext(AuthContext);
  if (context === undefined) {
    throw new Error("useAuth must be used within an AuthProvider");
  }
  return context;
}
