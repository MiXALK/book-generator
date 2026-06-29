"use client";

import { useEffect, useMemo, useState } from "react";
import Image from "next/image";
import Link from "next/link";
import { useRouter } from "next/navigation";
import { useAuth } from "@/app/context/AuthContext";
import { locales } from "@/app/context/locales";
import AppFooter from "@/app/components/AppFooter";
import AppHeader from "@/app/components/AppHeader";
import PageShell, { PageShellMain } from "@/app/components/PageShell";
import ui from "@/app/components/ui.module.css";
import { BookGeneration } from "@/app/types/book";
import styles from "./dashboard.module.css";

function BookBasketIcon({ className }: { className?: string }) {
  return (
    <svg
      className={className}
      viewBox="0 0 24 24"
      fill="none"
      stroke="currentColor"
      strokeWidth="2"
      aria-hidden="true"
    >
      <path d="M6 7h12l-1 12a2 2 0 0 1-2 2H9a2 2 0 0 1-2-2L6 7z" />
      <path d="M9 7V5a3 3 0 0 1 6 0v2" />
      <path d="M4 7h16" strokeLinecap="round" />
    </svg>
  );
}

export default function DashboardPage() {
  const router = useRouter();
  const { token, user, loading, logout, locale } = useAuth();
  const t = locales[locale] || locales.ru;
  const apiBaseUrl = process.env.NEXT_PUBLIC_API_BASE_URL || "http://localhost:8000/api";

  const [books, setBooks] = useState<BookGeneration[]>([]);
  const [libraryLoading, setLibraryLoading] = useState(true);
  const [libraryError, setLibraryError] = useState<string | null>(null);
  const [billingLoading, setBillingLoading] = useState(false);
  const [deletingBookId, setDeletingBookId] = useState<number | null>(null);
  const [accountDeleting, setAccountDeleting] = useState(false);

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
      <div className={ui.loadingCenter}>
        <div className={ui.spinnerLarge} />
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

  const deleteBook = async (bookId: number) => {
    if (!token || !window.confirm(t.deleteBookConfirm)) {
      return;
    }

    setDeletingBookId(bookId);

    try {
      const response = await fetch(`${apiBaseUrl}/books/${bookId}`, {
        method: "DELETE",
        headers: {
          Authorization: `Bearer ${token}`,
        },
      });
      const data = await response.json();

      if (!response.ok) {
        throw new Error(data.message || t.deleteBookError);
      }

      setBooks((current) => current.filter((book) => book.id !== bookId));
    } catch (err) {
      const message = err instanceof Error ? err.message : t.deleteBookError;
      alert(message);
    } finally {
      setDeletingBookId(null);
    }
  };

  const deleteAccount = async () => {
    if (!token || !window.confirm(t.deleteAccountConfirm)) {
      return;
    }

    setAccountDeleting(true);

    try {
      const response = await fetch(`${apiBaseUrl}/user`, {
        method: "DELETE",
        headers: {
          Authorization: `Bearer ${token}`,
          "Content-Type": "application/json",
        },
        body: JSON.stringify({ confirm: true }),
      });
      const data = await response.json();

      if (!response.ok) {
        throw new Error(data.message || t.deleteAccountError);
      }

      await logout();
    } catch (err) {
      const message = err instanceof Error ? err.message : t.deleteAccountError;
      alert(message);
      setAccountDeleting(false);
    }
  };

  return (
    <PageShell variant="landing">
      <div className={styles.mesh} aria-hidden="true" />
      <div className={styles.glassDecor} aria-hidden="true">
        <span className={styles.glassOrbA} />
        <span className={styles.glassOrbB} />
        <span className={styles.glassOrbC} />
      </div>

      <AppHeader brandName={t.brandName} homeHref="/dashboard" className={styles.dashboardChrome}>
        <div className={styles.headerSession}>
          {user.role === "admin" && (
            <Link href="/admin" className={`${ui.btnGhost} ${styles.headerAdminLink}`}>
              {t.openAdmin}
            </Link>
          )}
          <div className={styles.userChip}>
            {user.avatar_url ? (
              <Image
                src={user.avatar_url}
                alt={user.name}
                width={40}
                height={40}
                className={styles.avatar}
              />
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
          <button type="button" className={`${ui.btnGhost} ${styles.headerLogout}`} onClick={logout}>
            {t.signOut}
          </button>
        </div>
      </AppHeader>

      <PageShellMain variant="full">
        <div className={styles.dashboardLayout}>
          <section className={styles.heroRow}>
            <div className={styles.heroMain}>
              <p className={styles.welcomeEyebrow}>{t.dashboardEyebrow}</p>
              <h1>
                {t.welcomeBack}, {user.name.split(" ")[0]}!
              </h1>
              <p className={styles.heroSubtitle}>{t.letInspire}</p>
              <button
                type="button"
                className={`${ui.btnPrimary} ${ui.btnLarge} ${styles.createButton}`}
                onClick={() => router.push("/generate")}
              >
                {t.createNewStorybook}
              </button>
            </div>

            <aside className={styles.quotaPanel}>
              <h2 className={styles.quotaPanelTitle}>{t.monthlyUsageLimit}</h2>
              <div className={styles.quotaNumbers}>
                <span className={styles.quotaCurrent}>{monthlyUsage}</span>
                <span className={styles.quotaTotal}>
                  / {monthlyLimit} {t.books}
                </span>
              </div>
              <div className={styles.progressBarBg}>
                <div className={styles.progressBarFill} style={{ width: `${usagePercent}%` }} />
              </div>
              <p className={styles.quotaSub}>{t.quotaResets}</p>
              {!isPaid && (
                <button
                  type="button"
                  className={`${ui.btnPrimary} ${styles.quotaAction}`}
                  onClick={startCheckout}
                  disabled={billingLoading}
                >
                  {billingLoading ? t.billingRedirecting : t.upgradeToPremium}
                </button>
              )}
              {isPaid && (
                <button
                  type="button"
                  className={`${ui.btnGhost} ${styles.quotaAction}`}
                  onClick={openBillingPortal}
                  disabled={billingLoading}
                >
                  {billingLoading ? t.billingRedirecting : t.manageBilling}
                </button>
              )}
            </aside>
          </section>

          <section className={styles.librarySection}>
            <div className={styles.libraryHeader}>
              <h2 className={styles.libraryTitle}>{t.myLibraryTitle}</h2>
              {!libraryLoading && !libraryError && books.length > 0 && (
                <span className={styles.libraryCount}>
                  {books.length} {t.books}
                </span>
              )}
            </div>
            {libraryLoading && <p className={styles.libraryState}>{t.loading}</p>}
            {libraryError && <p className={styles.libraryError}>{libraryError}</p>}
            {!libraryLoading && !libraryError && books.length === 0 && (
              <div className={styles.emptyState}>
                <span className={styles.emptyIcon}>📚</span>
                <h3>{t.noBooksYet}</h3>
                <p>{t.noBooksDesc}</p>
                <button className={ui.btnPrimary} onClick={() => router.push("/generate")}>
                  {t.getFirstBook}
                </button>
              </div>
            )}
            {!libraryLoading && !libraryError && books.length > 0 && (
              <div className={styles.libraryGrid}>
                {books.map((book) => {
                  const coverImage = book.book_pages[0]?.image_url;
                  const title = book.book_template?.title ?? book.child_name;
                  const openLabel = `${t.readBook}: ${title}`;

                  return (
                    <article key={book.id} className={styles.bookCard}>
                      <button
                        type="button"
                        className={styles.bookOpen}
                        onClick={() => router.push(`/books/${book.id}`)}
                        aria-label={openLabel}
                      >
                        <span className={styles.bookSpine} aria-hidden="true" />
                        <span className={styles.bookCover}>
                          {coverImage ? (
                            <Image
                              src={coverImage}
                              alt=""
                              fill
                              className={styles.bookCoverImage}
                              sizes="(max-width: 768px) 200px, 280px"
                              unoptimized
                            />
                          ) : (
                            <span className={styles.bookCoverFallback} aria-hidden="true" />
                          )}
                          <span className={styles.bookPagesEdge} aria-hidden="true" />
                          <span className={styles.bookCoverScrim}>
                            <h3>{title}</h3>
                            <p>
                              {book.child_name} · {book.child_goal}
                            </p>
                          </span>
                        </span>
                      </button>
                      <button
                        type="button"
                        className={styles.bookDelete}
                        onClick={() => deleteBook(book.id)}
                        disabled={deletingBookId === book.id}
                        aria-label={t.deleteBook}
                      >
                        {deletingBookId === book.id ? (
                          <span className={styles.bookDeleteSpinner} aria-hidden="true" />
                        ) : (
                          <BookBasketIcon className={styles.bookDeleteIcon} />
                        )}
                      </button>
                    </article>
                  );
                })}
              </div>
            )}
          </section>

          <div className={styles.auxRow}>
            <div className={styles.miniPanel}>
              <h2 className={styles.miniPanelTitle}>{t.privacyConsentTitle}</h2>
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

            <div className={`${styles.miniPanel} ${styles.dangerPanel}`}>
              <h2 className={styles.miniPanelTitle}>{t.deleteAccountTitle}</h2>
              <p className={styles.dangerDesc}>{t.deleteAccountDesc}</p>
              <button
                type="button"
                className={`${ui.btnGhost} ${styles.dangerButton}`}
                onClick={deleteAccount}
                disabled={accountDeleting}
              >
                {accountDeleting ? t.deleting : t.deleteAccountButton}
              </button>
            </div>
          </div>
        </div>
      </PageShellMain>

      <AppFooter>
        &copy; {new Date().getFullYear()} {t.brandName}. {t.privacyBuiltIn}
      </AppFooter>
    </PageShell>
  );
}
