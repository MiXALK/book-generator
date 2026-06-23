"use client";

import { ChangeEvent, FormEvent, useEffect, useState, useSyncExternalStore } from "react";
import { useRouter } from "next/navigation";
import { useAuth } from "@/app/context/AuthContext";
import { locales } from "@/app/context/locales";
import btn from "@/app/components/storyButton.module.css";
import styles from "./generate.module.css";

interface CatalogGoal {
  id: number;
  name: string;
  is_locked?: boolean;
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
  const hydrated = useSyncExternalStore(
    () => () => {},
    () => true,
    () => false,
  );

  const [goals, setGoals] = useState<CatalogGoal[]>([]);
  const [ageRanges, setAgeRanges] = useState<CatalogAgeRange[]>([]);
  const [hasPaidAccess, setHasPaidAccess] = useState(false);
  const [fetching, setFetching] = useState(true);
  const [submitting, setSubmitting] = useState(false);
  const [uploadingPhoto, setUploadingPhoto] = useState(false);
  const [message, setMessage] = useState<string | null>(null);
  const [error, setError] = useState<string | null>(null);

  const [childName, setChildName] = useState("");
  const [selectedGoal, setSelectedGoal] = useState("");
  const [selectedAgeRange, setSelectedAgeRange] = useState("");
  const [parentalConsent, setParentalConsent] = useState(false);
  const [photoFile, setPhotoFile] = useState<File | null>(null);
  const [uploadedPhotoId, setUploadedPhotoId] = useState<number | null>(null);

  const selectedGoalMeta = goals.find((goal) => goal.name === selectedGoal);
  const selectedGoalLocked = selectedGoalMeta?.is_locked === true;
  const isPaid = user?.plan === "paid" && user?.subscription_status === "active";

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
        setHasPaidAccess(data.has_paid_access === true);
      } catch (err) {
        const msg = err instanceof Error ? err.message : t.genericGenerateError;
        setError(msg);
      } finally {
        setFetching(false);
      }
    };

    loadCatalog();
  }, [apiBaseUrl, t.catalogLoadError, t.genericGenerateError, token]);

  const handlePhotoChange = (event: ChangeEvent<HTMLInputElement>) => {
    const file = event.target.files?.[0] ?? null;
    setPhotoFile(file);
    setUploadedPhotoId(null);
    setMessage(null);
    setError(null);
  };

  const uploadPhoto = async (): Promise<number | null> => {
    if (!token || !photoFile || !parentalConsent) {
      return null;
    }

    if (!hasPaidAccess) {
      setError(t.photoPremiumRequired);
      return null;
    }

    setUploadingPhoto(true);
    setError(null);

    try {
      const formData = new FormData();
      formData.append("photo", photoFile);
      formData.append("parental_consent", "1");

      if (childName.trim()) {
        formData.append("child_name", childName.trim());
      }

      const response = await fetch(`${apiBaseUrl}/photos/upload`, {
        method: "POST",
        headers: {
          Authorization: `Bearer ${token}`,
        },
        body: formData,
      });

      const data = await response.json();

      if (!response.ok) {
        throw new Error(data.message || t.photoUploadError);
      }

      const photoId = data.uploaded_photo?.id;

      if (typeof photoId !== "number") {
        throw new Error(t.photoUploadError);
      }

      setUploadedPhotoId(photoId);
      setMessage(t.photoUploadSuccess);

      return photoId;
    } catch (err) {
      const msg = err instanceof Error ? err.message : t.photoUploadError;
      setError(msg);
      return null;
    } finally {
      setUploadingPhoto(false);
    }
  };

  const submitGeneration = async (event: FormEvent<HTMLFormElement>) => {
    event.preventDefault();
    if (!token || !selectedGoal || !selectedAgeRange || !childName.trim() || selectedGoalLocked) {
      return;
    }

    const selectedRange = ageRanges.find((ageRange) => ageRange.value === selectedAgeRange);
    const childAge = selectedRange?.min_age ?? 5;

    setSubmitting(true);
    setMessage(null);
    setError(null);

    try {
      let photoId = uploadedPhotoId;

      if (photoFile && photoId === null) {
        photoId = await uploadPhoto();

        if (photoFile && photoId === null) {
          setSubmitting(false);
          return;
        }
      }

      const payload: Record<string, string | number> = {
        child_name: childName.trim(),
        age: childAge,
        goal: selectedGoal,
      };

      if (photoId !== null) {
        payload.uploaded_photo_id = photoId;
      }

      const response = await fetch(`${apiBaseUrl}/books/generate`, {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
          Authorization: `Bearer ${token}`,
        },
        body: JSON.stringify(payload),
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

  if (!hydrated || loading || !user) {
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
              <option key={goal.id} value={goal.name} disabled={goal.is_locked}>
                {goal.is_locked ? `${goal.name} (${t.goalLockedLabel})` : goal.name}
              </option>
            ))}
          </select>
          {selectedGoalLocked && <p className={styles.warning}>{t.goalLockedHint}</p>}
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

        {isPaid && hasPaidAccess && (
          <div className={styles.photoSection}>
            <h2>{t.photoUploadTitle}</h2>
            <p className={styles.photoHint}>{t.photoUploadHint}</p>
            <label className={styles.field}>
              <span>{t.photoUploadLabel}</span>
              <input type="file" accept="image/jpeg,image/png,image/webp" onChange={handlePhotoChange} />
            </label>
            <label className={styles.consentField}>
              <input
                type="checkbox"
                checked={parentalConsent}
                onChange={(event) => setParentalConsent(event.target.checked)}
              />
              <span>{t.parentalConsentCheckbox}</span>
            </label>
            {uploadingPhoto && <p className={styles.state}>{t.photoUploading}</p>}
          </div>
        )}

        <button
          type="submit"
          disabled={fetching || submitting || uploadingPhoto || selectedGoalLocked}
          className={`${btn.btnPrimary} ${styles.submit}`}
        >
          {submitting ? t.generating : t.generateButton}
        </button>
      </form>

      {fetching && <p className={styles.state}>{t.loading}</p>}
      {message && <p className={styles.success}>{message}</p>}
      {error && <p className={styles.error}>{error}</p>}
    </main>
  );
}
