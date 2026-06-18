"use client";

import { useEffect, useRef, useState } from "react";
import { useAuth } from "@/app/context/AuthContext";
import { Locale } from "@/app/context/locales";
import btn from "@/app/components/storyButton.module.css";
import styles from "./LanguageSelect.module.css";

const LANGUAGES: { value: Locale; label: string; flag: string }[] = [
  { value: "ru", label: "Русский", flag: "🇷🇺" },
  { value: "en", label: "English", flag: "🇬🇧" },
];

export default function LanguageSelect() {
  const { locale, setLocale } = useAuth();
  const [open, setOpen] = useState(false);
  const rootRef = useRef<HTMLDivElement>(null);

  const current = LANGUAGES.find((lang) => lang.value === locale) ?? LANGUAGES[0];

  useEffect(() => {
    if (!open) {
      return;
    }

    const handlePointerDown = (event: MouseEvent) => {
      if (!rootRef.current?.contains(event.target as Node)) {
        setOpen(false);
      }
    };

    const handleKeyDown = (event: KeyboardEvent) => {
      if (event.key === "Escape") {
        setOpen(false);
      }
    };

    document.addEventListener("mousedown", handlePointerDown);
    document.addEventListener("keydown", handleKeyDown);
    return () => {
      document.removeEventListener("mousedown", handlePointerDown);
      document.removeEventListener("keydown", handleKeyDown);
    };
  }, [open]);

  const selectLanguage = (value: Locale) => {
    setLocale(value);
    setOpen(false);
  };

  return (
    <div className={styles.root} ref={rootRef}>
      <button
        type="button"
        className={`${btn.btnGhost} ${styles.trigger}`}
        aria-haspopup="listbox"
        aria-expanded={open}
        aria-label="Select language"
        onClick={() => setOpen((prev) => !prev)}
      >
        <span className={styles.triggerFlag} aria-hidden="true">
          {current.flag}
        </span>
        <span className={styles.triggerLabel}>{current.label}</span>
        <span className={`${styles.chevron} ${open ? styles.chevronOpen : ""}`} aria-hidden="true">
          ▾
        </span>
      </button>

      {open && (
        <ul className={styles.menu} role="listbox" aria-label="Languages">
          {LANGUAGES.map((lang) => {
            const selected = lang.value === locale;
            return (
              <li key={lang.value} role="presentation">
                <button
                  type="button"
                  role="option"
                  aria-selected={selected}
                  className={`${styles.option} ${selected ? styles.optionSelected : ""}`}
                  onClick={() => selectLanguage(lang.value)}
                >
                  <span className={styles.optionFlag} aria-hidden="true">
                    {lang.flag}
                  </span>
                  <span>{lang.label}</span>
                </button>
              </li>
            );
          })}
        </ul>
      )}
    </div>
  );
}
