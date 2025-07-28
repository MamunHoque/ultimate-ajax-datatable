import { useQuery } from '@tanstack/react-query';
import { getPosts } from '../utils/api';
import { FilterState, PostsResponse } from '../types';

export const usePosts = (filters: FilterState) => {
  return useQuery<PostsResponse, Error>({
    queryKey: ['posts', filters],
    queryFn: () => getPosts(filters),
    staleTime: 5 * 60 * 1000, // 5 minutes
    retry: 2,
    refetchOnWindowFocus: false,
    // Keep previous data while loading new data (React Query v5 syntax)
    placeholderData: (previousData) => previousData,
  });
};
