import type { NextConfig } from "next";
import { buildImageRemotePatterns } from "./src/lib/imageRemotePatterns";

const nextConfig: NextConfig = {
  images: {
    remotePatterns: buildImageRemotePatterns(),
  },
};

export default nextConfig;
