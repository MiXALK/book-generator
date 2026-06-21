"use client";

import Link from "next/link";
import { FormEvent, useEffect, useState } from "react";
import { useAuth } from "@/app/context/AuthContext";
import { locales } from "@/app/context/locales";
import {
  createTemplate,
  deleteTemplate,
  fetchGoals,
  fetchTemplates,
  publishTemplate,
  submitTemplateReview,
  updateTemplate,
} from "@/app/lib/adminApi";
import { BookTemplate, StoryGoal } from "@/app/types/admin";
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

export default function AdminTemplatesPage() {
  const { token, locale } = useAuth();
  const t = locales[locale] || locales.ru;
  const [items, setItems] = useState<BookTemplate[]>([]);
  const [goals, setGoals] = useState<StoryGoal[]>([]);
  const [title, setTitle] = useState("");
  const [description, setDescription] = useState("");
  const [isFree, setIsFree] = useState(true);
  const [storyGoalId, setStoryGoalId] = useState("");
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    if (!token) {
      return;
    }

    let cancelled = false;

    (async () => {
      try {
        const [templatesData, goalsData] = await Promise.all([fetchTemplates(token), fetchGoals(token)]);
        if (!cancelled) {
          setItems(templatesData.items);
          setGoals(goalsData.items);
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
    const [templatesData, goalsData] = await Promise.all([fetchTemplates(token), fetchGoals(token)]);
    setItems(templatesData.items);
    setGoals(goalsData.items);
  };

  const onSubmit = async (event: FormEvent) => {
    event.preventDefault();
    if (!token) {
      return;
    }
    setError(null);
    try {
      await createTemplate(token, {
        title,
        description,
        is_free: isFree,
        template_type: "story",
        story_goal_id: storyGoalId ? Number(storyGoalId) : null,
      });
      setTitle("");
      setDescription("");
      await reload();
    } catch (err) {
      setError(err instanceof Error ? err.message : t.adminSaveError);
    }
  };

  return (
    <section>
      <h1 className={styles.title}>{t.adminTemplates}</h1>
      {error && <p className={styles.error}>{error}</p>}
      <form className={styles.form} onSubmit={onSubmit}>
        <label className={styles.label}>
          {t.adminTitleLabel}
          <input className={styles.input} value={title} onChange={(e) => setTitle(e.target.value)} required />
        </label>
        <label className={styles.label}>
          {t.adminDescription}
          <textarea className={styles.textarea} value={description} onChange={(e) => setDescription(e.target.value)} />
        </label>
        <label className={styles.label}>
          {t.adminGoal}
          <select className={styles.select} value={storyGoalId} onChange={(e) => setStoryGoalId(e.target.value)}>
            <option value="">{t.selectOption}</option>
            {goals.map((goal) => (
              <option key={goal.id} value={goal.id}>{goal.name}</option>
            ))}
          </select>
        </label>
        <label className={styles.label}>
          <input type="checkbox" checked={isFree} onChange={(e) => setIsFree(e.target.checked)} />
          {t.adminFreeTier}
        </label>
        <button className={styles.button} type="submit">{t.adminCreate}</button>
      </form>
      <table className={styles.table}>
        <thead>
          <tr>
            <th>{t.adminTitleLabel}</th>
            <th>{t.adminStatus}</th>
            <th>{t.adminFreeTier}</th>
            <th>{t.adminActions}</th>
          </tr>
        </thead>
        <tbody>
          {items.map((item) => (
            <tr key={item.id}>
              <td>{item.title}</td>
              <td>
                <span className={`${styles.status} ${statusClass(item.publication_status)}`}>
                  {item.publication_status}
                </span>
              </td>
              <td>{item.is_free ? t.freeTier : t.premiumSubscriber}</td>
              <td className={styles.actions}>
                <Link href={`/admin/preview/templates/${item.id}`} className={styles.navLink}>{t.adminPreview}</Link>
                <button
                  type="button"
                  className={`${styles.button} ${styles.buttonSecondary}`}
                  onClick={() => token && submitTemplateReview(token, item.id).then(reload)}
                >
                  {t.adminSubmitReview}
                </button>
                <button
                  type="button"
                  className={styles.button}
                  onClick={() => token && publishTemplate(token, item.id).then(reload)}
                >
                  {t.adminPublish}
                </button>
                <button
                  type="button"
                  className={`${styles.button} ${styles.buttonSecondary}`}
                  onClick={() => token && updateTemplate(token, item.id, { is_free: !item.is_free }).then(reload)}
                >
                  {t.adminToggleFree}
                </button>
                <button
                  type="button"
                  className={`${styles.button} ${styles.buttonDanger}`}
                  onClick={() => token && deleteTemplate(token, item.id).then(reload)}
                >
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
