import { useState, useRef, useEffect } from 'react';
import { useSearchSuggestions } from '../hooks/useSearchSuggestions';
import { debounce } from '../utils/api';

interface SearchInputProps {
  value: string;
  onChange: (value: string) => void;
  postType: string;
  placeholder?: string;
}

const SearchInput = ({ value, onChange, postType, placeholder }: SearchInputProps) => {
  const [showSuggestions, setShowSuggestions] = useState(false);
  const [suggestionQuery, setSuggestionQuery] = useState('');
  const inputRef = useRef<HTMLInputElement>(null);
  const suggestionsRef = useRef<HTMLDivElement>(null);

  // Debounced suggestion query to avoid too many API calls
  const debouncedSetSuggestionQuery = debounce((query: string) => {
    setSuggestionQuery(query);
  }, 300);

  const { data: suggestions } = useSearchSuggestions(suggestionQuery, postType);

  useEffect(() => {
    if (value.length >= 2) {
      debouncedSetSuggestionQuery(value);
    } else {
      setSuggestionQuery('');
      setShowSuggestions(false);
    }
  }, [value, debouncedSetSuggestionQuery]);

  useEffect(() => {
    const handleClickOutside = (event: MouseEvent) => {
      if (
        suggestionsRef.current &&
        !suggestionsRef.current.contains(event.target as Node) &&
        !inputRef.current?.contains(event.target as Node)
      ) {
        setShowSuggestions(false);
      }
    };

    document.addEventListener('mousedown', handleClickOutside);
    return () => document.removeEventListener('mousedown', handleClickOutside);
  }, []);

  const handleInputChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    const newValue = e.target.value;
    onChange(newValue);
    
    if (newValue.length >= 2) {
      setShowSuggestions(true);
    } else {
      setShowSuggestions(false);
    }
  };

  const handleSuggestionClick = (suggestion: string) => {
    onChange(suggestion);
    setShowSuggestions(false);
    inputRef.current?.focus();
  };

  const handleKeyDown = (e: React.KeyboardEvent) => {
    if (e.key === 'Escape') {
      setShowSuggestions(false);
    }
  };

  const hasSuggestions = suggestions?.suggestions && suggestions.suggestions.length > 0;

  return (
    <div className="relative">
      <div className="relative">
        <input
          ref={inputRef}
          type="text"
          className="uadt-input pr-10"
          value={value}
          onChange={handleInputChange}
          onKeyDown={handleKeyDown}
          onFocus={() => value.length >= 2 && setShowSuggestions(true)}
          placeholder={placeholder}
        />
        <div className="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
          <svg
            className="h-4 w-4 text-wp-text-light"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
          >
            <path
              strokeLinecap="round"
              strokeLinejoin="round"
              strokeWidth={2}
              d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"
            />
          </svg>
        </div>
      </div>

      {showSuggestions && hasSuggestions && (
        <div
          ref={suggestionsRef}
          className="absolute z-10 w-full mt-1 bg-white border border-wp-gray-dark rounded-md shadow-lg max-h-60 overflow-auto"
        >
          {suggestions.suggestions.map((suggestion, index) => (
            <button
              key={index}
              className="w-full px-4 py-2 text-left text-sm text-wp-text hover:bg-wp-gray hover:bg-opacity-50 focus:outline-none focus:bg-wp-gray focus:bg-opacity-50"
              onClick={() => handleSuggestionClick(suggestion)}
            >
              <span className="block truncate">{suggestion}</span>
            </button>
          ))}
        </div>
      )}
    </div>
  );
};

export default SearchInput;
