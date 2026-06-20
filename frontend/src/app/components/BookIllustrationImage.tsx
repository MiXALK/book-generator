import Image from "next/image";
import styles from "./bookReader.module.css";

interface BookIllustrationImageProps {
  src: string;
  sizes?: string;
  className?: string;
}

export default function BookIllustrationImage({
  src,
  sizes = "(max-width: 720px) 92vw, 760px",
  className = styles.illustrationImage,
}: BookIllustrationImageProps) {
  return (
    <Image
      src={src}
      alt=""
      fill
      className={className}
      sizes={sizes}
      unoptimized
    />
  );
}
