import { render, screen, fireEvent } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import Pagination from '../../src/components/Pagination';

describe('Pagination', () => {
  const defaultProps = {
    currentPage: 1,
    totalPages: 5,
    onPageChange: jest.fn(),
  };

  beforeEach(() => {
    jest.clearAllMocks();
  });

  it('should render page information', () => {
    render(<Pagination {...defaultProps} />);
    
    expect(screen.getByText('Page 1 of 5')).toBeInTheDocument();
  });

  it('should render Previous and Next buttons', () => {
    render(<Pagination {...defaultProps} />);
    
    expect(screen.getByText('‹ Previous')).toBeInTheDocument();
    expect(screen.getByText('Next ›')).toBeInTheDocument();
  });

  it('should disable Previous button on first page', () => {
    render(<Pagination {...defaultProps} currentPage={1} />);
    
    const prevButton = screen.getByText('‹ Previous');
    expect(prevButton).toBeDisabled();
  });

  it('should disable Next button on last page', () => {
    render(<Pagination {...defaultProps} currentPage={5} totalPages={5} />);
    
    const nextButton = screen.getByText('Next ›');
    expect(nextButton).toBeDisabled();
  });

  it('should call onPageChange when clicking Previous', async () => {
    const user = userEvent.setup();
    const onPageChange = jest.fn();
    
    render(
      <Pagination {...defaultProps} currentPage={3} onPageChange={onPageChange} />
    );
    
    const prevButton = screen.getByText('‹ Previous');
    await user.click(prevButton);
    
    expect(onPageChange).toHaveBeenCalledWith(2);
  });

  it('should call onPageChange when clicking Next', async () => {
    const user = userEvent.setup();
    const onPageChange = jest.fn();
    
    render(
      <Pagination {...defaultProps} currentPage={3} onPageChange={onPageChange} />
    );
    
    const nextButton = screen.getByText('Next ›');
    await user.click(nextButton);
    
    expect(onPageChange).toHaveBeenCalledWith(4);
  });

  it('should render page numbers', () => {
    render(<Pagination {...defaultProps} currentPage={3} totalPages={5} />);
    
    expect(screen.getByText('1')).toBeInTheDocument();
    expect(screen.getByText('2')).toBeInTheDocument();
    expect(screen.getByText('3')).toBeInTheDocument();
    expect(screen.getByText('4')).toBeInTheDocument();
    expect(screen.getByText('5')).toBeInTheDocument();
  });

  it('should highlight current page', () => {
    render(<Pagination {...defaultProps} currentPage={3} totalPages={5} />);
    
    const currentPageButton = screen.getByText('3');
    expect(currentPageButton).toHaveClass('bg-wp-blue', 'text-white');
  });

  it('should call onPageChange when clicking page number', async () => {
    const user = userEvent.setup();
    const onPageChange = jest.fn();
    
    render(
      <Pagination {...defaultProps} currentPage={1} onPageChange={onPageChange} />
    );
    
    const pageButton = screen.getByText('3');
    await user.click(pageButton);
    
    expect(onPageChange).toHaveBeenCalledWith(3);
  });

  it('should show ellipsis for large page counts', () => {
    render(<Pagination {...defaultProps} currentPage={10} totalPages={20} />);
    
    const ellipsis = screen.getAllByText('...');
    expect(ellipsis.length).toBeGreaterThan(0);
  });

  it('should handle single page', () => {
    render(<Pagination {...defaultProps} currentPage={1} totalPages={1} />);
    
    expect(screen.getByText('Page 1 of 1')).toBeInTheDocument();
    expect(screen.getByText('‹ Previous')).toBeDisabled();
    expect(screen.getByText('Next ›')).toBeDisabled();
  });

  it('should handle edge case with zero pages', () => {
    render(<Pagination {...defaultProps} currentPage={1} totalPages={0} />);
    
    expect(screen.getByText('Page 1 of 0')).toBeInTheDocument();
  });
});
