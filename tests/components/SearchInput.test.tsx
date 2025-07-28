import { render, screen, fireEvent, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import SearchInput from '../../src/components/SearchInput';

// Mock the API
jest.mock('../../src/utils/api', () => ({
  getSearchSuggestions: jest.fn(() => 
    Promise.resolve({ suggestions: ['Test Post 1', 'Test Post 2'] })
  ),
}));

const createWrapper = () => {
  const queryClient = new QueryClient({
    defaultOptions: {
      queries: { retry: false },
      mutations: { retry: false },
    },
  });
  
  return ({ children }: { children: React.ReactNode }) => (
    <QueryClientProvider client={queryClient}>
      {children}
    </QueryClientProvider>
  );
};

describe('SearchInput', () => {
  const defaultProps = {
    value: '',
    onChange: jest.fn(),
    postType: 'post',
    placeholder: 'Search posts...',
  };

  beforeEach(() => {
    jest.clearAllMocks();
  });

  it('should render with placeholder', () => {
    render(<SearchInput {...defaultProps} />, { wrapper: createWrapper() });
    
    const input = screen.getByPlaceholderText('Search posts...');
    expect(input).toBeInTheDocument();
  });

  it('should call onChange when typing', async () => {
    const user = userEvent.setup();
    const onChange = jest.fn();
    
    render(
      <SearchInput {...defaultProps} onChange={onChange} />, 
      { wrapper: createWrapper() }
    );
    
    const input = screen.getByPlaceholderText('Search posts...');
    await user.type(input, 'test');
    
    expect(onChange).toHaveBeenCalledWith('test');
  });

  it('should show search icon', () => {
    render(<SearchInput {...defaultProps} />, { wrapper: createWrapper() });
    
    const searchIcon = screen.getByRole('img', { hidden: true });
    expect(searchIcon).toBeInTheDocument();
  });

  it('should display current value', () => {
    render(
      <SearchInput {...defaultProps} value="test query" />, 
      { wrapper: createWrapper() }
    );
    
    const input = screen.getByDisplayValue('test query');
    expect(input).toBeInTheDocument();
  });

  it('should show suggestions when typing more than 2 characters', async () => {
    const user = userEvent.setup();
    
    render(<SearchInput {...defaultProps} />, { wrapper: createWrapper() });
    
    const input = screen.getByPlaceholderText('Search posts...');
    await user.type(input, 'test');
    
    // Wait for suggestions to appear
    await waitFor(() => {
      expect(screen.getByText('Test Post 1')).toBeInTheDocument();
      expect(screen.getByText('Test Post 2')).toBeInTheDocument();
    });
  });

  it('should hide suggestions when clicking outside', async () => {
    const user = userEvent.setup();
    
    render(
      <div>
        <SearchInput {...defaultProps} value="test" />
        <button>Outside</button>
      </div>, 
      { wrapper: createWrapper() }
    );
    
    // Wait for suggestions to appear
    await waitFor(() => {
      expect(screen.getByText('Test Post 1')).toBeInTheDocument();
    });
    
    // Click outside
    const outsideButton = screen.getByText('Outside');
    await user.click(outsideButton);
    
    // Suggestions should be hidden
    await waitFor(() => {
      expect(screen.queryByText('Test Post 1')).not.toBeInTheDocument();
    });
  });

  it('should hide suggestions when pressing Escape', async () => {
    const user = userEvent.setup();
    
    render(<SearchInput {...defaultProps} value="test" />, { wrapper: createWrapper() });
    
    const input = screen.getByPlaceholderText('Search posts...');
    
    // Wait for suggestions to appear
    await waitFor(() => {
      expect(screen.getByText('Test Post 1')).toBeInTheDocument();
    });
    
    // Press Escape
    await user.type(input, '{Escape}');
    
    // Suggestions should be hidden
    await waitFor(() => {
      expect(screen.queryByText('Test Post 1')).not.toBeInTheDocument();
    });
  });

  it('should select suggestion when clicked', async () => {
    const user = userEvent.setup();
    const onChange = jest.fn();
    
    render(
      <SearchInput {...defaultProps} value="test" onChange={onChange} />, 
      { wrapper: createWrapper() }
    );
    
    // Wait for suggestions to appear
    await waitFor(() => {
      expect(screen.getByText('Test Post 1')).toBeInTheDocument();
    });
    
    // Click on suggestion
    const suggestion = screen.getByText('Test Post 1');
    await user.click(suggestion);
    
    expect(onChange).toHaveBeenCalledWith('Test Post 1');
  });
});
