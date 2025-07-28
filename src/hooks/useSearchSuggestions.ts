import { useQuery } from '@tanstack/react-query';
import { getSearchSuggestions } from '../utils/api';
import { SearchSuggestions } from '../types';

export const useSearchSuggestions = (query: string, postType: string = 'post') => {
  return useQuery<SearchSuggestions, Error>({
    queryKey: ['searchSuggestions', query, postType],
    queryFn: () => getSearchSuggestions(query, postType),
    enabled: query.length >= 2, // Only run query if search term is at least 2 characters
    staleTime: 30 * 60 * 1000, // 30 minutes
    retry: 1,
    refetchOnWindowFocus: false,
  });
};
