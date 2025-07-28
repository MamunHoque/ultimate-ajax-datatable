import { useQuery } from '@tanstack/react-query';
import { getFilterOptions } from '../utils/api';
import { FilterOptions } from '../types';

export const useFilterOptions = (postType: string = 'post') => {
  return useQuery<FilterOptions, Error>({
    queryKey: ['filterOptions', postType],
    queryFn: () => getFilterOptions(postType),
    staleTime: 10 * 60 * 1000, // 10 minutes - filter options don't change often
    retry: 2,
    refetchOnWindowFocus: false,
  });
};
