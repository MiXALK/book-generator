"use client";

import { useEffect, useMemo, useState } from "react";
import { useRouter } from "next/navigation";
import { useAuth } from "@/app/context/AuthContext";
import { locales } from "@/app/context/locales";
import LanguageSelect from "@/app/components/LanguageSelect";
import { BookGeneration } from "@/app/types/book";
import styles from "./dashboard.module.css";

export default function DashboardPage() {
  const router = useRouter();
  const { token, user, loading, logout, locale } = useAuth();
  const t = locales[locale] || locales.ru;
  const apiBaseUrl = process.env.NEXT_PUBLIC_API_BASE_URL || "http://localhost:8000/api";

  const [books, setBooks] = useState<BookGeneration[]>([]);
  const [libraryLoading, setLibraryLoading] = useState(true);
  const [libraryError, setLibraryError] = useState<string | null>(null);
  const [billingLoading, setBillingLoading] = useState(false);

  useEffect(() => {
    if (!loading && !user) {
      router.push("/");
    }
  }, [user, loading, router]);

  useEffect(() => {
    if (!token) {
      return;
    }

    const loadLibrary = async () => {
      setLibraryLoading(true);
      setLibraryError(null);

      try {
        const response = await fetch(`${apiBaseUrl}/books/history`, {
          headers: {
            Authorization: `Bearer ${token}`,
          },
        });
        const data = await response.json();

        if (!response.ok) {
          throw new Error(data.message || t.bookLoadError);
        }

        setBooks(data.items ?? []);
      } catch (err) {
        const message = err instanceof Error ? err.message : t.bookLoadError;
        setLibraryError(message);
      } finally {
        setLibraryLoading(false);
      }
    };

    loadLibrary();
  }, [apiBaseUrl, t.bookLoadError, token]);

  const monthlyUsage = useMemo(() => {
    const now = new Date();
    const monthStart = new Date(now.getFullYear(), now.getMonth(), 1);

    return books.filter((book) => new Date(book.created_at) >= monthStart).length;
  }, [books]);

  if (loading) {
    return (
      <div className={styles.loadingContainer}>
        <div className={styles.spinner}></div>
        <p>{t.loadingDashboard}</p>
      </div>
    );
  }

  if (!user) {
    return null;
  }

  const isPaid = user.plan === "paid" && user.subscription_status === "active";
  const monthlyLimit = isPaid ? 10 : 3;
  const usagePercent = Math.min(100, Math.round((monthlyUsage / monthlyLimit) * 100));

  const startCheckout = async () => {
    if (!token) {
      return;
    }

    setBillingLoading(true);

    try {
      const response = await fetch(`${apiBaseUrl}/billing/checkout`, {
        method: "POST",
        headers: {
          Authorization: `Bearer ${token}`,
        },
      });
      const data = await response.json();

      if (!response.ok || typeof data.url !== "string") {
        throw new Error(data.message || t.billingNotConfigured);
      }

      window.location.href = data.url;
    } catch (err) {
      const message = err instanceof Error ? err.message : t.billingNotConfigured;
      alert(message);
      setBillingLoading(false);
    }
  };

  const openBillingPortal = async () => {
    if (!token) {
      return;
    }

    setBillingLoading(true);

    try {
      const response = await fetch(`${apiBaseUrl}/billing/portal`, {
        method: "POST",
        headers: {
          Authorization: `Bearer ${token}`,
        },
      });
      const data = await response.json();

      if (!response.ok || typeof data.url !== "string") {
        throw new Error(data.message || t.billingNotConfigured);
      }

      window.location.href = data.url;
    } catch (err) {
      const message = err instanceof Error ? err.message : t.billingNotConfigured;
      alert(message);
      setBillingLoading(false);
    }
  };

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
          <div className={styles.card}>
            <h2 className={styles.cardTitle}>{t.monthlyUsageLimit}</h2>
            <div className={styles.limitTracker}>
              <div className={styles.limitNumbers}>
                <span className={styles.limitCurrent}>{monthlyUsage}</span>
                <span className={styles.limitTotal}>/ {monthlyLimit} {t.books}</span>
              </div>
              <p className={styles.limitSub}>{t.quotaResets}</p>
              <div className={styles.progressBarBg}>
                <div className={styles.progressBarFill} style={{ width: `${usagePercent}%` }}></div>
              </div>
            </div>
            {!isPaid && (
              <div className={styles.upgradeBox}>
                <p>{t.unlockPremium}</p>
                <button
                  className={styles.upgradeButton}
                  onClick={startCheckout}
                  disabled={billingLoading}
                >
                  {billingLoading ? t.billingRedirecting : t.upgradeToPremium}
                </button>
              </div>
            )}
            {isPaid && (
              <div className={styles.upgradeBox}>
                <button
                  className={styles.upgradeButton}
                  onClick={openBillingPortal}
                  disabled={billingLoading}
                >
                  {billingLoading ? t.billingRedirecting : t.manageBilling}
                </button>
              </div>
            )}
          </div>

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

          <div className={`${styles.card} ${styles.fullWidth}`}>
            <h2 className={styles.cardTitle}>{t.myLibraryTitle}</h2>
            {libraryLoading && <p className={styles.libraryState}>{t.loading}</p>}
            {libraryError && <p className={styles.libraryError}>{libraryError}</p>}
            {!libraryLoading && !libraryError && books.length === 0 && (
              <div className={styles.emptyState}>
                <span className={styles.emptyIcon}>📚</span>
                <h3>{t.noBooksYet}</h3>
                <p>{t.noBooksDesc}</p>
                <button className={styles.emptyCta} onClick={() => router.push("/generate")}>
                  {t.getFirstBook}
                </button>
              </div>
            )}
            {!libraryLoading && !libraryError && books.length > 0 && (
              <div className={styles.libraryGrid}>
                {books.map((book) => {
                  const coverImage = book.book_pages[0]?.image_url;
                  const createdLabel = new Date(book.created_at).toLocaleDateString();

                  return (
                    <article key={book.id} className={styles.libraryCard}>
                      <div className={styles.libraryCover}>
                        {coverImage ? (
                          <img src={coverImage} alt="" />
                        ) : (
                          <div className={styles.libraryCoverFallback} />
                        )}
                      </div>
                      <div className={styles.libraryMeta}>
                        <h3>{book.book_template?.title ?? book.child_name}</h3>
                        <p>{book.child_name} · {book.child_goal}</p>
                        <p>{t.createdAt}: {createdLabel}</p>
                        <p>{book.book_pages.length} {t.pagesCount}</p>
                        <button
                          type="button"
                          className={styles.libraryReadButton}
                          onClick={() => router.push(`/books/${book.id}`)}
                        >
                          {t.readBook}
                        </button>
                      </div>
                    </article>
                  );
                })}
              </div>
            )}
          </div>
        </div>
      </main>

      <footer className={styles.footer}>
        <p>&copy; {new Date().getFullYear()} {t.brandName}. {t.privacyBuiltIn}</p>
      </footer>
    </div>
  );
}
