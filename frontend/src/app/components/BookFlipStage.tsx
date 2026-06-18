"use client";

import {
  useCallback,
  useEffect,
  useRef,
  useState,
  type PointerEvent as ReactPointerEvent,
} from "react";
import BookPageView from "@/app/components/BookPageView";
import { BookPage } from "@/app/types/book";
import styles from "./bookReader.module.css";

const WHEEL_THRESHOLD = 80;
const WHEEL_COOLDOWN_MS = 400;
const COMMIT_THRESHOLD = 0.45;
const FLIP_DURATION_MS = 680;

type FlipDirection = "forward" | "backward";

function easeInOutSine(t: number): number {
  return -(Math.cos(Math.PI * t) - 1) / 2;
}

function getFlipMotion(progress: number, direction: FlipDirection) {
  const wave = Math.sin(progress * Math.PI);
  const rotateY =
    direction === "forward" ? -progress * 180 : -180 + progress * 180;
  const lift = wave * 18;
  const tiltX = wave * (direction === "forward" ? -4.5 : 4.5);
  const cornerRotateX = -wave * 28;
  const cornerRotateZ = wave * (direction === "forward" ? 6 : -6);

  return {
    turningTransform: `rotateY(${rotateY}deg) rotateX(${tiltX}deg) translateZ(${lift}px)`,
    cornerRollTransform: `rotateX(${cornerRotateX}deg) rotateZ(${cornerRotateZ}deg)`,
    cornerRollOpacity: Math.min(1, wave * 1.7),
    shadowIntensity: wave * 0.38,
  };
}

interface BookFlipStageProps {
  pages: BookPage[];
  currentIndex: number;
  onPageChange: (index: number) => void;
  pageAnnouncement: string;
  onClose: () => void;
  closeLabel: string;
}

