"use client";

import Link from "next/link";
import { usePathname, useRouter } from "next/navigation";
import { useEffect } from "react";
import { useAuth } from "@/app/context/AuthContext";
import { locales } from "@/app/context/locales";
import AppHeader from "@/app/components/AppHeader";
import PageShell, { PageShellMain } from "@/app/components/PageShell";
import ui from "@/app/components/ui.module.css";
import styles from "./admin.module.css";

export default function AdminLayout({ children }: { children: React.ReactNode }) {
  const { user, loading, locale } = useAuth();
  const router = useRouter();
  const pathname = usePathname();
  const t = locales[locale] || locales.ru;

  useEffect(() => {
    if (!loading && (!user || user.role !== "admin")) {
      router.push("/");
    }
  }, [user, loading, router]);

  if (loading || !user || user.role !== "admin") {
    return (
      <div className={ui.loadingCenter}>
        <div className={ui.spinnerLarge} />
        <p>{t.loading}</p>
      </div>
    );
  }

  const links = [
    { href: "/admin", label: t.adminDashboard },
    { href: "/admin/goals", label: t.adminGoals },
    { href: "/admin/templates", label: t.adminTemplates },
    { href: "/admin/prompts", label: t.adminPrompts },
    { href: "/admin/layouts", label: t.adminLayouts },
  ];

  return (
    <PageShell>
      <AppHeader brandName={t.adminTitle} homeHref="/admin">
        <Link href="/dashboard" className={ui.btnGhost}>
          {t.goToDashboard}
        </Link>
      </AppHeader>

      <div className={styles.subnav}>
        <nav className={styles.nav}>
          {links.map((link) => (
            <Link
              key={link.href}
              href={link.href}
              className={`${styles.navLink} ${pathname === link.href ? styles.navActive : ""}`}
            >
              {link.label}
            </Link>
          ))}
        </nav>
      </div>

      <PageShellMain variant="wide">{children}</PageShellMain>
    </PageShell>
  );
}
