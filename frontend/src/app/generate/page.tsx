"use client";

import { FormEvent, useEffect, useState } from "react";
import { useRouter } from "next/navigation";
import { useAuth } from "@/app/context/AuthContext";
import { locales } from "@/app/context/locales";
import styles from "./generate.module.css";

interface CatalogGoal {
  id: number;
  name: string;
}

interface CatalogAgeRange {
  value: string;
  min_age: number;
  max_age: number;
}

export default function GeneratePage() {
  const router = useRouter();
  const { token, user, loading, locale } = useAuth();
  const t = locales[locale] || locales.ru;
  const apiBaseUrl = process.env.NEXT_PUBLIC_API_BASE_URL || "http://localhost:8000/api";

  const [goals, setGoals] = useState<CatalogGoal[]>([]);
  const [ageRanges, setAgeRanges] = useState<CatalogAgeRange[]>([]);
  const [fetching, setFetching] = useState(true);
  const [submitting, setSubmitting] = useState(false);
  const [message, setMessage] = useState<string | null>(null);
  const [error, setError] = useState<string | null>(null);

  const [childName, setChildName] = useState("");
  const [selectedGoal, setSelectedGoal] = useState("");
  const [selectedAgeRange, setSelectedAgeRange] = useState("");

  useEffect(() => {
    if (!loading && !user) {
      router.push("/");
    }
  }, [loading, router, user]);

  useEffect(() => {
    if (!token) {
      return;
    }

    const loadCatalog = async () => {
      setFetching(true);
      setError(null);

      try {
        const response = await fetch(`${apiBaseUrl}/templates/catalog`, {
          headers: {
            Authorization: `Bearer ${token}`,
          },
        });

        if (!response.ok) {
          throw new Error(t.catalogLoadError);
        }

        const data = await response.json();
        setGoals(data.goals ?? []);
        setAgeRanges(data.age_ranges ?? []);
      } catch (err) {
        const msg = err instanceof Error ? err.message : t.genericGenerateError;
        setError(msg);
      } finally {
        setFetching(false);
      }
    };

    loadCatalog();
  }, [apiBaseUrl, t.catalogLoadError, t.genericGenerateError, token]);

  const submitGeneration = async (event: FormEvent<HTMLFormElement>) => {
    event.preventDefault();
    if (!token || !selectedGoal || !selectedAgeRange || !childName.trim()) {
      return;
    }

    const selectedRange = ageRanges.find((ageRange) => ageRange.value === selectedAgeRange);
    const childAge = selectedRange?.min_age ?? 5;

    setSubmitting(true);
    setMessage(null);
    setError(null);

    try {
      const response = await fetch(`${apiBaseUrl}/books/generate`, {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
          Authorization: `Bearer ${token}`,
        },
        body: JSON.stringify({
          child_name: childName.trim(),
          age: childAge,
          goal: selectedGoal,
        }),
      });

      const data = await response.json();

      if (!response.ok) {
        throw new Error(data.message || t.genericGenerateError);
      }

      const generationId = data.generation?.id;

      if (typeof generationId === "number") {
        router.push(`/books/${generationId}/status`);
        return;
      }

      setMessage(t.generateSuccess);
    } catch (err) {
      const msg = err instanceof Error ? err.message : t.genericGenerateError;
      setError(msg);
    } finally {
      setSubmitting(false);
    }
  };

  if (loading || !user) {
    return (
      <div className={styles.state}>
        <p>{t.loading}</p>
      </div>
    );
  }

  return (
    <main className={styles.page}>
      <div className={styles.header}>
        <h1>{t.generateTitle}</h1>
        <p>{t.generateSubtitle}</p>
      </div>

      <form className={styles.form} onSubmit={submitGeneration}>
        <label className={styles.field}>
          <span>{t.childNameLabel}</span>
          <input
            value={childName}
            onChange={(event) => setChildName(event.target.value)}
            placeholder={t.childNamePlaceholder}
            maxLength={120}
            required
          />
        </label>

        <label className={styles.field}>
          <span>{t.goalLabel}</span>
          <select value={selectedGoal} onChange={(event) => setSelectedGoal(event.target.value)} required>
            <option value="">{t.selectOption}</option>
            {goals.map((goal) => (
              <option key={goal.id} value={goal.name}>
                {goal.name}
              </option>
            ))}
          </select>
        </label>

        <label className={styles.field}>
          <span>{t.ageRangeLabel}</span>
          <select value={selectedAgeRange} onChange={(event) => setSelectedAgeRange(event.target.value)} required>
            <option value="">{t.selectOption}</option>
            {ageRanges.map((ageRange) => (
              <option key={ageRange.value} value={ageRange.value}>
                {t.ageRangeLabels[ageRange.value as keyof typeof t.ageRangeLabels] ?? ageRange.value}
              </option>
            ))}
          </select>
        </label>

        <button type="submit" disabled={fetching || submitting} className={styles.submit}>
          {submitting ? t.generating : t.generateButton}
        </button>
      </form>

      {fetching && <p className={styles.state}>{t.loading}</p>}
      {message && <p className={styles.success}>{message}</p>}
      {error && <p className={styles.error}>{error}</p>}
    </main>
  );
}
