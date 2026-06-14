import type { Metadata } from "next";
export const metadata: Metadata = { title: "采购单" };
export default function Layout({ children }: { children: React.ReactNode }) {
  return <>{children}</>;
}
