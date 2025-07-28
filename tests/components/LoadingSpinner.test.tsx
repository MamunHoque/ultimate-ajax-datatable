import { render, screen } from '@testing-library/react';
import LoadingSpinner from '../../src/components/LoadingSpinner';

describe('LoadingSpinner', () => {
  it('should render with default props', () => {
    render(<LoadingSpinner />);
    
    const spinner = screen.getByRole('img', { hidden: true });
    expect(spinner).toBeInTheDocument();
    expect(spinner).toHaveClass('h-6', 'w-6'); // medium size
  });

  it('should render with custom message', () => {
    const message = 'Loading posts...';
    render(<LoadingSpinner message={message} />);
    
    expect(screen.getByText(message)).toBeInTheDocument();
  });

  it('should render with small size', () => {
    render(<LoadingSpinner size="small" />);
    
    const spinner = screen.getByRole('img', { hidden: true });
    expect(spinner).toHaveClass('h-4', 'w-4');
  });

  it('should render with large size', () => {
    render(<LoadingSpinner size="large" />);
    
    const spinner = screen.getByRole('img', { hidden: true });
    expect(spinner).toHaveClass('h-8', 'w-8');
  });

  it('should have proper CSS classes', () => {
    render(<LoadingSpinner />);
    
    const container = screen.getByRole('img', { hidden: true }).parentElement;
    expect(container).toHaveClass('uadt-loading');
    
    const spinner = screen.getByRole('img', { hidden: true });
    expect(spinner).toHaveClass('uadt-spinner');
  });

  it('should not render message when not provided', () => {
    render(<LoadingSpinner />);
    
    const container = screen.getByRole('img', { hidden: true }).parentElement;
    expect(container?.textContent).toBe('');
  });
});
