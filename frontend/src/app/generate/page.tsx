"use client";

import { FormEvent, useEffect, useMemo, useState } from "react";
import { useRouter } from "next/navigation";
import { useAuth } from "@/app/context/AuthContext";
import { locales } from "@/app/context/locales";
import styles from "./generate.module.css";

interface CatalogGoal {
  id: number;
  name: string;
}

interface CatalogAgeRange {
  id: number;
  label: string;
}

interface CatalogTemplate {
  id: number;
  title: string;
  description: string | null;
  is_free: boolean;
}

export default function GeneratePage() {
  const router = useRouter();
  const { token, user, loading, locale } = useAuth();
  const t = locales[locale] || locales.ru;
  const apiBaseUrl = process.env.NEXT_PUBLIC_API_BASE_URL || "http://localhost:8000/api";

  const [goals, setGoals] = useState<CatalogGoal[]>([]);
  const [ageRanges, setAgeRanges] = useState<CatalogAgeRange[]>([]);
  const [templates, setTemplates] = useState<CatalogTemplate[]>([]);
  const [fetching, setFetching] = useState(true);
  const [submitting, setSubmitting] = useState(false);
  const [message, setMessage] = useState<string | null>(null);
  const [error, setError] = useState<string | null>(null);

  const [childName, setChildName] = useState("");
  const [selectedGoal, setSelectedGoal] = useState("");
  const [selectedAgeRange, setSelectedAgeRange] = useState("");
  const [selectedTemplate, setSelectedTemplate] = useState("");

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
        setTemplates(data.templates ?? []);
      } catch (err) {
        const msg = err instanceof Error ? err.message : t.genericGenerateError;
        setError(msg);
      } finally {
        setFetching(false);
      }
    };

    loadCatalog();
  }, [apiBaseUrl, t.catalogLoadError, t.genericGenerateError, token]);

  const selectedTemplateItem = useMemo(() => {
    return templates.find((template) => String(template.id) === selectedTemplate);
  }, [selectedTemplate, templates]);

  const submitGeneration = async (event: FormEvent<HTMLFormElement>) => {
    event.preventDefault();
    if (!token || !selectedGoal || !selectedAgeRange || !selectedTemplate || !childName.trim()) {
      return;
    }

    const ageDigits = selectedAgeRange.match(/\d+/);
    const childAge = ageDigits ? Number(ageDigits[0]) : 5;

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
          book_template_id: Number(selectedTemplate),
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
              <option key={ageRange.id} value={ageRange.label}>
                {ageRange.label}
              </option>
            ))}
          </select>
        </label>

        <label className={styles.field}>
          <span>{t.templateLabel}</span>
          <select value={selectedTemplate} onChange={(event) => setSelectedTemplate(event.target.value)} required>
            <option value="">{t.selectOption}</option>
            {templates.map((template) => (
              <option key={template.id} value={template.id}>
                {template.title}
              </option>
            ))}
          </select>
        </label>

        {selectedTemplateItem && (
          <div className={styles.templateMeta}>
            <p>{selectedTemplateItem.description}</p>
            {!selectedTemplateItem.is_free && user.plan === "free" && (
              <p className={styles.warning}>{t.paidTemplateWarning}</p>
            )}
          </div>
        )}

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
