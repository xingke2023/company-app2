export interface User {
  id: number;
  name: string;
  email: string;
  email_verified_at: string | null;
  created_at: string;
  updated_at: string;
}

export interface AssistantNote {
  id: number;
  type: string;
  content: string;
  created_at: string;
  updated_at: string;
}

export interface Post {
  id: number;
  user_id: number;
  title: string;
  content: string;
  published: boolean;
  created_at: string;
  updated_at: string;
  user?: {
    id: number;
    name: string;
    email: string;
  };
}

export interface AuthResponse {
  access_token: string;
  token_type: string;
  expires_in: number;
  user: User;
}

export interface LoginCredentials {
  email: string;
  password: string;
}

export interface RegisterCredentials {
  name: string;
  email: string;
  password: string;
  password_confirmation: string;
}

export interface PaginatedResponse<T> {
  data: T[];
  current_page: number;
  last_page: number;
  per_page: number;
  total: number;
}

export interface PostFormData {
  title: string;
  content: string;
  published?: boolean;
}

export interface Product {
  id: number;
  name: string;
  sku: string;
  unit: string;
  cost_price: string;
  sell_price: string;
  stock_quantity: number;
  min_stock: number | null;
  status: string;
  category?: { id: number; name: string };
  supplier?: { id: number; name: string };
}

export interface Customer {
  id: number;
  name: string;
  company: string | null;
  phone: string | null;
  email: string | null;
  status: string;
  type: string | null;
  sales_orders_count?: number;
}

export interface Lead {
  id: number;
  name: string;
  company: string | null;
  phone: string | null;
  email: string | null;
  source: string | null;
  status: string;
}

export interface Opportunity {
  id: number;
  title: string;
  amount: string;
  stage: string;
  probability: number;
  expected_close_date: string | null;
  customer?: { id: number; name: string };
}

export interface SalesOrder {
  id: number;
  order_no: string;
  status: string;
  total_amount: string;
  ordered_at: string | null;
  customer?: { id: number; name: string };
}

export interface Activity {
  id: number;
  type: string;
  title: string | null;
  description: string | null;
  scheduled_at: string | null;
  completed_at: string | null;
  customer_id: number | null;
  lead_id: number | null;
  opportunity_id: number | null;
  created_at: string;
}

export interface CustomerDetail extends Customer {
  address: string | null;
  notes: string | null;
  contacts: { id: number; name: string; phone: string | null; position: string | null }[];
  activities: Activity[];
}

export interface PurchaseOrder {
  id: number;
  order_no: string;
  status: string;
  total_amount: string;
  ordered_at: string | null;
  supplier?: { id: number; name: string };
  items_count?: number;
}
