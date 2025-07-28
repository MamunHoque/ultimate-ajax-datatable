import { useState, useCallback } from 'react';
import { FilterState } from '../types';
import { useFilterOptions } from '../hooks/useFilterOptions';
import SearchInput from './SearchInput';
import DateRangePicker from './DateRangePicker';
import { debounce } from '../utils/api';

interface FilterPanelProps {
  filters: FilterState;
  onFilterChange: (filters: Partial<FilterState>) => void;
  onClearFilters: () => void;
}

const FilterPanel = ({ filters, onFilterChange, onClearFilters }: FilterPanelProps) => {
  const [isExpanded, setIsExpanded] = useState(true);
  const { data: filterOptions, isLoading: optionsLoading } = useFilterOptions(filters.post_type);

  // Debounced filter change to avoid too many API calls
  const debouncedFilterChange = useCallback(
    debounce((newFilters: Partial<FilterState>) => {
      onFilterChange(newFilters);
    }, 300),
    [onFilterChange]
  );

  const handleInputChange = (key: keyof FilterState, value: string | number) => {
    if (key === 'search') {
      // For search, use debounced change
      debouncedFilterChange({ [key]: String(value), page: 1 });
    } else {
      // For other filters, change immediately
      onFilterChange({ [key]: String(value), page: 1 });
    }
  };

  const hasActiveFilters = () => {
    return filters.search || 
           filters.author || 
           filters.status !== 'publish' || 
           filters.category || 
           filters.tag || 
           filters.date_from || 
           filters.date_to;
  };

  return (
    <div className="uadt-filter-panel">
      <div className="flex items-center justify-between mb-4">
        <h3 className="text-lg font-medium text-wp-text">
          {window.uadtAdmin?.strings?.filters || 'Filters'}
        </h3>
        <div className="flex items-center gap-2">
          {hasActiveFilters() && (
            <button
              onClick={onClearFilters}
              className="text-sm text-wp-blue hover:text-wp-blue-dark"
            >
              {window.uadtAdmin?.strings?.clearFilters || 'Clear Filters'}
            </button>
          )}
          <button
            onClick={() => setIsExpanded(!isExpanded)}
            className="text-wp-text-light hover:text-wp-text"
          >
            {isExpanded ? '−' : '+'}
          </button>
        </div>
      </div>

      {isExpanded && (
        <div className="space-y-4">
          {/* Search Input */}
          <div className="uadt-filter-row">
            <div className="uadt-filter-group flex-1">
              <label className="uadt-filter-label">
                {window.uadtAdmin?.strings?.search || 'Search'}
              </label>
              <SearchInput
                value={filters.search}
                onChange={(value) => handleInputChange('search', value)}
                postType={filters.post_type}
                placeholder={window.uadtAdmin?.strings?.search || 'Search posts...'}
              />
            </div>
          </div>

          {/* Filter Row 1: Post Type, Author, Status */}
          <div className="uadt-filter-row">
            <div className="uadt-filter-group">
              <label className="uadt-filter-label">Post Type</label>
              <select
                className="uadt-select"
                value={filters.post_type}
                onChange={(e) => handleInputChange('post_type', e.target.value)}
              >
                {window.uadtAdmin?.settings?.enabledPostTypes?.map((postType) => (
                  <option key={postType} value={postType}>
                    {postType.charAt(0).toUpperCase() + postType.slice(1)}
                  </option>
                ))}
              </select>
            </div>

            <div className="uadt-filter-group">
              <label className="uadt-filter-label">Author</label>
              <select
                className="uadt-select"
                value={filters.author}
                onChange={(e) => handleInputChange('author', e.target.value)}
                disabled={optionsLoading}
              >
                <option value="">All Authors</option>
                {filterOptions?.authors?.map((author) => (
                  <option key={author.id} value={author.id}>
                    {author.name}
                  </option>
                ))}
              </select>
            </div>

            <div className="uadt-filter-group">
              <label className="uadt-filter-label">Status</label>
              <select
                className="uadt-select"
                value={filters.status}
                onChange={(e) => handleInputChange('status', e.target.value)}
                disabled={optionsLoading}
              >
                {filterOptions?.statuses?.map((status) => (
                  <option key={status.value} value={status.value}>
                    {status.label}
                  </option>
                ))}
              </select>
            </div>
          </div>

          {/* Filter Row 2: Categories, Tags */}
          {filters.post_type === 'post' && (
            <div className="uadt-filter-row">
              <div className="uadt-filter-group">
                <label className="uadt-filter-label">Category</label>
                <select
                  className="uadt-select"
                  value={filters.category}
                  onChange={(e) => handleInputChange('category', e.target.value)}
                  disabled={optionsLoading}
                >
                  <option value="">All Categories</option>
                  {filterOptions?.categories?.map((category) => (
                    <option key={category.id} value={category.slug}>
                      {category.name} {category.count ? `(${category.count})` : ''}
                    </option>
                  ))}
                </select>
              </div>

              <div className="uadt-filter-group">
                <label className="uadt-filter-label">Tag</label>
                <select
                  className="uadt-select"
                  value={filters.tag}
                  onChange={(e) => handleInputChange('tag', e.target.value)}
                  disabled={optionsLoading}
                >
                  <option value="">All Tags</option>
                  {filterOptions?.tags?.map((tag) => (
                    <option key={tag.id} value={tag.slug}>
                      {tag.name} {tag.count ? `(${tag.count})` : ''}
                    </option>
                  ))}
                </select>
              </div>
            </div>
          )}

          {/* Date Range Filter */}
          <div className="uadt-filter-row">
            <div className="uadt-filter-group">
              <label className="uadt-filter-label">Date Range</label>
              <DateRangePicker
                dateFrom={filters.date_from}
                dateTo={filters.date_to}
                onDateChange={(dateFrom, dateTo) => {
                  onFilterChange({ date_from: dateFrom, date_to: dateTo, page: 1 });
                }}
              />
            </div>
          </div>

          {/* Active Filters Summary */}
          {hasActiveFilters() && (
            <div className="mt-4 p-3 bg-wp-blue bg-opacity-5 rounded-md">
              <div className="text-sm text-wp-text-light">
                Active filters: {[
                  filters.search && `Search: "${filters.search}"`,
                  filters.author && `Author: ${filterOptions?.authors?.find(a => a.id.toString() === filters.author)?.name}`,
                  filters.status !== 'publish' && `Status: ${filterOptions?.statuses?.find(s => s.value === filters.status)?.label}`,
                  filters.category && `Category: ${filterOptions?.categories?.find(c => c.slug === filters.category)?.name}`,
                  filters.tag && `Tag: ${filterOptions?.tags?.find(t => t.slug === filters.tag)?.name}`,
                  (filters.date_from || filters.date_to) && 'Date range'
                ].filter(Boolean).join(', ')}
              </div>
            </div>
          )}
        </div>
      )}
    </div>
  );
};

export default FilterPanel;
