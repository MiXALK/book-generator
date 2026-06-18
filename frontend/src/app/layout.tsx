import type { Metadata } from "next";
import { Lora } from "next/font/google";
import "./globals.css";
import { AuthProvider } from "@/app/context/AuthContext";

const lora = Lora({
  variable: "--font-lora",
  subsets: ["latin", "cyrillic"],
  weight: ["400", "500", "600", "700"],
  display: "swap",
});

export const metadata: Metadata = {
  title: "StorySprout - Personalized Children's Books",
  description: "AI-powered personalized storybook generator tailored to your child's developmental goals.",
};

export default function RootLayout({
  children,
}: Readonly<{
  children: React.ReactNode;
}>) {
  return (
    <html lang="en" className={lora.variable}>
      <body className={lora.className}>
        <AuthProvider>
          {children}
        </AuthProvider>
      </body>
    </html>
  );
}
