import { apiClient } from './client';
import type { PaginatedResponse, Product, Customer, CustomerDetail, Activity, Lead, Opportunity, SalesOrder, PurchaseOrder } from './types';

export const productsApi = {
  list: (token: string, search?: string) =>
    apiClient.get<PaginatedResponse<Product>>(`/products${search ? `?search=${encodeURIComponent(search)}` : ''}`, token),
};

export const customersApi = {
  list: (token: string, search?: string, status?: string) => {
    const params = new URLSearchParams();
    if (search) params.set('search', search);
    if (status) params.set('status', status);
    const qs = params.toString();
    return apiClient.get<PaginatedResponse<Customer>>(`/customers${qs ? `?${qs}` : ''}`, token);
  },
  get: (token: string, id: number) =>
    apiClient.get<CustomerDetail>(`/customers/${id}`, token),
};

export const activitiesApi = {
  listByCustomer: (token: string, customerId: number) =>
    apiClient.get<PaginatedResponse<Activity>>(`/activities?customer_id=${customerId}&per_page=50`, token),
};

export const leadsApi = {
  list: (token: string, search?: string) =>
    apiClient.get<PaginatedResponse<Lead>>(`/leads${search ? `?search=${encodeURIComponent(search)}` : ''}`, token),
};

export const opportunitiesApi = {
  list: (token: string, search?: string) =>
    apiClient.get<PaginatedResponse<Opportunity>>(`/opportunities${search ? `?search=${encodeURIComponent(search)}` : ''}`, token),
};

export const salesOrdersApi = {
  list: (token: string, search?: string) =>
    apiClient.get<PaginatedResponse<SalesOrder>>(`/sales-orders${search ? `?search=${encodeURIComponent(search)}` : ''}`, token),
};

export const purchaseOrdersApi = {
  list: (token: string, search?: string) =>
    apiClient.get<PaginatedResponse<PurchaseOrder>>(`/purchase-orders${search ? `?search=${encodeURIComponent(search)}` : ''}`, token),
};
