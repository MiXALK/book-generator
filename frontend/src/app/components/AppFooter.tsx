import { ReactNode } from "react";
import styles from "./AppFooter.module.css";

interface AppFooterProps {
  children: ReactNode;
  className?: string;
}

export default function AppFooter({ children, className }: AppFooterProps) {
  return (
    <footer className={`${styles.footer} ${className ?? ""}`.trim()}>
      <p>{children}</p>
    </footer>
  );
}
