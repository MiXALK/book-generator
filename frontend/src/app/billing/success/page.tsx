"use client";

import { useEffect } from "react";
import { useRouter } from "next/navigation";
import { useAuth } from "@/app/context/AuthContext";
import { locales } from "@/app/context/locales";
import PageShell, { PageShellMain } from "@/app/components/PageShell";
import ui from "@/app/components/ui.module.css";
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
      <div className={ui.loadingCenter}>
        <div className={ui.spinnerLarge} />
        <p>{t.loading}</p>
      </div>
    );
  }

  return (
    <PageShell>
      <PageShellMain variant="centered">
        <div className={`${ui.card} ${styles.card}`}>
          <div className={styles.icon}>★</div>
          <h1>{t.billingSuccessTitle}</h1>
          <p>{t.billingSuccessMessage}</p>
          <button type="button" className={`${ui.btnPrimary} ${ui.btnLarge}`} onClick={() => router.push("/dashboard")}>
            {t.billingSuccessContinue}
          </button>
        </div>
      </PageShellMain>
    </PageShell>
  );
}
