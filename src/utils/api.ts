import apiFetch from '@wordpress/api-fetch';
import { 
  FilterState, 
  PostsResponse, 
  FilterOptions, 
  BulkActionResult, 
  SearchSuggestions 
} from '../types';

// Base API URL
const API_BASE = window.uadtAdmin?.apiUrl || '/wp-json/uadt/v1/';

// API request wrapper with error handling
const apiRequest = async <T>(endpoint: string, options: RequestInit = {}): Promise<T> => {
  try {
    const response = await apiFetch({
      path: `${API_BASE}${endpoint}`,
      ...options,
    });
    return response as T;
  } catch (error) {
    console.error('API Request Error:', error);
    throw error;
  }
};

// Get posts with filtering
export const getPosts = async (filters: FilterState): Promise<PostsResponse> => {
  const params = new URLSearchParams();
  
  Object.entries(filters).forEach(([key, value]) => {
    if (value !== '' && value !== null && value !== undefined) {
      params.append(key, String(value));
    }
  });

  return apiRequest<PostsResponse>(`posts?${params.toString()}`);
};

// Get filter options for dropdowns
export const getFilterOptions = async (postType: string = 'post'): Promise<FilterOptions> => {
  return apiRequest<FilterOptions>(`filter-options?post_type=${postType}`);
};

// Get search suggestions
export const getSearchSuggestions = async (
  query: string, 
  postType: string = 'post'
): Promise<SearchSuggestions> => {
  if (query.length < 2) {
    return { suggestions: [] };
  }
  
  return apiRequest<SearchSuggestions>(
    `search-suggestions?query=${encodeURIComponent(query)}&post_type=${postType}`
  );
};

// Perform bulk actions
export const performBulkAction = async (
  action: string, 
  postIds: number[]
): Promise<BulkActionResult> => {
  return apiRequest<BulkActionResult>('posts/bulk', {
    method: 'POST',
    data: {
      action,
      post_ids: postIds,
    },
  });
};

// Export data
export const exportData = async (
  format: 'csv' | 'excel', 
  filters: FilterState
): Promise<{ message: string }> => {
  return apiRequest<{ message: string }>('export', {
    method: 'POST',
    data: {
      format,
      ...filters,
    },
  });
};

// Utility function to build query string from filters
export const buildQueryString = (filters: Partial<FilterState>): string => {
  const params = new URLSearchParams();
  
  Object.entries(filters).forEach(([key, value]) => {
    if (value !== '' && value !== null && value !== undefined) {
      params.append(key, String(value));
    }
  });
  
  return params.toString();
};

// Debounce utility for search
export const debounce = <T extends (...args: any[]) => any>(
  func: T,
  wait: number
): ((...args: Parameters<T>) => void) => {
  let timeout: NodeJS.Timeout;
  
  return (...args: Parameters<T>) => {
    clearTimeout(timeout);
    timeout = setTimeout(() => func(...args), wait);
  };
};

// Format date for API
export const formatDateForAPI = (date: string): string => {
  if (!date) return '';
  
  try {
    const d = new Date(date);
    return d.toISOString().split('T')[0]; // YYYY-MM-DD format
  } catch {
    return date; // Return as-is if parsing fails
  }
};

// Parse date from API
export const parseDateFromAPI = (dateString: string): Date | null => {
  if (!dateString) return null;
  
  try {
    return new Date(dateString);
  } catch {
    return null;
  }
};
