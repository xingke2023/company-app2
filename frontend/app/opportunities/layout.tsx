import type { Metadata } from "next";
export const metadata: Metadata = { title: "商机" };
export default function Layout({ children }: { children: React.ReactNode }) {
  return <>{children}</>;
}
