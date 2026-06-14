import type { Metadata } from "next";
export const metadata: Metadata = { title: "销售单" };
export default function Layout({ children }: { children: React.ReactNode }) {
  return <>{children}</>;
}
