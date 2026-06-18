"use client";

import { useMemo, useState, useSyncExternalStore } from "react";
import BookFlipStage from "@/app/components/BookFlipStage";
import { BookGeneration } from "@/app/types/book";
import styles from "./bookReader.module.css";

const HINT_STORAGE_KEY = "storysprout-reader-hint-dismissed";

const hintListeners = new Set<() => void>();

function subscribeHint(callback: () => void) {
  hintListeners.add(callback);
  return () => hintListeners.delete(callback);
}

function getHintDismissed() {
  return localStorage.getItem(HINT_STORAGE_KEY) === "1";
}

function notifyHintListeners() {
  hintListeners.forEach((listener) => listener());
}

interface BookReaderLabels {
  closeReader: string;
  turnPageHint: string;
  dismissHint: string;
  pageAnnouncement: string;
}

interface BookReaderProps {
  generation: BookGeneration;
  labels: BookReaderLabels;
  onClose: () => void;
}

export default function BookReader({ generation, labels, onClose }: BookReaderProps) {
  const pages = useMemo(
    () => [...generation.book_pages].sort((left, right) => left.page_number - right.page_number),
    [generation.book_pages],
  );
  const [currentIndex, setCurrentIndex] = useState(0);
  const hintDismissed = useSyncExternalStore(subscribeHint, getHintDismissed, () => true);
  const showHint = !hintDismissed;

  const totalPages = pages.length;
  const progress = totalPages > 0 ? ((currentIndex + 1) / totalPages) * 100 : 0;

  const pageAnnouncement = labels.pageAnnouncement
    .replace("{current}", String(currentIndex + 1))
    .replace("{total}", String(totalPages));

  const dismissHint = () => {
    localStorage.setItem(HINT_STORAGE_KEY, "1");
    notifyHintListeners();
  };

  if (totalPages === 0) {
    return null;
  }

  return (
    <div className={styles.readerShell}>
      <div
        className={styles.progressBarTrack}
        role="progressbar"
        aria-valuenow={currentIndex + 1}
        aria-valuemin={1}
        aria-valuemax={totalPages}
        aria-label={pageAnnouncement}
      >
        <span className={styles.progressBarLabel}>
          {currentIndex + 1}/{totalPages}
        </span>
        <div className={styles.progressBarFill} style={{ width: `${progress}%` }} />
      </div>

      <div className={styles.readerMain}>
        <BookFlipStage
          pages={pages}
          currentIndex={currentIndex}
          onPageChange={setCurrentIndex}
          pageAnnouncement={pageAnnouncement}
          onClose={onClose}
          closeLabel={labels.closeReader}
        />

        {showHint && (
          <p className={styles.turnHint}>
            {labels.turnPageHint}{" "}
            <button type="button" className={styles.turnHintDismiss} onClick={dismissHint}>
              {labels.dismissHint}
            </button>
          </p>
        )}
      </div>
    </div>
  );
}
