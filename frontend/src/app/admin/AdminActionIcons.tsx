import { ReactNode, SVGProps } from "react";

type IconProps = SVGProps<SVGSVGElement>;

function AdminIcon({ children, ...props }: IconProps & { children: ReactNode }) {
  return (
    <svg
      viewBox="0 0 24 24"
      width="1.1em"
      height="1.1em"
      fill="none"
      stroke="currentColor"
      strokeWidth="2"
      strokeLinecap="round"
      strokeLinejoin="round"
      aria-hidden="true"
      {...props}
    >
      {children}
    </svg>
  );
}

export function AdminEditIcon(props: IconProps) {
  return (
    <AdminIcon {...props}>
      <path d="M12 20h9" />
      <path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4Z" />
    </AdminIcon>
  );
}

export function AdminDeleteIcon(props: IconProps) {
  return (
    <AdminIcon {...props}>
      <path d="M3 6h18" />
      <path d="M8 6V4h8v2" />
      <path d="M19 6l-1 14H6L5 6" />
      <path d="M10 11v6" />
      <path d="M14 11v6" />
    </AdminIcon>
  );
}

export function AdminPublishIcon(props: IconProps) {
  return (
    <AdminIcon {...props}>
      <path d="M12 3v12" />
      <path d="m7 8 5-5 5 5" />
      <path d="M5 21h14" />
      <path d="M5 16h14" />
    </AdminIcon>
  );
}
