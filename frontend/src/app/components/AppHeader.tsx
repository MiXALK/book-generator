"use client";

import { ReactNode } from "react";
import Link from "next/link";
import LanguageSelect from "@/app/components/LanguageSelect";
import styles from "./AppHeader.module.css";

interface AppHeaderProps {
  brandName: string;
  children?: ReactNode;
  homeHref?: string;
  className?: string;
}

export default function AppHeader({ brandName, children, homeHref = "/", className }: AppHeaderProps) {
  return (
    <header className={`${styles.header} ${className ?? ""}`.trim()}>
      <Link href={homeHref} className={styles.brand}>
        <span className={styles.logoMark} aria-hidden="true">
          📖
        </span>
        <span className={styles.logoText}>{brandName}</span>
      </Link>

      <div className={styles.langSlot}>
        <LanguageSelect />
      </div>

      {children ? <div className={styles.sessionBar}>{children}</div> : null}
    </header>
  );
}
