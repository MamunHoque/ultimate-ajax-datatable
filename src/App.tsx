import { useState } from 'react';
import DataTable from './components/DataTable';
import FilterPanel from './components/FilterPanel';
import { FilterState } from './types';

const App = () => {
  const [filters, setFilters] = useState<FilterState>({
    search: '',
    post_type: 'post',
    author: '',
    status: 'publish',
    category: '',
    tag: '',
    date_from: '',
    date_to: '',
    page: 1,
    per_page: 25,
    orderby: 'date',
    order: 'DESC',
  });

  const handleFilterChange = (newFilters: Partial<FilterState>) => {
    setFilters(prev => ({
      ...prev,
      ...newFilters,
      page: newFilters.page || 1, // Reset to page 1 when filters change (except pagination)
    }));
  };

  const handleClearFilters = () => {
    setFilters({
      search: '',
      post_type: 'post',
      author: '',
      status: 'publish',
      category: '',
      tag: '',
      date_from: '',
      date_to: '',
      page: 1,
      per_page: 25,
      orderby: 'date',
      order: 'DESC',
    });
  };

  return (
    <div className="uadt-app">
      <div className="mb-6">
        <h1 className="text-2xl font-semibold text-wp-text mb-2">
          DataTable Manager
        </h1>
        <p className="text-wp-text-light">
          Manage your posts with advanced filtering and bulk operations.
        </p>
      </div>

      <FilterPanel
        filters={filters}
        onFilterChange={handleFilterChange}
        onClearFilters={handleClearFilters}
      />

      <DataTable
        filters={filters}
        onFilterChange={handleFilterChange}
      />
    </div>
  );
};

export default App;
