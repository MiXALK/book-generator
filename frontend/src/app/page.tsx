"use client";

import { useEffect, useState } from "react";
import styles from "./page.module.css";

export default function Home() {
  const [apiStatus, setApiStatus] = useState<"loading" | "connected" | "failed">("loading");
  const [errorMessage, setErrorMessage] = useState<string | null>(null);

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

  return (
    <div className={styles.page}>
      <header className={styles.header}>
        <div className={styles.logoContainer}>
          <span className={styles.logoText}>📖 StorySprout</span>
        </div>
        <div className={styles.navActions}>
          <button className={styles.signInButton} onClick={() => alert("Google OAuth will be configured in Stage 2")}>
            Sign In with Google
          </button>
        </div>
      </header>

      <main className={styles.main}>
        <div className={styles.hero}>
          <h1 className={styles.title}>
            Personalized Children&apos;s Stories Tailored For Their Growth
          </h1>
          <p className={styles.subtitle}>
            Empower your child&apos;s development with custom, beautifully illustrated stories. 
            Address developmental goals like learning to share, overcoming fears, or bedtime routines.
          </p>
          
          <div className={styles.features}>
            <div className={styles.featureCard}>
              <span className={styles.featureIcon}>✨</span>
              <h3>Custom Templates</h3>
              <p>Choose from our template library specifically designed for early childhood developmental milestones.</p>
            </div>
            <div className={styles.featureCard}>
              <span className={styles.featureIcon}>🎨</span>
              <h3>AI Illustrations</h3>
              <p>Engage your child with rich, personalized visual storytelling featuring custom-styled characters.</p>
            </div>
          </div>

          <div className={styles.ctaGroup}>
            <button className={styles.primaryCta} onClick={() => alert("Google OAuth will be configured in Stage 2")}>
              Get Started Free
            </button>
          </div>
        </div>

        <div className={styles.statusWidget}>
          <h3 className={styles.widgetTitle}>System Status</h3>
          <div className={styles.statusRow}>
            <span>Backend API:</span>
            {apiStatus === "loading" && (
              <span className={`${styles.statusBadge} ${styles.statusLoading}`}>
                Connecting...
              </span>
            )}
            {apiStatus === "connected" && (
              <span className={`${styles.statusBadge} ${styles.statusConnected}`}>
                Connected
              </span>
            )}
            {apiStatus === "failed" && (
              <span className={`${styles.statusBadge} ${styles.statusFailed}`}>
                Offline ({errorMessage})
              </span>
            )}
          </div>
        </div>
      </main>

      <footer className={styles.footer}>
        <p>&copy; {new Date().getFullYear()} StorySprout. All rights reserved.</p>
      </footer>
    </div>
  );
}
