"use client";

import { useEffect, useState } from "react";
import { useParams, useRouter } from "next/navigation";
import { useAuth } from "@/app/context/AuthContext";
import { locales } from "@/app/context/locales";
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

  useEffect(() => {
    if (!loading && !user) {
      router.push("/");
    }
  }, [loading, router, user]);

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

        if (generation.status === "completed") {
          router.replace(`/books/${bookId}`);
          return;
        }

        if (generation.status === "failed") {
          setError(t.bookGenerationFailed);
          return;
        }

        setMessage(t.assemblingBook);
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
  }, [apiBaseUrl, bookId, router, t.assemblingBook, t.bookGenerationFailed, t.bookStatusError, t.preparingBook, token]);

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
        <button type="button" onClick={() => router.push("/dashboard")}>
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
    </div>
  );
}
