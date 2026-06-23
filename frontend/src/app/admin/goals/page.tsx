"use client";

import { FormEvent, useEffect, useState } from "react";
import { useAuth } from "@/app/context/AuthContext";
import { locales } from "@/app/context/locales";
import { createGoal, deleteGoal, fetchGoals, updateGoal } from "@/app/lib/adminApi";
import { StoryGoal } from "@/app/types/admin";
import styles from "../admin.module.css";

export default function AdminGoalsPage() {
  const { token, locale } = useAuth();
  const t = locales[locale] || locales.ru;
  const [items, setItems] = useState<StoryGoal[]>([]);
  const [name, setName] = useState("");
  const [description, setDescription] = useState("");
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    if (!token) {
      return;
    }

    let cancelled = false;

    (async () => {
      try {
        const data = await fetchGoals(token);
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
    const data = await fetchGoals(token);
    setItems(data.items);
  };

  const onSubmit = async (event: FormEvent) => {
    event.preventDefault();
    if (!token) {
      return;
    }
    setError(null);
    try {
      await createGoal(token, { name, description });
      setName("");
      setDescription("");
      await reload();
    } catch (err) {
      setError(err instanceof Error ? err.message : t.adminSaveError);
    }
  };

  const onDelete = async (id: number) => {
    if (!token || !window.confirm(t.adminDeleteConfirm)) {
      return;
    }
    await deleteGoal(token, id);
    await reload();
  };

  const onEdit = async (goal: StoryGoal) => {
    if (!token) {
      return;
    }
    const nextName = window.prompt(t.adminName, goal.name);
    if (!nextName) {
      return;
    }
    await updateGoal(token, goal.id, { name: nextName });
    await reload();
  };

  return (
    <section>
      <h1 className={styles.title}>{t.adminGoals}</h1>
      {error && <p className={styles.error}>{error}</p>}
      <form className={styles.form} onSubmit={onSubmit}>
        <label className={styles.label}>
          {t.adminName}
          <input className={styles.input} value={name} onChange={(e) => setName(e.target.value)} required />
        </label>
        <label className={styles.label}>
          {t.adminDescription}
          <textarea className={styles.textarea} value={description} onChange={(e) => setDescription(e.target.value)} />
        </label>
        <button className={styles.button} type="submit">{t.adminCreate}</button>
      </form>
      <table className={styles.table}>
        <thead>
          <tr>
            <th>{t.adminName}</th>
            <th>{t.adminDescription}</th>
            <th>{t.adminActions}</th>
          </tr>
        </thead>
        <tbody>
          {items.map((goal) => (
            <tr key={goal.id}>
              <td>{goal.name}</td>
              <td>{goal.description}</td>
              <td className={styles.actions}>
                <button type="button" className={`${styles.button} ${styles.buttonSecondary}`} onClick={() => onEdit(goal)}>
                  {t.adminEdit}
                </button>
                <button type="button" className={`${styles.button} ${styles.buttonDanger}`} onClick={() => onDelete(goal.id)}>
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
