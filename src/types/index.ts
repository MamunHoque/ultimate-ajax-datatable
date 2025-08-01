// Filter state interface
export interface FilterState {
  search: string;
  post_type: string;
  author: string | string[];
  status: string | string[];
  category: string | string[];
  tag: string | string[];
  date_from: string;
  date_to: string;
  date_preset: string;
  custom_fields: Record<string, string>;
  page: number;
  per_page: number;
  orderby: string;
  order: 'ASC' | 'DESC';
  selected_posts: number[];
}

// Post data interface
export interface Post {
  id: number;
  title: string;
  slug: string;
  status: string;
  status_label: string;
  author: string;
  author_id: number;
  date: string;
  date_formatted: string;
  modified: string;
  modified_formatted: string;
  post_type: string;
  post_type_label: string;
  excerpt: string;
  featured_image: string;
  categories: Category[];
  tags: Tag[];
  edit_link: string;
  view_link: string;
  can_edit: boolean;
  can_delete: boolean;
}

// Category interface
export interface Category {
  id: number;
  name: string;
  slug: string;
  count?: number;
}

// Tag interface
export interface Tag {
  id: number;
  name: string;
  slug: string;
  count?: number;
}

// Author interface
export interface Author {
  id: number;
  name: string;
  login: string;
}

// Post status interface
export interface PostStatus {
  value: string;
  label: string;
}

// API response interface
export interface PostsResponse {
  posts: Post[];
  total: number;
  pages: number;
  current_page: number;
  per_page: number;
  query_time: number;
  cached: boolean;
}

// Filter options interface
export interface FilterOptions {
  authors: Author[];
  statuses: PostStatus[];
  categories: Category[];
  tags: Tag[];
}

// Bulk action interface
export interface BulkActionResult {
  success: boolean;
  results: Record<number, { success: boolean; error?: string }>;
  summary: {
    success_count: number;
    error_count: number;
    total: number;
  };
}

// Search suggestions interface
export interface SearchSuggestions {
  suggestions: string[];
}

// WordPress admin globals
declare global {
  interface Window {
    uadtAdmin: {
      apiUrl: string;
      nonce: string;
      currentUser: number;
      capabilities: {
        edit_posts: boolean;
        delete_posts: boolean;
        manage_options: boolean;
      };
      settings: {
        enabledPostTypes: string[];
        itemsPerPage: number;
        enableSearch: boolean;
        enableFilters: boolean;
        enableBulkActions: boolean;
        maxItemsPerPage: number;
      };
      strings: {
        loading: string;
        error: string;
        noResults: string;
        search: string;
        filters: string;
        clearFilters: string;
        bulkActions: string;
        selectAll: string;
        export: string;
      };
    };
  }
}

// Bulk action interface
export interface BulkAction {
  action: string;
  post_ids: number[];
  confirm?: boolean;
}

// Bulk action result
export interface BulkActionResult {
  success: boolean;
  message: string;
  summary: {
    success_count: number;
    error_count: number;
    errors: Array<{
      post_id: number;
      error: string;
    }>;
  };
}

// Filter preset interface
export interface FilterPreset {
  id?: number;
  name: string;
  filters: Partial<FilterState>;
  post_type: string;
  is_default: boolean;
  user_id?: number;
  created_at?: string;
  updated_at?: string;
}

// Export options interface
export interface ExportOptions {
  format: 'csv' | 'excel';
  filters: Partial<FilterState>;
  columns: string[];
  filename?: string;
}

// Date preset options
export interface DatePreset {
  label: string;
  value: string;
  date_from: string;
  date_to: string;
}

// Export empty object to make this a module
export {};
