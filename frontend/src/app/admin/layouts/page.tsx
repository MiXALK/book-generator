"use client";

import Link from "next/link";
import { FormEvent, useEffect, useState } from "react";
import { useAuth } from "@/app/context/AuthContext";
import { locales } from "@/app/context/locales";
import {
  createLayout,
  deleteLayout,
  fetchLayouts,
  publishLayout,
  submitLayoutReview,
} from "@/app/lib/adminApi";
import { LayoutTemplate } from "@/app/types/admin";
import styles from "../admin.module.css";

function statusClass(status: string) {
  if (status === "published") {
    return styles.statusPublished;
  }
  if (status === "pending_review") {
    return styles.statusPending;
  }
  return styles.statusDraft;
}

export default function AdminLayoutsPage() {
  const { token, locale } = useAuth();
  const t = locales[locale] || locales.ru;
  const [items, setItems] = useState<LayoutTemplate[]>([]);
  const [key, setKey] = useState("");
  const [title, setTitle] = useState("");
  const [category, setCategory] = useState("content");
  const [textPosition, setTextPosition] = useState("bottom");
  const [sortOrder, setSortOrder] = useState("0");
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    if (!token) {
      return;
    }

    let cancelled = false;

    (async () => {
      try {
        const data = await fetchLayouts(token);
        if (!cancelled) {
          setItems(data.items);
        }
      } catch (err) {
        if (!cancelled) {
          setError(err instanceof Error ? err.message : t.adminSaveError);
        }
      }
    })();

    return () => {
      cancelled = true;
    };
  }, [token, t.adminSaveError]);

  const reload = async () => {
    if (!token) {
      return;
    }
    const data = await fetchLayouts(token);
    setItems(data.items);
  };

  const onSubmit = async (event: FormEvent) => {
    event.preventDefault();
    if (!token) {
      return;
    }
    setError(null);
    try {
      await createLayout(token, {
        key,
        title,
        category,
        ratio_profile: "80_20",
        text_position: textPosition,
        sort_order: Number(sortOrder),
      });
      setKey("");
      setTitle("");
      await reload();
    } catch (err) {
      setError(err instanceof Error ? err.message : t.adminSaveError);
    }
  };

  return (
    <section>
      <h1 className={styles.title}>{t.adminLayouts}</h1>
      {error && <p className={styles.error}>{error}</p>}
      <form className={styles.form} onSubmit={onSubmit}>
        <label className={styles.label}>
          Key
          <input className={styles.input} value={key} onChange={(e) => setKey(e.target.value)} required />
        </label>
        <label className={styles.label}>
          {t.adminTitleLabel}
          <input className={styles.input} value={title} onChange={(e) => setTitle(e.target.value)} required />
        </label>
        <label className={styles.label}>
          Category
          <select className={styles.select} value={category} onChange={(e) => setCategory(e.target.value)}>
            <option value="cover">cover</option>
            <option value="content">content</option>
            <option value="ending">ending</option>
          </select>
        </label>
        <label className={styles.label}>
          Text position
          <select className={styles.select} value={textPosition} onChange={(e) => setTextPosition(e.target.value)}>
            <option value="top">top</option>
            <option value="bottom">bottom</option>
            <option value="left">left</option>
            <option value="right">right</option>
            <option value="overlay">overlay</option>
          </select>
        </label>
        <label className={styles.label}>
          Sort order
          <input className={styles.input} type="number" value={sortOrder} onChange={(e) => setSortOrder(e.target.value)} />
        </label>
        <button className={styles.button} type="submit">{t.adminCreate}</button>
      </form>
      <table className={styles.table}>
        <thead>
          <tr>
            <th>{t.adminTitleLabel}</th>
            <th>Category</th>
            <th>{t.adminStatus}</th>
            <th>{t.adminActions}</th>
          </tr>
        </thead>
        <tbody>
          {items.map((item) => (
            <tr key={item.id}>
              <td>{item.title}</td>
              <td>{item.category}</td>
              <td>
                <span className={`${styles.status} ${statusClass(item.publication_status)}`}>
                  {item.publication_status}
                </span>
              </td>
              <td className={styles.actions}>
                <Link href={`/admin/preview/layouts/${item.id}`} className={styles.navLink}>{t.adminPreview}</Link>
                <button type="button" className={`${styles.button} ${styles.buttonSecondary}`} onClick={() => token && submitLayoutReview(token, item.id).then(reload)}>
                  {t.adminSubmitReview}
                </button>
                <button type="button" className={styles.button} onClick={() => token && publishLayout(token, item.id).then(reload)}>
                  {t.adminPublish}
                </button>
                <button type="button" className={`${styles.button} ${styles.buttonDanger}`} onClick={() => token && deleteLayout(token, item.id).then(reload)}>
                  {t.adminDelete}
                </button>
              </td>
            </tr>
          ))}
        </tbody>
      </table>
    </section>
  );
}
