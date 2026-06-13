import { apiClient } from './client';
import type { PaginatedResponse, Product, Customer, Lead, Opportunity, SalesOrder, PurchaseOrder } from './types';

export const productsApi = {
  list: (token: string, search?: string) =>
    apiClient.get<PaginatedResponse<Product>>(`/products${search ? `?search=${encodeURIComponent(search)}` : ''}`, token),
};

export const customersApi = {
  list: (token: string, search?: string) =>
    apiClient.get<PaginatedResponse<Customer>>(`/customers${search ? `?search=${encodeURIComponent(search)}` : ''}`, token),
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
