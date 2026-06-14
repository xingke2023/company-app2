import { apiClient } from './client';
import type { AssistantNote } from './types';

export const assistantApi = {
  list: (type?: string): Promise<AssistantNote[]> =>
    apiClient.get<AssistantNote[]>('/assistant/notes' + (type ? `?type=${type}` : '')),

  create: (type: string, content: string): Promise<AssistantNote> =>
    apiClient.post<AssistantNote>('/assistant/notes', { type, content }),

  delete: (id: number): Promise<void> =>
    apiClient.delete<void>(`/assistant/notes/${id}`),
};
