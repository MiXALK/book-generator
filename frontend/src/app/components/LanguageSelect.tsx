"use client";

import { useAuth } from "@/app/context/AuthContext";
import { Locale } from "@/app/context/locales";
import styles from "./LanguageSelect.module.css";

export default function LanguageSelect() {
  const { locale, setLocale } = useAuth();

  const handleLanguageChange = (e: React.ChangeEvent<HTMLSelectElement>) => {
    setLocale(e.target.value as Locale);
  };

  return (
    <div className={styles.selectWrapper}>
      <span className={styles.globeIcon}>🌐</span>
      <select
        value={locale}
        onChange={handleLanguageChange}
        className={styles.selectInput}
        aria-label="Select Language"
      >
        <option value="ru">Русский</option>
        <option value="en">English</option>
      </select>
    </div>
  );
}
