"use client";

import { useEffect, useState, Suspense } from "react";
import { useRouter, useSearchParams } from "next/navigation";
import { useAuth } from "@/app/context/AuthContext";
import { locales } from "@/app/context/locales";
import PageShell, { PageShellMain } from "@/app/components/PageShell";
import ui from "@/app/components/ui.module.css";
import styles from "./callback.module.css";

function CallbackHandler() {
  const router = useRouter();
  const searchParams = useSearchParams();
  const { login, locale } = useAuth();
  const t = locales[locale] || locales.ru;
  const [status, setStatus] = useState<"loading" | "success" | "error">("loading");
  const [errorMessage, setErrorMessage] = useState<string | null>(null);

  useEffect(() => {
    const code = searchParams.get("code");
    const error = searchParams.get("error");

    if (error) {
      Promise.resolve().then(() => {
        setStatus("error");
        setErrorMessage(
          error === "access_denied"
            ? t.authGoogleCancelled
            : t.authGoogleError.replace("{error}", error),
        );
      });
      return;
    }

    if (!code) {
      Promise.resolve().then(() => {
        setStatus("error");
        setErrorMessage(t.authNoCode);
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
          throw new Error(errData.message || t.authExchangeFailed);
        }
        return res.json();
      })
      .then((data) => {
        if (data.token && data.user) {
          login(data.token, data.user);
          Promise.resolve().then(() => {
            setStatus("success");
          });
          setTimeout(() => {
            router.push("/dashboard");
          }, 1000);
        } else {
          throw new Error(t.authInvalidResponse);
        }
      })
      .catch((err) => {
        console.error("Authentication callback exchange failed:", err);
        Promise.resolve().then(() => {
          setStatus("error");
          setErrorMessage(err.message || t.authServerError);
        });
      });
  }, [searchParams, login, router, t]);

  return (
    <PageShellMain variant="centered">
      <div className={styles.card}>
        {status === "loading" && (
          <div className={styles.stateBlock}>
            <div className={ui.spinnerLarge} />
            <h2>{t.authenticating}</h2>
            <p>{t.verifyingIdentity}</p>
          </div>
        )}

        {status === "success" && (
          <div className={styles.stateBlock}>
            <div className={styles.successIcon}>✓</div>
            <h2>{t.signInSuccess}</h2>
            <p>{t.redirectingWorkspace}</p>
          </div>
        )}

        {status === "error" && (
          <div className={styles.stateBlock}>
            <div className={styles.errorIcon}>✕</div>
            <h2>{t.signInFailed}</h2>
            <p className={styles.errorText}>{errorMessage}</p>
            <button className={`${ui.btnPrimary} ${styles.retryButton}`} onClick={() => router.push("/")}>
              {t.returnLanding}
            </button>
          </div>
        )}
      </div>
    </PageShellMain>
  );
}

export default function CallbackPage() {
  const { locale } = useAuth();
  const t = locales[locale] || locales.ru;

  return (
    <PageShell>
      <Suspense
        fallback={
          <PageShellMain variant="centered">
            <div className={styles.card}>
              <div className={styles.stateBlock}>
                <div className={ui.spinnerLarge} />
                <h2>{t.initializing}</h2>
                <p>{t.loadingSession}</p>
              </div>
            </div>
          </PageShellMain>
        }
      >
        <CallbackHandler />
      </Suspense>
    </PageShell>
  );
}
