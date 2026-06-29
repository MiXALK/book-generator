import { ReactNode } from "react";
import shell from "@/app/components/appShell.module.css";

interface PageShellProps {
  children: ReactNode;
  className?: string;
  variant?: "default" | "landing";
}

export default function PageShell({ children, className, variant = "default" }: PageShellProps) {
  const backgroundClass = variant === "landing" ? shell.pageLandingBackground : shell.pageBackground;

  return <div className={`${backgroundClass} ${className ?? ""}`.trim()}>{children}</div>;
}

export function PageShellMain({
  children,
  variant = "wide",
}: {
  children: ReactNode;
  variant?: "centered" | "wide" | "full";
}) {
  const mainClass =
    variant === "centered"
      ? shell.pageMainCentered
      : variant === "full"
        ? shell.pageMainFull
        : shell.pageMainWide;
  return <main className={mainClass}>{children}</main>;
}
