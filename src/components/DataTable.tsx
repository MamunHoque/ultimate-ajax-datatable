import { useState } from 'react';
import { usePosts } from '../hooks/usePosts';
import { FilterState, Post } from '../types';
import LoadingSpinner from './LoadingSpinner';
import Pagination from './Pagination';
import BulkActions from './BulkActions';
import PostRow from './PostRow';

interface DataTableProps {
  filters: FilterState;
  onFilterChange: (filters: Partial<FilterState>) => void;
}

const DataTable = ({ filters, onFilterChange }: DataTableProps) => {
  const [selectedPosts, setSelectedPosts] = useState<number[]>([]);
  const { data, isLoading, error, isFetching } = usePosts(filters);

  const handleSelectAll = (checked: boolean) => {
    if (checked && data?.posts) {
      setSelectedPosts(data.posts.map(post => post.id));
    } else {
      setSelectedPosts([]);
    }
  };

  const handleSelectPost = (postId: number, checked: boolean) => {
    if (checked) {
      setSelectedPosts(prev => [...prev, postId]);
    } else {
      setSelectedPosts(prev => prev.filter(id => id !== postId));
    }
  };

  const handleSort = (column: string) => {
    const newOrder = filters.orderby === column && filters.order === 'DESC' ? 'ASC' : 'DESC';
    onFilterChange({ orderby: column, order: newOrder, page: 1 });
  };

  const handlePageChange = (page: number) => {
    onFilterChange({ page });
  };

  const handlePerPageChange = (perPage: number) => {
    onFilterChange({ per_page: perPage, page: 1 });
  };

  const getSortIcon = (column: string) => {
    if (filters.orderby !== column) {
      return (
        <svg className="w-4 h-4 text-wp-text-light" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4" />
        </svg>
      );
    }
    
    return filters.order === 'ASC' ? (
      <svg className="w-4 h-4 text-wp-blue" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M5 15l7-7 7 7" />
      </svg>
    ) : (
      <svg className="w-4 h-4 text-wp-blue" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M19 9l-7 7-7-7" />
      </svg>
    );
  };

  if (error) {
    return (
      <div className="uadt-error">
        <p>Error loading posts: {error.message}</p>
        <button 
          onClick={() => window.location.reload()} 
          className="uadt-button-secondary mt-2"
        >
          Retry
        </button>
      </div>
    );
  }

  return (
    <div className="bg-white border border-wp-gray-dark rounded-lg">
      {/* Table Header with Bulk Actions */}
      <div className="p-4 border-b border-wp-gray-dark">
        <div className="flex items-center justify-between">
          <div className="flex items-center gap-4">
            {window.uadtAdmin?.settings?.enableBulkActions && (
              <BulkActions
                selectedPosts={selectedPosts}
                onSelectionChange={setSelectedPosts}
                disabled={isLoading}
              />
            )}
            
            {data && (
              <div className="text-sm text-wp-text-light">
                {data.total} {data.total === 1 ? 'item' : 'items'} found
                {data.cached && (
                  <span className="ml-2 text-xs bg-wp-gray px-2 py-1 rounded">
                    Cached ({data.query_time}s)
                  </span>
                )}
              </div>
            )}
          </div>

          <div className="flex items-center gap-2">
            <label className="text-sm text-wp-text-light">
              Per page:
            </label>
            <select
              className="uadt-select w-20"
              value={filters.per_page}
              onChange={(e) => handlePerPageChange(Number(e.target.value))}
            >
              <option value={25}>25</option>
              <option value={50}>50</option>
              <option value={100}>100</option>
            </select>
          </div>
        </div>
      </div>

      {/* Loading State */}
      {isLoading && !data && (
        <div className="p-8">
          <LoadingSpinner message={window.uadtAdmin?.strings?.loading || 'Loading...'} />
        </div>
      )}

      {/* Table */}
      {data && (
        <>
          <div className="overflow-x-auto">
            <table className="uadt-table">
              <thead>
                <tr>
                  {window.uadtAdmin?.settings?.enableBulkActions && (
                    <th className="w-12">
                      <input
                        type="checkbox"
                        checked={selectedPosts.length === data.posts.length && data.posts.length > 0}
                        onChange={(e) => handleSelectAll(e.target.checked)}
                        className="rounded border-wp-gray-dark"
                      />
                    </th>
                  )}
                  
                  <th>
                    <button
                      onClick={() => handleSort('title')}
                      className="flex items-center gap-1 hover:text-wp-blue"
                    >
                      Title
                      {getSortIcon('title')}
                    </button>
                  </th>
                  
                  <th>
                    <button
                      onClick={() => handleSort('author')}
                      className="flex items-center gap-1 hover:text-wp-blue"
                    >
                      Author
                      {getSortIcon('author')}
                    </button>
                  </th>
                  
                  <th>Status</th>
                  
                  {filters.post_type === 'post' && (
                    <>
                      <th>Categories</th>
                      <th>Tags</th>
                    </>
                  )}
                  
                  <th>
                    <button
                      onClick={() => handleSort('date')}
                      className="flex items-center gap-1 hover:text-wp-blue"
                    >
                      Date
                      {getSortIcon('date')}
                    </button>
                  </th>
                  
                  <th>Actions</th>
                </tr>
              </thead>
              
              <tbody>
                {data.posts.length === 0 ? (
                  <tr>
                    <td colSpan={8} className="text-center py-8 text-wp-text-light">
                      {window.uadtAdmin?.strings?.noResults || 'No results found'}
                    </td>
                  </tr>
                ) : (
                  data.posts.map((post: Post) => (
                    <PostRow
                      key={post.id}
                      post={post}
                      isSelected={selectedPosts.includes(post.id)}
                      onSelect={(checked) => handleSelectPost(post.id, checked)}
                      showBulkActions={window.uadtAdmin?.settings?.enableBulkActions}
                      showCategories={filters.post_type === 'post'}
                    />
                  ))
                )}
              </tbody>
            </table>
          </div>

          {/* Pagination */}
          {data.pages > 1 && (
            <div className="p-4 border-t border-wp-gray-dark">
              <Pagination
                currentPage={data.current_page}
                totalPages={data.pages}
                onPageChange={handlePageChange}
              />
            </div>
          )}
        </>
      )}

      {/* Loading Overlay */}
      {isFetching && data && (
        <div className="relative">
          <div className="absolute inset-0 bg-white bg-opacity-50 flex items-center justify-center z-10">
            <LoadingSpinner size="small" />
          </div>
        </div>
      )}
    </div>
  );
};

export default DataTable;
