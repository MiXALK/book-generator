"use client";

import { useEffect, useState } from "react";
import { useParams } from "next/navigation";
import BookPageView from "@/app/components/BookPageView";
import { useAuth } from "@/app/context/AuthContext";
import { locales } from "@/app/context/locales";
import { fetchPreview } from "@/app/lib/adminApi";
import { ContentPreview } from "@/app/types/admin";
import { BookPage } from "@/app/types/book";
import styles from "../../../admin.module.css";

export default function AdminPreviewPage() {
  const params = useParams<{ type: string; id: string }>();
  const { token, locale } = useAuth();
  const t = locales[locale] || locales.ru;
  const [preview, setPreview] = useState<ContentPreview | null>(null);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    if (!token || !params?.type || !params?.id) {
      return;
    }

    fetchPreview(token, params.type, Number(params.id))
      .then(setPreview)
      .catch((err: Error) => setError(err.message));
  }, [token, params]);

  if (error) {
    return <p className={styles.error}>{error}</p>;
  }

  if (!preview) {
    return <p className={styles.loading}>{t.loading}</p>;
  }

  const pages: BookPage[] = preview.pages.map((page, index) => ({
    id: index + 1,
    page_number: page.page_number,
    text: page.text,
    image_url: page.image_url,
    layout_template: page.layout_template
      ? {
          id: page.layout_template.id ?? 0,
          key: page.layout_template.key,
          category: page.layout_template.category,
          ratio_profile: page.layout_template.ratio_profile,
          text_position: page.layout_template.text_position as "top" | "bottom" | "left" | "right",
        }
      : null,
  }));

  return (
    <section>
      <h1 className={styles.title}>{t.adminPreview}: {preview.title}</h1>
      {preview.description && <p>{preview.description}</p>}
      {preview.prompt_text && (
        <pre className={styles.card}>{preview.prompt_text}</pre>
      )}
      <div className={styles.previewStack}>
        {pages.map((page) => (
          <BookPageView key={page.id} page={page} />
        ))}
      </div>
    </section>
  );
}
