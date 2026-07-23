"use client";

import Link from "next/link";
import { FormEvent, useEffect, useState } from "react";
import { useAuth } from "@/app/context/AuthContext";
import { locales } from "@/app/context/locales";
import {
  createPrompt,
  deletePrompt,
  fetchGoals,
  fetchPrompts,
  publishPrompt,
  ratePrompt,
  submitPromptReview,
} from "@/app/lib/adminApi";
import { StoryGoal, StoryPrompt } from "@/app/types/admin";
import { AdminDeleteIcon, AdminPublishIcon } from "../AdminActionIcons";
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

export default function AdminPromptsPage() {
  const { token, locale } = useAuth();
  const t = locales[locale] || locales.ru;
  const [items, setItems] = useState<StoryPrompt[]>([]);
  const [goals, setGoals] = useState<StoryGoal[]>([]);
  const [promptText, setPromptText] = useState("");
  const [language, setLanguage] = useState("ru");
  const [storyGoalId, setStoryGoalId] = useState("");
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    if (!token) {
      return;
    }

    let cancelled = false;

    (async () => {
      try {
        const [promptsData, goalsData] = await Promise.all([fetchPrompts(token), fetchGoals(token)]);
        if (!cancelled) {
          setItems(promptsData.items);
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
    const [promptsData, goalsData] = await Promise.all([fetchPrompts(token), fetchGoals(token)]);
    setItems(promptsData.items);
    setGoals(goalsData.items);
  };

  const onSubmit = async (event: FormEvent) => {
    event.preventDefault();
    if (!token) {
      return;
    }
    setError(null);
    try {
      await createPrompt(token, {
        prompt_text: promptText,
        language,
        story_goal_id: storyGoalId ? Number(storyGoalId) : null,
      });
      setPromptText("");
      await reload();
    } catch (err) {
      setError(err instanceof Error ? err.message : t.adminSaveError);
    }
  };

  const onRate = async (id: number) => {
    if (!token) {
      return;
    }
    const rating = Number(window.prompt(t.adminRatingPrompt, "5"));
    if (!rating) {
      return;
    }
    await ratePrompt(token, id, rating);
    await reload();
  };

  return (
    <section>
      <h1 className={styles.title}>{t.adminPrompts}</h1>
      {error && <p className={styles.error}>{error}</p>}
      <form className={styles.form} onSubmit={onSubmit}>
        <label className={styles.label}>
          {t.adminPromptText}
          <textarea className={styles.textarea} value={promptText} onChange={(e) => setPromptText(e.target.value)} required />
        </label>
        <label className={styles.label}>
          {t.adminLanguage}
          <select className={styles.select} value={language} onChange={(e) => setLanguage(e.target.value)}>
            <option value="ru">RU</option>
            <option value="en">EN</option>
          </select>
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
        <button className={styles.button} type="submit">{t.adminCreate}</button>
      </form>
      <table className={styles.table}>
        <thead>
          <tr>
            <th>{t.adminGoal}</th>
            <th>{t.adminLanguage}</th>
            <th>{t.adminQualityScore}</th>
            <th>{t.adminStatus}</th>
            <th>{t.adminActions}</th>
          </tr>
        </thead>
        <tbody>
          {items.map((item) => (
            <tr key={item.id}>
              <td>{item.story_goal?.name ?? `Prompt #${item.id}`}</td>
              <td>{item.language}</td>
              <td>{item.quality_score} ({item.rating_count})</td>
              <td>
                <span className={`${styles.status} ${statusClass(item.publication_status)}`}>
                  {item.publication_status}
                </span>
              </td>
              <td className={styles.actions}>
                <Link href={`/admin/preview/prompts/${item.id}`} className={styles.navLink}>{t.adminPreview}</Link>
                <button type="button" className={`${styles.button} ${styles.buttonSecondary}`} onClick={() => onRate(item.id)}>
                  {t.adminRate}
                </button>
                <button type="button" className={`${styles.button} ${styles.buttonSecondary}`} onClick={() => token && submitPromptReview(token, item.id).then(reload)}>
                  {t.adminSubmitReview}
                </button>
                <button
                  type="button"
                  className={`${styles.button} ${styles.iconButton}`}
                  onClick={() => {
                    if (!token || !window.confirm(t.adminPublishConfirm)) {
                      return;
                    }
                    void publishPrompt(token, item.id)
                      .then(reload)
                      .catch((err: Error) => setError(err.message));
                  }}
                  aria-label={t.adminPublish}
                  title={t.adminPublish}
                >
                  <AdminPublishIcon />
                </button>
                <button
                  type="button"
                  className={`${styles.button} ${styles.buttonDanger} ${styles.iconButton}`}
                  onClick={() => token && deletePrompt(token, item.id).then(reload)}
                  aria-label={t.adminDelete}
                  title={t.adminDelete}
                >
                  <AdminDeleteIcon />
                </button>
              </td>
            </tr>
          ))}
        </tbody>
      </table>
    </section>
  );
}
