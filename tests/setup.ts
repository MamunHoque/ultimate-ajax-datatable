import '@testing-library/jest-dom';

// Mock WordPress globals
global.window.uadtAdmin = {
  apiUrl: '/wp-json/uadt/v1/',
  nonce: 'test-nonce',
  currentUser: 1,
  capabilities: {
    edit_posts: true,
    delete_posts: true,
    manage_options: true,
  },
  settings: {
    enabledPostTypes: ['post', 'page'],
    itemsPerPage: 25,
    enableSearch: true,
    enableFilters: true,
    enableBulkActions: true,
    maxItemsPerPage: 100,
  },
  strings: {
    loading: 'Loading...',
    error: 'An error occurred',
    noResults: 'No results found',
    search: 'Search...',
    filters: 'Filters',
    clearFilters: 'Clear Filters',
    bulkActions: 'Bulk Actions',
    selectAll: 'Select All',
    export: 'Export',
  },
};

// Mock WordPress API fetch
jest.mock('@wordpress/api-fetch', () => {
  return jest.fn(() => Promise.resolve({}));
});

// Mock WordPress i18n
jest.mock('@wordpress/i18n', () => ({
  __: (text: string) => text,
  _n: (single: string, plural: string, number: number) => number === 1 ? single : plural,
  sprintf: (format: string, ...args: any[]) => format,
}));

// Mock console methods to reduce noise in tests
global.console = {
  ...console,
  warn: jest.fn(),
  error: jest.fn(),
};

// Mock IntersectionObserver
global.IntersectionObserver = class IntersectionObserver {
  constructor() {}
  disconnect() {}
  observe() {}
  unobserve() {}
};

// Mock ResizeObserver
global.ResizeObserver = class ResizeObserver {
  constructor() {}
  disconnect() {}
  observe() {}
  unobserve() {}
};