export default function BookFlipStage({
  pages,
  currentIndex,
  onPageChange,
  pageAnnouncement,
  onClose,
  closeLabel,
}: BookFlipStageProps) {
  const stageRef = useRef<HTMLDivElement>(null);
  const wheelDeltaRef = useRef(0);
  const wheelCooldownRef = useRef(false);
  const dragStartXRef = useRef(0);
  const dragDirectionRef = useRef<FlipDirection | null>(null);
  const flipProgressRef = useRef(0);

  const [flipDirection, setFlipDirection] = useState<FlipDirection | null>(null);
  const [flipProgress, setFlipProgress] = useState(0);
  const [isAnimating, setIsAnimating] = useState(false);
  const [isDragging, setIsDragging] = useState(false);

  useEffect(() => {
    flipProgressRef.current = flipProgress;
  }, [flipProgress]);

  const totalPages = pages.length;
  const currentPage = pages[currentIndex];

  const canGoForward = currentIndex < totalPages - 1;
  const canGoBackward = currentIndex > 0;

  const completeFlip = useCallback(
    (direction: FlipDirection) => {
      if (direction === "forward" && canGoForward) {
        onPageChange(currentIndex + 1);
      } else if (direction === "backward" && canGoBackward) {
        onPageChange(currentIndex - 1);
      }

      setFlipDirection(null);
      setFlipProgress(0);
      setIsAnimating(false);
      setIsDragging(false);
      dragDirectionRef.current = null;
    },
    [canGoBackward, canGoForward, currentIndex, onPageChange],
  );

  const animateToProgress = useCallback(
    (target: number, direction: FlipDirection, onDone: () => void) => {
      setIsAnimating(true);
      setFlipDirection(direction);

      const start = flipProgressRef.current;
      const startTime = performance.now();

      const tick = (now: number) => {
        const elapsed = now - startTime;
        const t = Math.min(elapsed / FLIP_DURATION_MS, 1);
        const eased = easeInOutSine(t);
        const value = start + (target - start) * eased;

        setFlipProgress(value);

        if (t < 1) {
          requestAnimationFrame(tick);
        } else {
          onDone();
        }
      };

      requestAnimationFrame(tick);
    },
    [],
  );

  const startFlip = useCallback(
    (direction: FlipDirection) => {
      if (isAnimating || isDragging) {
        return;
      }

      if (direction === "forward" && !canGoForward) {
        return;
      }

      if (direction === "backward" && !canGoBackward) {
        return;
      }

      flipProgressRef.current = 0;
      setFlipProgress(0);
      animateToProgress(1, direction, () => completeFlip(direction));
    },
    [animateToProgress, canGoBackward, canGoForward, completeFlip, isAnimating, isDragging],
  );

  const snapBack = useCallback(() => {
    if (flipProgressRef.current <= 0) {
      setFlipDirection(null);
      setIsDragging(false);
      dragDirectionRef.current = null;
      return;
    }

    animateToProgress(0, flipDirection ?? "forward", () => {
      setFlipDirection(null);
      setFlipProgress(0);
      setIsAnimating(false);
      setIsDragging(false);
      dragDirectionRef.current = null;
    });
  }, [animateToProgress, flipDirection]);

  useEffect(() => {
    const stage = stageRef.current;
    if (!stage) {
      return;
    }

    const handleNativeWheel = (event: WheelEvent) => {
      event.preventDefault();

      if (isAnimating || isDragging) {
        return;
      }

      wheelDeltaRef.current += event.deltaY;

      if (Math.abs(wheelDeltaRef.current) < WHEEL_THRESHOLD) {
        return;
      }

      if (wheelCooldownRef.current) {
        wheelDeltaRef.current = 0;
        return;
      }

      const direction: FlipDirection = wheelDeltaRef.current > 0 ? "forward" : "backward";
      wheelDeltaRef.current = 0;

      if (direction === "forward" && canGoForward) {
        wheelCooldownRef.current = true;
        startFlip("forward");
        window.setTimeout(() => {
          wheelCooldownRef.current = false;
        }, WHEEL_COOLDOWN_MS);
      } else if (direction === "backward" && canGoBackward) {
        wheelCooldownRef.current = true;
        startFlip("backward");
        window.setTimeout(() => {
          wheelCooldownRef.current = false;
        }, WHEEL_COOLDOWN_MS);
      }
    };

    stage.addEventListener("wheel", handleNativeWheel, { passive: false });
    return () => stage.removeEventListener("wheel", handleNativeWheel);
  }, [canGoBackward, canGoForward, isAnimating, isDragging, startFlip]);

  useEffect(() => {
    const handleKeyDown = (event: KeyboardEvent) => {
      if (isAnimating || isDragging) {
        return;
      }

      if (event.key === "ArrowRight" || event.key === "ArrowDown") {
        event.preventDefault();
        startFlip("forward");
      } else if (event.key === "ArrowLeft" || event.key === "ArrowUp") {
        event.preventDefault();
        startFlip("backward");
      }
    };

    window.addEventListener("keydown", handleKeyDown);
    return () => window.removeEventListener("keydown", handleKeyDown);
  }, [isAnimating, isDragging, startFlip]);

  const resolveDragDirection = (clientX: number, rect: DOMRect): FlipDirection | null => {
    const relativeX = (clientX - rect.left) / rect.width;

    if (relativeX >= 0.6 && canGoForward) {
      return "forward";
    }

    if (relativeX <= 0.4 && canGoBackward) {
      return "backward";
    }

    return null;
  };

  const handlePointerDown = (event: ReactPointerEvent<HTMLDivElement>) => {
    if (isAnimating || event.button !== 0) {
      return;
    }

    const rect = stageRef.current?.getBoundingClientRect();
    if (!rect) {
      return;
    }

    const direction = resolveDragDirection(event.clientX, rect);
    if (!direction) {
      return;
    }

    event.currentTarget.setPointerCapture(event.pointerId);
    dragStartXRef.current = event.clientX;
    dragDirectionRef.current = direction;
    setFlipDirection(direction);
    setIsDragging(true);
    setFlipProgress(0);
  };

  const handlePointerMove = (event: ReactPointerEvent<HTMLDivElement>) => {
    if (!isDragging || !dragDirectionRef.current) {
      return;
    }

    const rect = stageRef.current?.getBoundingClientRect();
    if (!rect) {
      return;
    }

    const delta = event.clientX - dragStartXRef.current;
    const progress =
      dragDirectionRef.current === "forward"
        ? Math.max(0, Math.min(1, -delta / (rect.width * 0.55)))
        : Math.max(0, Math.min(1, delta / (rect.width * 0.55)));

    setFlipProgress(progress);
  };

  const handlePointerUp = (event: ReactPointerEvent<HTMLDivElement>) => {
    if (!isDragging || !dragDirectionRef.current) {
      return;
    }

    if (event.currentTarget.hasPointerCapture(event.pointerId)) {
      event.currentTarget.releasePointerCapture(event.pointerId);
    }

    const direction = dragDirectionRef.current;

    if (flipProgressRef.current >= COMMIT_THRESHOLD) {
      setIsDragging(false);
      animateToProgress(1, direction, () => completeFlip(direction));
    } else {
      snapBack();
    }
  };

  if (!currentPage) {
    return null;
  }

  const isFlipping = flipDirection !== null;
  const underneathPage =
    flipDirection === "forward"
      ? pages[currentIndex + 1]
      : flipDirection === "backward"
        ? pages[currentIndex]
        : null;

  const turningPage =
    flipDirection === "backward" && isFlipping ? pages[currentIndex - 1] : currentPage;

  const flipMotion =
    flipDirection !== null ? getFlipMotion(flipProgress, flipDirection) : null;

  return (
    <div
      ref={stageRef}
      className={styles.flipStage}
      onPointerDown={handlePointerDown}
      onPointerMove={handlePointerMove}
      onPointerUp={handlePointerUp}
      onPointerCancel={handlePointerUp}
      role="region"
      aria-label={pageAnnouncement}
      aria-live="polite"
    >
      <div className={styles.bookViewport}>
        <button
          type="button"
          className={styles.closeButton}
          onClick={onClose}
          onPointerDown={(event) => event.stopPropagation()}
          aria-label={closeLabel}
        >
          ×
        </button>
        <div className={styles.bookSpread}>
          <div className={styles.pageStackEdge} aria-hidden="true" />
          <div className={styles.spineShadow} />

          {isFlipping && underneathPage && (
            <div className={styles.pageUnderneath}>
              <BookPageView page={underneathPage} />
            </div>
          )}

          {!isFlipping && (
            <div className={styles.pageStatic}>
              <BookPageView page={currentPage} />
            </div>
          )}

          {isFlipping && turningPage && flipMotion && (
            <div
              className={`${styles.pageTurning} ${isDragging ? styles.pageTurningDragging : ""}`}
              style={{
                transform: flipMotion.turningTransform,
                transformOrigin: "left center",
                boxShadow: `-8px 4px 28px rgba(61, 54, 50, ${flipMotion.shadowIntensity})`,
              }}
            >
              <BookPageView page={turningPage} />
              <div
                className={styles.pageCornerRoll}
                style={{
                  transform: flipMotion.cornerRollTransform,
                  opacity: flipMotion.cornerRollOpacity,
                }}
                aria-hidden="true"
              />
              <div
                className={styles.pageTurningShade}
                style={{ opacity: flipMotion.shadowIntensity * 1.35 }}
              />
            </div>
          )}

          <div className={styles.edgeHintLeft} aria-hidden="true" />
          <div className={styles.edgeHintRight} aria-hidden="true" />
        </div>
      </div>
    </div>
  );
}
