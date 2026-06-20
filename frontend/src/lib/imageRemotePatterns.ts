import type { RemotePattern } from "next/dist/shared/lib/image-config";

function protocolFromUrl(url: URL): "http" | "https" {
  return url.protocol === "https:" ? "https" : "http";
}

export function buildImageRemotePatterns(): RemotePattern[] {
  const patterns: RemotePattern[] = [
    {
      protocol: "https",
      hostname: "lh3.googleusercontent.com",
      pathname: "/**",
    },
  ];

  const apiBase = process.env.NEXT_PUBLIC_API_BASE_URL ?? "http://localhost:8000/api";

  try {
    const apiUrl = new URL(apiBase);
    const pattern: RemotePattern = {
      protocol: protocolFromUrl(apiUrl),
      hostname: apiUrl.hostname,
      pathname: "/api/books/**",
    };

    if (apiUrl.port !== "") {
      pattern.port = apiUrl.port;
    }

    patterns.push(pattern);
  } catch {
    patterns.push({
      protocol: "http",
      hostname: "localhost",
      port: "8000",
      pathname: "/api/books/**",
    });
  }

  return patterns;
}
