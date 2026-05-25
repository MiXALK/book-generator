"use client";

import { useEffect, useState, Suspense } from "react";
import { useRouter, useSearchParams } from "next/navigation";
import { useAuth } from "@/app/context/AuthContext";
import styles from "./callback.module.css";

function CallbackHandler() {
  const router = useRouter();
  const searchParams = useSearchParams();
  const { login } = useAuth();
  const [status, setStatus] = useState<"loading" | "success" | "error">("loading");
  const [errorMessage, setErrorMessage] = useState<string | null>(null);

  useEffect(() => {
    const code = searchParams.get("code");
    const error = searchParams.get("error");

    if (error) {
      Promise.resolve().then(() => {
        setStatus("error");
        setErrorMessage(error === "access_denied" ? "Google sign-in was cancelled." : `Google error: ${error}`);
      });
      return;
    }

    if (!code) {
      Promise.resolve().then(() => {
        setStatus("error");
        setErrorMessage("No authorization code received from Google.");
      });
      return;
    }

    const apiBaseUrl = process.env.NEXT_PUBLIC_API_BASE_URL || "http://localhost:8000/api";

    fetch(`${apiBaseUrl}/auth/google/callback`, {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
      },
      body: JSON.stringify({ code }),
    })
      .then(async (res) => {
        if (!res.ok) {
          const errData = await res.json().catch(() => ({}));
          throw new Error(errData.message || "Failed to exchange authorization token.");
        }
        return res.json();
      })
      .then((data) => {
        if (data.token && data.user) {
          login(data.token, data.user);
          Promise.resolve().then(() => {
            setStatus("success");
          });
          // Redirect to authenticated dashboard after a short delay
          setTimeout(() => {
            router.push("/dashboard");
          }, 1000);
        } else {
          throw new Error("Invalid response payload from authentication server.");
        }
      })
      .catch((err) => {
        console.error("Authentication callback exchange failed:", err);
        Promise.resolve().then(() => {
          setStatus("error");
          setErrorMessage(err.message || "Internal authentication server error.");
        });
      });
  }, [searchParams, login, router]);

  return (
    <div className={styles.container}>
      <div className={styles.card}>
        {status === "loading" && (
          <div className={styles.loadingState}>
            <div className={styles.spinner}></div>
            <h2>Authenticating...</h2>
            <p>Verifying secure identity details with Google, please wait.</p>
          </div>
        )}

        {status === "success" && (
          <div className={styles.successState}>
            <div className={styles.successIcon}>✓</div>
            <h2>Sign-In Successful!</h2>
            <p>Redirecting you to your StorySprout workspace.</p>
          </div>
        )}

        {status === "error" && (
          <div className={styles.errorState}>
            <div className={styles.errorIcon}>✕</div>
            <h2>Sign-In Failed</h2>
            <p className={styles.errorText}>{errorMessage}</p>
            <button className={styles.retryButton} onClick={() => router.push("/")}>
              Return to Landing Page
            </button>
          </div>
        )}
      </div>
    </div>
  );
}

export default function CallbackPage() {
  return (
    <Suspense fallback={
      <div className={styles.container}>
        <div className={styles.card}>
          <div className={styles.loadingState}>
            <div className={styles.spinner}></div>
            <h2>Initializing...</h2>
            <p>Loading application session details.</p>
          </div>
        </div>
      </div>
    }>
      <CallbackHandler />
    </Suspense>
  );
}
