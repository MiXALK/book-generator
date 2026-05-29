"use client";

import { useEffect } from "react";
import { useRouter } from "next/navigation";
import { useAuth } from "@/app/context/AuthContext";
import { locales } from "@/app/context/locales";
import LanguageSelect from "@/app/components/LanguageSelect";
import styles from "./dashboard.module.css";

export default function DashboardPage() {
  const router = useRouter();
  const { user, loading, logout, locale } = useAuth();

  // Load correct translations
  const t = locales[locale] || locales.ru;

  useEffect(() => {
    if (!loading && !user) {
      router.push("/");
    }
  }, [user, loading, router]);

  if (loading) {
    return (
      <div className={styles.loadingContainer}>
        <div className={styles.spinner}></div>
        <p>{t.loadingDashboard}</p>
      </div>
    );
  }

  if (!user) {
    return null; // Prevents flashing content during redirect
  }

  const isPaid = user.plan === "paid";
  const monthlyLimit = isPaid ? 10 : 3;

  return (
    <div className={styles.container}>
      <header className={styles.header}>
        <div className={styles.brand}>
          <span className={styles.logo}>📖</span>
          <span className={styles.logoText}>{t.brandName}</span>
        </div>
        <nav className={styles.nav}>
          <LanguageSelect />
          <div className={styles.userInfo}>
            {user.avatar_url ? (
              <img src={user.avatar_url} alt={user.name} className={styles.avatar} />
            ) : (
              <div className={styles.avatarPlaceholder}>{user.name.charAt(0)}</div>
            )}
            <div className={styles.userMeta}>
              <span className={styles.userName}>{user.name}</span>
              <span className={`${styles.planBadge} ${isPaid ? styles.paidBadge : styles.freeBadge}`}>
                {isPaid ? t.premiumSubscriber : t.freeTier}
              </span>
            </div>
          </div>
          <button className={styles.logoutButton} onClick={logout}>
            {t.signOut}
          </button>
        </nav>
      </header>

      <main className={styles.main}>
        <section className={styles.welcomeBanner}>
          <div className={styles.welcomeText}>
            <h1>{t.welcomeBack}, {user.name.split(" ")[0]}!</h1>
            <p>{t.letInspire}</p>
          </div>
          <div className={styles.quickCta}>
            <button className={styles.primaryCta} onClick={() => router.push("/generate")}>
              {t.createNewStorybook}
            </button>
          </div>
        </section>

        <div className={styles.grid}>
          {/* Subscription Limits Status */}
          <div className={styles.card}>
            <h2 className={styles.cardTitle}>{t.monthlyUsageLimit}</h2>
            <div className={styles.limitTracker}>
              <div className={styles.limitNumbers}>
                <span className={styles.limitCurrent}>0</span>
                <span className={styles.limitTotal}>/ {monthlyLimit} {t.books}</span>
              </div>
              <p className={styles.limitSub}>{t.quotaResets}</p>
              <div className={styles.progressBarBg}>
                <div className={styles.progressBarFill} style={{ width: "0%" }}></div>
              </div>
            </div>
            {!isPaid && (
              <div className={styles.upgradeBox}>
                <p>{t.unlockPremium}</p>
                <button className={styles.upgradeButton} onClick={() => alert(t.stripePrepAlert)}>
                  {t.upgradeToPremium}
                </button>
              </div>
            )}
          </div>

          {/* Guidelines & Safety Card */}
          <div className={styles.card}>
            <h2 className={styles.cardTitle}>{t.privacyConsentTitle}</h2>
            <div className={styles.safetyContent}>
              <div className={styles.safetyItem}>
                <span className={styles.safetyIcon}>✓</span>
                <div>
                  <h4>{t.strictPhotoDeletionTitle}</h4>
                  <p>{t.strictPhotoDeletionDesc}</p>
                </div>
              </div>
              <div className={styles.safetyItem}>
                <span className={styles.safetyIcon}>✓</span>
                <div>
                  <h4>{t.parentalConsentTitle}</h4>
                  <p>{t.parentalConsentDesc}</p>
                </div>
              </div>
              <div className={styles.safetyItem}>
                <span className={styles.safetyIcon}>✓</span>
                <div>
                  <h4>{t.privateS3Title}</h4>
                  <p>{t.privateS3Desc}</p>
                </div>
              </div>
            </div>
          </div>

          {/* Book Library Empty State Placeholder */}
          <div className={`${styles.card} ${styles.fullWidth}`}>
            <h2 className={styles.cardTitle}>{t.myLibraryTitle}</h2>
            <div className={styles.emptyState}>
              <span className={styles.emptyIcon}>📚</span>
              <h3>{t.noBooksYet}</h3>
              <p>{t.noBooksDesc}</p>
              <button className={styles.emptyCta} onClick={() => router.push("/generate")}>
                {t.getFirstBook}
              </button>
            </div>
          </div>
        </div>
      </main>

      <footer className={styles.footer}>
        <p>&copy; {new Date().getFullYear()} {t.brandName}. {t.privacyBuiltIn}</p>
      </footer>
    </div>
  );
}
