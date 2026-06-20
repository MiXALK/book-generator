import { BookPage } from "@/app/types/book";
import BookIllustrationImage from "@/app/components/BookIllustrationImage";
import styles from "./bookReader.module.css";

interface BookPageViewProps {
  page: BookPage;
}

function layoutClass(textPosition: string | undefined): string {
  switch (textPosition) {
    case "top":
      return styles.layoutTextTop;
    case "left":
      return styles.layoutTextLeft;
    case "right":
      return styles.layoutTextRight;
    default:
      return styles.layoutTextBottom;
  }
}

export default function BookPageView({ page }: BookPageViewProps) {
  const textPosition = page.layout_template?.text_position ?? "bottom";
  const category = page.layout_template?.category ?? "content";

  return (
    <article className={`${styles.pageCanvas} ${layoutClass(textPosition)}`}>
      <div className={styles.illustrationZone} data-category={category}>
        {page.image_url ? (
          <BookIllustrationImage src={page.image_url} />
        ) : (
          <div className={styles.illustrationFallback} />
        )}
      </div>
      <div className={styles.textZone}>
        <div className={styles.textZoneBackdrop} aria-hidden="true" />
        <p className={styles.pageText}>{page.text}</p>
      </div>
    </article>
  );
}
