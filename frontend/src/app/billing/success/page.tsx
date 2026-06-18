"use client";

import { useEffect } from "react";
import { useRouter } from "next/navigation";
import { useAuth } from "@/app/context/AuthContext";
import { locales } from "@/app/context/locales";
import btn from "@/app/components/storyButton.module.css";
import styles from "./success.module.css";

export default function BillingSuccessPage() {
  const router = useRouter();
  const { user, loading, refreshUser, locale } = useAuth();
  const t = locales[locale] || locales.ru;

  useEffect(() => {
    if (!loading && !user) {
      router.push("/");
    }
  }, [loading, router, user]);

  useEffect(() => {
    if (!user) {
      return;
    }

    refreshUser();
  }, [refreshUser, user]);

  if (loading || !user) {
    return (
      <main className={styles.page}>
        <p>{t.loading}</p>
      </main>
    );
  }

  return (
    <main className={styles.page}>
      <h1>{t.billingSuccessTitle}</h1>
      <p>{t.billingSuccessMessage}</p>
      <button type="button" className={btn.btnPrimary} onClick={() => router.push("/dashboard")}>
        {t.billingSuccessContinue}
      </button>
    </main>
  );
}
