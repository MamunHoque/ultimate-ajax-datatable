import { buildQueryString, debounce, formatDateForAPI, parseDateFromAPI } from '../../src/utils/api';
import { FilterState } from '../../src/types';

describe('API Utilities', () => {
  describe('buildQueryString', () => {
    it('should build query string from filters', () => {
      const filters: Partial<FilterState> = {
        search: 'test',
        post_type: 'post',
        page: 1,
        per_page: 25,
      };

      const queryString = buildQueryString(filters);
      expect(queryString).toContain('search=test');
      expect(queryString).toContain('post_type=post');
      expect(queryString).toContain('page=1');
      expect(queryString).toContain('per_page=25');
    });

    it('should skip empty values', () => {
      const filters: Partial<FilterState> = {
        search: '',
        post_type: 'post',
        author: null as any,
        page: 1,
      };

      const queryString = buildQueryString(filters);
      expect(queryString).not.toContain('search=');
      expect(queryString).not.toContain('author=');
      expect(queryString).toContain('post_type=post');
      expect(queryString).toContain('page=1');
    });
  });

  describe('debounce', () => {
    jest.useFakeTimers();

    it('should debounce function calls', () => {
      const mockFn = jest.fn();
      const debouncedFn = debounce(mockFn, 300);

      debouncedFn('test1');
      debouncedFn('test2');
      debouncedFn('test3');

      expect(mockFn).not.toHaveBeenCalled();

      jest.advanceTimersByTime(300);

      expect(mockFn).toHaveBeenCalledTimes(1);
      expect(mockFn).toHaveBeenCalledWith('test3');
    });

    it('should reset timer on subsequent calls', () => {
      const mockFn = jest.fn();
      const debouncedFn = debounce(mockFn, 300);

      debouncedFn('test1');
      jest.advanceTimersByTime(200);
      
      debouncedFn('test2');
      jest.advanceTimersByTime(200);
      
      expect(mockFn).not.toHaveBeenCalled();
      
      jest.advanceTimersByTime(100);
      
      expect(mockFn).toHaveBeenCalledTimes(1);
      expect(mockFn).toHaveBeenCalledWith('test2');
    });

    afterEach(() => {
      jest.clearAllTimers();
    });
  });

  describe('formatDateForAPI', () => {
    it('should format valid date strings', () => {
      const date = '2023-12-25T10:30:00.000Z';
      const formatted = formatDateForAPI(date);
      expect(formatted).toBe('2023-12-25');
    });

    it('should handle empty strings', () => {
      const formatted = formatDateForAPI('');
      expect(formatted).toBe('');
    });

    it('should return original string for invalid dates', () => {
      const invalidDate = 'invalid-date';
      const formatted = formatDateForAPI(invalidDate);
      expect(formatted).toBe(invalidDate);
    });

    it('should handle Date objects', () => {
      const date = new Date('2023-12-25T10:30:00.000Z');
      const formatted = formatDateForAPI(date.toISOString());
      expect(formatted).toBe('2023-12-25');
    });
  });

  describe('parseDateFromAPI', () => {
    it('should parse valid date strings', () => {
      const dateString = '2023-12-25T10:30:00.000Z';
      const parsed = parseDateFromAPI(dateString);
      expect(parsed).toBeInstanceOf(Date);
      expect(parsed?.getFullYear()).toBe(2023);
      expect(parsed?.getMonth()).toBe(11); // December is month 11
      expect(parsed?.getDate()).toBe(25);
    });

    it('should return null for empty strings', () => {
      const parsed = parseDateFromAPI('');
      expect(parsed).toBeNull();
    });

    it('should return null for invalid dates', () => {
      const parsed = parseDateFromAPI('invalid-date');
      expect(parsed).toBeNull();
    });

    it('should handle ISO date strings', () => {
      const dateString = '2023-12-25';
      const parsed = parseDateFromAPI(dateString);
      expect(parsed).toBeInstanceOf(Date);
      expect(parsed?.getFullYear()).toBe(2023);
    });
  });
});
