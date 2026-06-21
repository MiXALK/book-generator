"use client";

import Link from "next/link";
import { usePathname, useRouter } from "next/navigation";
import { useEffect } from "react";
import { useAuth } from "@/app/context/AuthContext";
import { locales } from "@/app/context/locales";
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
    return <div className={styles.loading}>{t.loading}</div>;
  }

  const links = [
    { href: "/admin", label: t.adminDashboard },
    { href: "/admin/goals", label: t.adminGoals },
    { href: "/admin/templates", label: t.adminTemplates },
    { href: "/admin/prompts", label: t.adminPrompts },
    { href: "/admin/layouts", label: t.adminLayouts },
  ];

  return (
    <div className={styles.container}>
      <header className={styles.header}>
        <strong>{t.adminTitle}</strong>
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
          <Link href="/dashboard" className={styles.navLink}>
            {t.goToDashboard}
          </Link>
        </nav>
      </header>
      <main className={styles.main}>{children}</main>
    </div>
  );
}
