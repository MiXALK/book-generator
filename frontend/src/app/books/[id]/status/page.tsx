"use client";

import { useEffect, useState } from "react";
import { useParams, useRouter } from "next/navigation";
import { useAuth } from "@/app/context/AuthContext";
import { locales } from "@/app/context/locales";
import btn from "@/app/components/storyButton.module.css";
import { BookGeneration } from "@/app/types/book";
import styles from "./status.module.css";

export default function BookStatusPage() {
  const router = useRouter();
  const params = useParams();
  const bookId = Number(params.id);
  const { token, user, loading, locale } = useAuth();
  const t = locales[locale] || locales.ru;
  const apiBaseUrl = process.env.NEXT_PUBLIC_API_BASE_URL || "http://localhost:8000/api";

  const [message, setMessage] = useState(t.preparingBook);
  const [error, setError] = useState<string | null>(null);
  const [canRetryIllustrations, setCanRetryIllustrations] = useState(false);
  const [canReadBook, setCanReadBook] = useState(false);
  const [retrying, setRetrying] = useState(false);

  useEffect(() => {
    if (!loading && !user) {
      router.push("/");
    }
  }, [loading, router, user]);

  const retryIllustrations = async () => {
    if (!token || Number.isNaN(bookId)) {
      return;
    }

    setRetrying(true);
    setError(null);
    setCanRetryIllustrations(false);
    setMessage(t.assemblingBook);

    try {
      const response = await fetch(`${apiBaseUrl}/books/${bookId}/retry-illustrations`, {
        method: "POST",
        headers: {
          Authorization: `Bearer ${token}`,
        },
      });

      const data = await response.json();

      if (!response.ok) {
        throw new Error(data.message || t.bookStatusError);
      }
    } catch (err) {
      const statusMessage = err instanceof Error ? err.message : t.bookStatusError;
      setError(statusMessage);
    } finally {
      setRetrying(false);
    }
  };

  useEffect(() => {
    if (!token || Number.isNaN(bookId)) {
      return;
    }

    let cancelled = false;

    const pollStatus = async () => {
      try {
        const response = await fetch(`${apiBaseUrl}/books/${bookId}`, {
          headers: {
            Authorization: `Bearer ${token}`,
          },
        });
        const data = await response.json();

        if (!response.ok) {
          throw new Error(data.message || t.bookStatusError);
        }

        const generation = data.generation as BookGeneration;

        if (cancelled) {
          return;
        }

        setCanReadBook((generation.book_pages?.length ?? 0) > 0);

        if (generation.status === "completed") {
          router.replace(`/books/${bookId}`);
          return;
        }

        if (generation.illustration_status === "failed") {
          setError(generation.error_message || t.illustrationFailed);
          setCanRetryIllustrations(true);

          return;
        }

        if (generation.status === "failed") {
          setError(t.bookGenerationFailed);

          return;
        }

        setMessage(
          generation.illustration_status === "processing" || generation.illustration_status === "queued"
            ? t.assemblingBook
            : t.preparingBook,
        );
        window.setTimeout(pollStatus, 800);
      } catch (err) {
        if (!cancelled) {
          const statusMessage = err instanceof Error ? err.message : t.bookStatusError;
          setError(statusMessage);
        }
      }
    };

    pollStatus();

    return () => {
      cancelled = true;
    };
  }, [
    apiBaseUrl,
    bookId,
    retrying,
    router,
    t.assemblingBook,
    t.bookGenerationFailed,
    t.bookStatusError,
    t.illustrationFailed,
    t.preparingBook,
    token,
  ]);

  if (loading) {
    return (
      <div className={styles.state}>
        <p>{t.loading}</p>
      </div>
    );
  }

  if (error) {
    return (
      <div className={styles.state}>
        <p className={styles.error}>{error}</p>
        {canRetryIllustrations && (
          <button type="button" className={btn.btnPrimary} onClick={retryIllustrations} disabled={retrying}>
            {retrying ? t.generating : t.retryIllustrations}
          </button>
        )}
        {canReadBook && (
          <button type="button" className={btn.btnPrimary} onClick={() => router.push(`/books/${bookId}`)}>
            {t.readBook}
          </button>
        )}
        <button type="button" className={btn.btnPrimary} onClick={() => router.push("/dashboard")}>
          {t.backToDashboard}
        </button>
      </div>
    );
  }

  return (
    <div className={styles.state}>
      <div className={styles.spinner} />
      <h1>{t.generationStatusTitle}</h1>
      <p>{message}</p>
      {canReadBook && (
        <button type="button" className={btn.btnPrimary} onClick={() => router.push(`/books/${bookId}`)}>
          {t.readBook}
        </button>
      )}
    </div>
  );
}
