"use client";

import { useEffect, useState } from "react";
import { useRouter } from "next/navigation";
import { useAuth } from "@/app/context/AuthContext";
import { locales } from "@/app/context/locales";
import AppFooter from "@/app/components/AppFooter";
import AppHeader from "@/app/components/AppHeader";
import PageShell, { PageShellMain } from "@/app/components/PageShell";
import ui from "@/app/components/ui.module.css";
import styles from "./page.module.css";

export default function Home() {
  const router = useRouter();
  const { user, loading, locale, getGoogleAuthUrl } = useAuth();
  const [apiStatus, setApiStatus] = useState<"loading" | "connected" | "failed">("loading");
  const [errorMessage, setErrorMessage] = useState<string | null>(null);
  const [authLoading, setAuthLoading] = useState(false);

  const t = locales[locale] || locales.ru;

  useEffect(() => {
    const apiBaseUrl = process.env.NEXT_PUBLIC_API_BASE_URL || "http://localhost:8000/api";

    fetch(`${apiBaseUrl}/health`)
      .then((res) => {
        if (!res.ok) {
          throw new Error(`HTTP error! status: ${res.status}`);
        }
        return res.json();
      })
      .then((data) => {
        if (data.status === "ok") {
          setApiStatus("connected");
        } else {
          setApiStatus("failed");
          setErrorMessage("Unexpected API response format");
        }
      })
      .catch((err) => {
        console.error("Failed to fetch backend health status:", err);
        setApiStatus("failed");
        setErrorMessage(err.message || "Failed to reach backend");
      });
  }, []);

  const handleSignIn = async () => {
    if (authLoading) return;
    setAuthLoading(true);
    try {
      const authUrl = await getGoogleAuthUrl();
      window.location.href = authUrl;
    } catch (err: unknown) {
      console.error("Failed to initiate Google OAuth redirect:", err);
      const msg = err instanceof Error ? err.message : t.oauthConfigAlert;
      alert(msg);
      setAuthLoading(false);
    }
  };

  const statusClass =
    apiStatus === "loading"
      ? styles.statusLoading
      : apiStatus === "connected"
        ? styles.statusConnected
        : styles.statusFailed;

  const statusLabel =
    apiStatus === "loading"
      ? t.connecting
      : apiStatus === "connected"
        ? t.connected
        : `${t.offline}${errorMessage ? `: ${errorMessage}` : ""}`;

  return (
    <PageShell variant="landing">
      <div className={styles.mesh} aria-hidden="true" />

      <AppHeader brandName={t.brandName} className={styles.landingChrome}>
        {loading ? (
          <button className={ui.btnGhost} disabled>
            {t.loading}
          </button>
        ) : user ? (
          <button className={ui.btnGhost} onClick={() => router.push("/dashboard")}>
            {t.goToDashboard}
          </button>
        ) : (
          <button className={ui.btnGhost} onClick={handleSignIn} disabled={authLoading}>
            {authLoading ? t.redirecting : t.signInWithGoogle}
          </button>
        )}
      </AppHeader>

      <PageShellMain variant="wide">
        <section className={styles.hero}>
          <div className={styles.heroCopy}>
            <p className={styles.eyebrow}>{t.featuresTitle}</p>
            <h1 className={styles.title}>{t.heroTitle}</h1>
            <p className={styles.subtitle}>{t.heroSubtitle}</p>

            <div className={styles.ctaRow}>
              {loading ? (
                <button className={`${ui.btnPrimary} ${ui.btnLarge}`} disabled>
                  {t.checkingSession}
                </button>
              ) : user ? (
                <button className={`${ui.btnPrimary} ${ui.btnLarge}`} onClick={() => router.push("/dashboard")}>
                  {t.goToDashboard}
                </button>
              ) : (
                <button className={`${ui.btnPrimary} ${ui.btnLarge}`} onClick={handleSignIn} disabled={authLoading}>
                  {authLoading ? t.preparingGoogle : t.getStartedFree}
                </button>
              )}

              <span className={statusClass} title={t.systemStatus}>
                <span className={styles.statusDot} aria-hidden="true" />
                {t.backendApi}: {statusLabel}
              </span>
            </div>
          </div>

          <div className={styles.heroVisual} aria-hidden="true">
            <div className={styles.bookStack}>
              <div className={`${styles.book} ${styles.bookBack}`} />
              <div className={`${styles.book} ${styles.bookMid}`} />
              <div className={`${styles.book} ${styles.bookFront}`}>
                <div className={styles.bookCoverArt} />
                <p className={styles.bookTitle}>{t.landingBookPreviewTitle}</p>
              </div>
            </div>
            <span className={`${styles.spark} ${styles.sparkA}`}>✨</span>
            <span className={`${styles.spark} ${styles.sparkB}`}>🌿</span>
          </div>
        </section>

        <section className={styles.bento} aria-label={t.featuresTitle}>
          <article className={styles.bentoCard}>
            <span className={styles.bentoIcon}>✨</span>
            <h3>{t.featureTemplatesTitle}</h3>
            <p>{t.featureTemplatesDesc}</p>
          </article>
          <article className={styles.bentoCard}>
            <span className={styles.bentoIcon}>🎨</span>
            <h3>{t.featureIllustrationsTitle}</h3>
            <p>{t.featureIllustrationsDesc}</p>
          </article>
          <article className={`${styles.bentoCard} ${styles.bentoAccent}`}>
            <span className={styles.bentoIcon}>📖</span>
            <h3>{t.featureReaderTitle}</h3>
            <p>{t.featureReaderDesc}</p>
          </article>
        </section>
      </PageShellMain>

      <AppFooter>
        &copy; {new Date().getFullYear()} {t.brandName}. {t.rightsReserved}
      </AppFooter>
    </PageShell>
  );
}
