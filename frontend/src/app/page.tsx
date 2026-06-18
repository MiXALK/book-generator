"use client";

import { useEffect, useState } from "react";
import { useRouter } from "next/navigation";
import { useAuth } from "@/app/context/AuthContext";
import { locales } from "@/app/context/locales";
import LanguageSelect from "@/app/components/LanguageSelect";
import btn from "@/app/components/storyButton.module.css";
import styles from "./page.module.css";

export default function Home() {
  const router = useRouter();
  const { user, loading, locale, getGoogleAuthUrl } = useAuth();
  const [apiStatus, setApiStatus] = useState<"loading" | "connected" | "failed">("loading");
  const [errorMessage, setErrorMessage] = useState<string | null>(null);
  const [authLoading, setAuthLoading] = useState(false);

  // Load correct translations
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

  return (
    <div className={styles.page}>
      <header className={styles.header}>
        <div className={styles.logoContainer}>
          <span className={styles.logoText}>📖 {t.brandName}</span>
        </div>
        <div className={styles.navActions}>
          <LanguageSelect />
          {loading ? (
            <button className={btn.btnGhost} disabled>
              {t.loading}
            </button>
          ) : user ? (
            <button className={btn.btnGhost} onClick={() => router.push("/dashboard")}>
              {t.goToDashboard}
            </button>
          ) : (
            <button className={btn.btnGhost} onClick={handleSignIn} disabled={authLoading}>
              {authLoading ? t.redirecting : t.signInWithGoogle}
            </button>
          )}
        </div>
      </header>

      <main className={styles.main}>
        <div className={styles.hero}>
          <h1 className={styles.title}>
            {t.heroTitle}
          </h1>
          <p className={styles.subtitle}>
            {t.heroSubtitle}
          </p>
          
          <div className={styles.features}>
            <div className={styles.featureCard}>
              <span className={styles.featureIcon}>✨</span>
              <h3>{t.featureTemplatesTitle}</h3>
              <p>{t.featureTemplatesDesc}</p>
            </div>
            <div className={styles.featureCard}>
              <span className={styles.featureIcon}>🎨</span>
              <h3>{t.featureIllustrationsTitle}</h3>
              <p>{t.featureIllustrationsDesc}</p>
            </div>
          </div>

          <div className={styles.ctaGroup}>
            {loading ? (
              <button className={`${btn.btnPrimary} ${btn.btnLarge}`} disabled>
                {t.checkingSession}
              </button>
            ) : user ? (
              <button className={`${btn.btnPrimary} ${btn.btnLarge}`} onClick={() => router.push("/dashboard")}>
                {t.goToDashboard}
              </button>
            ) : (
              <button className={`${btn.btnPrimary} ${btn.btnLarge}`} onClick={handleSignIn} disabled={authLoading}>
                {authLoading ? t.preparingGoogle : t.getStartedFree}
              </button>
            )}
          </div>
        </div>

        <div className={styles.statusWidget}>
          <h3 className={styles.widgetTitle}>{t.systemStatus}</h3>
          <div className={styles.statusRow}>
            <span>{t.backendApi}:</span>
            {apiStatus === "loading" && (
              <span className={`${styles.statusBadge} ${styles.statusLoading}`}>
                {t.connecting}
              </span>
            )}
            {apiStatus === "connected" && (
              <span className={`${styles.statusBadge} ${styles.statusConnected}`}>
                {t.connected}
              </span>
            )}
            {apiStatus === "failed" && (
              <span className={`${styles.statusBadge} ${styles.statusFailed}`}>
                {t.offline} ({errorMessage})
              </span>
            )}
          </div>
        </div>
      </main>

      <footer className={styles.footer}>
        <p>&copy; {new Date().getFullYear()} {t.brandName}. {t.rightsReserved}</p>
      </footer>
    </div>
  );
}
