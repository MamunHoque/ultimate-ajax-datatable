interface PaginationProps {
  currentPage: number;
  totalPages: number;
  onPageChange: (page: number) => void;
}

const Pagination = ({ currentPage, totalPages, onPageChange }: PaginationProps) => {
  const getVisiblePages = () => {
    const delta = 2;
    const range = [];
    const rangeWithDots = [];

    for (let i = Math.max(2, currentPage - delta); i <= Math.min(totalPages - 1, currentPage + delta); i++) {
      range.push(i);
    }

    if (currentPage - delta > 2) {
      rangeWithDots.push(1, '...');
    } else {
      rangeWithDots.push(1);
    }

    rangeWithDots.push(...range);

    if (currentPage + delta < totalPages - 1) {
      rangeWithDots.push('...', totalPages);
    } else {
      rangeWithDots.push(totalPages);
    }

    return rangeWithDots;
  };

  const visiblePages = getVisiblePages();

  return (
    <div className="flex items-center justify-between">
      <div className="text-sm text-wp-text-light">
        Page {currentPage} of {totalPages}
      </div>
      
      <div className="flex items-center gap-1">
        {/* Previous Button */}
        <button
          onClick={() => onPageChange(currentPage - 1)}
          disabled={currentPage === 1}
          className="px-3 py-1 text-sm border border-wp-gray-dark rounded hover:bg-wp-gray disabled:opacity-50 disabled:cursor-not-allowed"
        >
          ‹ Previous
        </button>

        {/* Page Numbers */}
        {visiblePages.map((page, index) => (
          <span key={index}>
            {page === '...' ? (
              <span className="px-3 py-1 text-sm text-wp-text-light">...</span>
            ) : (
              <button
                onClick={() => onPageChange(page as number)}
                className={`px-3 py-1 text-sm border rounded ${
                  currentPage === page
                    ? 'bg-wp-blue text-white border-wp-blue'
                    : 'border-wp-gray-dark hover:bg-wp-gray'
                }`}
              >
                {page}
              </button>
            )}
          </span>
        ))}

        {/* Next Button */}
        <button
          onClick={() => onPageChange(currentPage + 1)}
          disabled={currentPage === totalPages}
          className="px-3 py-1 text-sm border border-wp-gray-dark rounded hover:bg-wp-gray disabled:opacity-50 disabled:cursor-not-allowed"
        >
          Next ›
        </button>
      </div>
    </div>
  );
};

export default Pagination;
