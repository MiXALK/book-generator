"use client";

import { useEffect, useState } from "react";
import { useParams, useRouter } from "next/navigation";
import BookReader from "@/app/components/BookReader";
import btn from "@/app/components/storyButton.module.css";
import { useAuth } from "@/app/context/AuthContext";
import { locales } from "@/app/context/locales";
import { BookGeneration } from "@/app/types/book";
import styles from "./book.module.css";

export default function BookReaderPage() {
  const router = useRouter();
  const params = useParams();
  const bookId = Number(params.id);
  const { token, user, loading, locale } = useAuth();
  const t = locales[locale] || locales.ru;
  const apiBaseUrl = process.env.NEXT_PUBLIC_API_BASE_URL || "http://localhost:8000/api";

  const [generation, setGeneration] = useState<BookGeneration | null>(null);
  const [error, setError] = useState<string | null>(null);
  const [fetching, setFetching] = useState(true);

  useEffect(() => {
    if (!loading && !user) {
      router.push("/");
    }
  }, [loading, router, user]);

  useEffect(() => {
    if (!token || Number.isNaN(bookId)) {
      return;
    }

    const loadBook = async () => {
      setFetching(true);
      setError(null);

      try {
        const response = await fetch(`${apiBaseUrl}/books/${bookId}`, {
          headers: {
            Authorization: `Bearer ${token}`,
          },
        });
        const data = await response.json();

        if (!response.ok) {
          throw new Error(data.message || t.bookLoadError);
        }

        setGeneration(data.generation);
      } catch (err) {
        const message = err instanceof Error ? err.message : t.bookLoadError;
        setError(message);
      } finally {
        setFetching(false);
      }
    };

    loadBook();
  }, [apiBaseUrl, bookId, t.bookLoadError, token]);

  if (loading || fetching) {
    return (
      <div className={styles.state}>
        <div className={styles.spinner} />
        <p>{t.loadingBook}</p>
      </div>
    );
  }

  if (error || !generation) {
    return (
      <div className={styles.state}>
        <p>{error || t.bookLoadError}</p>
        <button type="button" className={btn.btnPrimary} onClick={() => router.push("/dashboard")}>
          {t.backToDashboard}
        </button>
      </div>
    );
  }

  return (
    <BookReader
      generation={generation}
      labels={{
        closeReader: t.closeReader,
        turnPageHint: t.turnPageHint,
        dismissHint: t.dismissHint,
        pageAnnouncement: t.pageAnnouncement,
      }}
      onClose={() => router.push("/dashboard")}
    />
  );
}
