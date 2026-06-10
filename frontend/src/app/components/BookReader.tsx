"use client";

import { useCallback, useEffect, useMemo, useState, type TouchEvent } from "react";
import BookPageView from "@/app/components/BookPageView";
import { BookGeneration } from "@/app/types/book";
import styles from "./bookReader.module.css";

interface BookReaderLabels {
  previousPage: string;
  nextPage: string;
  pageIndicator: string;
  closeReader: string;
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
  const [transitionDirection, setTransitionDirection] = useState<"forward" | "backward" | "none">("none");
  const [touchStartX, setTouchStartX] = useState<number | null>(null);

  const currentPage = pages[currentIndex];
  const totalPages = pages.length;

  const goToPage = useCallback((nextIndex: number, direction: "forward" | "backward") => {
    if (nextIndex < 0 || nextIndex >= totalPages) {
      return;
    }

    setTransitionDirection(direction);
    setCurrentIndex(nextIndex);
  }, [totalPages]);

  const goPrevious = () => goToPage(currentIndex - 1, "backward");
  const goNext = () => goToPage(currentIndex + 1, "forward");

  useEffect(() => {
    if (transitionDirection === "none") {
      return;
    }

    const timer = window.setTimeout(() => setTransitionDirection("none"), 280);

    return () => window.clearTimeout(timer);
  }, [transitionDirection, currentIndex]);

  const handleTouchStart = (event: TouchEvent<HTMLDivElement>) => {
    setTouchStartX(event.changedTouches[0]?.clientX ?? null);
  };

  const handleTouchEnd = (event: TouchEvent<HTMLDivElement>) => {
    if (touchStartX === null) {
      return;
    }

    const touchEndX = event.changedTouches[0]?.clientX ?? touchStartX;
    const delta = touchEndX - touchStartX;

    if (Math.abs(delta) > 48) {
      if (delta < 0) {
        goNext();
      } else {
        goPrevious();
      }
    }

    setTouchStartX(null);
  };

  if (!currentPage) {
    return null;
  }

  const transitionClass =
    transitionDirection === "forward"
      ? styles.pageEnterForward
      : transitionDirection === "backward"
        ? styles.pageEnterBackward
        : "";

  return (
    <div className={styles.readerShell}>
      <header className={styles.readerHeader}>
        <div>
          <h1 className={styles.readerTitle}>{generation.book_template?.title ?? generation.child_name}</h1>
          <p className={styles.readerSubtitle}>
            {generation.child_name} · {generation.child_goal}
          </p>
        </div>
        <button type="button" className={styles.closeButton} onClick={onClose}>
          {labels.closeReader}
        </button>
      </header>

      <div
        className={styles.readerStage}
        onTouchStart={handleTouchStart}
        onTouchEnd={handleTouchEnd}
      >
        <div key={currentPage.id} className={`${styles.pageFrame} ${transitionClass}`}>
          <BookPageView page={currentPage} />
        </div>
      </div>

      <footer className={styles.readerFooter}>
        <button
          type="button"
          className={styles.navButton}
          onClick={goPrevious}
          disabled={currentIndex === 0}
        >
          {labels.previousPage}
        </button>
        <span className={styles.pageIndicator}>
          {labels.pageIndicator
            .replace("{current}", String(currentIndex + 1))
            .replace("{total}", String(totalPages))}
        </span>
        <button
          type="button"
          className={styles.navButton}
          onClick={goNext}
          disabled={currentIndex >= totalPages - 1}
        >
          {labels.nextPage}
        </button>
      </footer>
    </div>
  );
}
