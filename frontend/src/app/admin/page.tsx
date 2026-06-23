"use client";

import { useEffect, useState } from "react";
import Link from "next/link";
import { useAuth } from "@/app/context/AuthContext";
import { locales } from "@/app/context/locales";
import { fetchReviewQueue } from "@/app/lib/adminApi";
import { ReviewQueueItem } from "@/app/types/admin";
import styles from "./admin.module.css";

export default function AdminDashboardPage() {
  const { token, locale } = useAuth();
  const t = locales[locale] || locales.ru;
  const [items, setItems] = useState<ReviewQueueItem[]>([]);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    if (!token) {
      return;
    }

    let cancelled = false;

    (async () => {
      try {
        const data = await fetchReviewQueue(token);
        if (!cancelled) {
          setItems(data.items);
        }
      } catch (err) {
        if (!cancelled) {
          setError(err instanceof Error ? err.message : "Failed to load review queue.");
        }
      }
    })();

    return () => {
      cancelled = true;
    };
  }, [token]);

  const previewPath = (item: ReviewQueueItem) => {
    if (item.type === "book_template") {
      return `/admin/preview/templates/${item.id}`;
    }
    if (item.type === "story_prompt") {
      return `/admin/preview/prompts/${item.id}`;
    }
    return `/admin/preview/layouts/${item.id}`;
  };

  return (
    <section>
      <h1 className={styles.title}>{t.adminDashboard}</h1>
      {error && <p className={styles.error}>{error}</p>}
      <div className={styles.card}>
        <h2>{t.adminReviewQueue}</h2>
        {items.length === 0 ? (
          <p>{t.adminReviewEmpty}</p>
        ) : (
          <table className={styles.table}>
            <thead>
              <tr>
                <th>{t.adminType}</th>
                <th>{t.adminName}</th>
                <th>{t.adminActions}</th>
              </tr>
            </thead>
            <tbody>
              {items.map((item) => (
                <tr key={`${item.type}-${item.id}`}>
                  <td>{item.type}</td>
                  <td>{item.title}</td>
                  <td>
                    <Link href={previewPath(item)} className={styles.navLink}>
                      {t.adminPreview}
                    </Link>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        )}
      </div>
    </section>
  );
}
