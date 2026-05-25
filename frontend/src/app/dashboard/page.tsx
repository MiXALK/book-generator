"use client";

import { useEffect } from "react";
import { useRouter } from "next/navigation";
import { useAuth } from "@/app/context/AuthContext";
import styles from "./dashboard.module.css";

export default function DashboardPage() {
  const router = useRouter();
  const { user, loading, logout } = useAuth();

  useEffect(() => {
    if (!loading && !user) {
      router.push("/");
    }
  }, [user, loading, router]);

  if (loading) {
    return (
      <div className={styles.loadingContainer}>
        <div className={styles.spinner}></div>
        <p>Loading your dashboard...</p>
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
          <span className={styles.logoText}>StorySprout</span>
        </div>
        <nav className={styles.nav}>
          <div className={styles.userInfo}>
            {user.avatar_url ? (
              <img src={user.avatar_url} alt={user.name} className={styles.avatar} />
            ) : (
              <div className={styles.avatarPlaceholder}>{user.name.charAt(0)}</div>
            )}
            <div className={styles.userMeta}>
              <span className={styles.userName}>{user.name}</span>
              <span className={`${styles.planBadge} ${isPaid ? styles.paidBadge : styles.freeBadge}`}>
                {isPaid ? "Premium Subscriber" : "Free Tier"}
              </span>
            </div>
          </div>
          <button className={styles.logoutButton} onClick={logout}>
            Sign Out
          </button>
        </nav>
      </header>

      <main className={styles.main}>
        <section className={styles.welcomeBanner}>
          <div className={styles.welcomeText}>
            <h1>Welcome back, {user.name.split(" ")[0]}!</h1>
            <p>Let&apos;s inspire developmental growth and create a magical reading milestone today.</p>
          </div>
          <div className={styles.quickCta}>
            <button className={styles.primaryCta} onClick={() => alert("Story Generation (Stage 3) is being prepared.")}>
              + Create New Storybook
            </button>
          </div>
        </section>

        <div className={styles.grid}>
          {/* Subscription Limits Status */}
          <div className={styles.card}>
            <h2 className={styles.cardTitle}>Monthly Usage Limit</h2>
            <div className={styles.limitTracker}>
              <div className={styles.limitNumbers}>
                <span className={styles.limitCurrent}>0</span>
                <span className={styles.limitTotal}>/ {monthlyLimit} books</span>
              </div>
              <p className={styles.limitSub}>Quota resets on the 1st of next month.</p>
              <div className={styles.progressBarBg}>
                <div className={styles.progressBarFill} style={{ width: "0%" }}></div>
              </div>
            </div>
            {!isPaid && (
              <div className={styles.upgradeBox}>
                <p>Unlock photo personalization, expanded templates, and up to 10 stories per month!</p>
                <button className={styles.upgradeButton} onClick={() => alert("Stripe Billing (Stage 5) is being configured.")}>
                  Upgrade to Premium
                </button>
              </div>
            )}
          </div>

          {/* Guidelines & Safety Card */}
          <div className={styles.card}>
            <h2 className={styles.cardTitle}>Privacy & Parental Consent</h2>
            <div className={styles.safetyContent}>
              <div className={styles.safetyItem}>
                <span className={styles.safetyIcon}>✓</span>
                <div>
                  <h4>Strict Photo Deletion</h4>
                  <p>Uploaded photos are deleted immediately after generating character faces for paid users.</p>
                </div>
              </div>
              <div className={styles.safetyItem}>
                <span className={styles.safetyIcon}>✓</span>
                <div>
                  <h4>Parental Consent First</h4>
                  <p>By uploading any profile reference, you assert that you are the legal parent or guardian.</p>
                </div>
              </div>
              <div className={styles.safetyItem}>
                <span className={styles.safetyIcon}>✓</span>
                <div>
                  <h4>Private S3 Storage</h4>
                  <p>All illustrations and metadata assets are stored privately in encrypted MinIO/S3 folders.</p>
                </div>
              </div>
            </div>
          </div>

          {/* Book Library Empty State Placeholder */}
          <div className={`${styles.card} ${styles.fullWidth}`}>
            <h2 className={styles.cardTitle}>My Storybook Library</h2>
            <div className={styles.emptyState}>
              <span className={styles.emptyIcon}>📚</span>
              <h3>No generated books yet</h3>
              <p>Your personalized developmental storybooks will appear here once generated.</p>
              <button className={styles.emptyCta} onClick={() => alert("Story Generation (Stage 3) is being prepared.")}>
                Get Started by Creating Your First Book
              </button>
            </div>
          </div>
        </div>
      </main>

      <footer className={styles.footer}>
        <p>&copy; {new Date().getFullYear()} StorySprout. Built with robust children data privacy compliance.</p>
      </footer>
    </div>
  );
}
