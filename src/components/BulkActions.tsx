import { useState } from 'react';
import { useMutation, useQueryClient } from '@tanstack/react-query';
import { performBulkAction } from '../utils/api';

interface BulkActionsProps {
  selectedPosts: number[];
  onSelectionChange: (posts: number[]) => void;
  disabled?: boolean;
}

const BulkActions = ({ selectedPosts, onSelectionChange, disabled }: BulkActionsProps) => {
  const [action, setAction] = useState('');
  const queryClient = useQueryClient();

  const bulkMutation = useMutation({
    mutationFn: ({ action, postIds }: { action: string; postIds: number[] }) =>
      performBulkAction(action, postIds),
    onSuccess: (data) => {
      // Invalidate posts query to refresh the table
      queryClient.invalidateQueries({ queryKey: ['posts'] });
      
      // Clear selection
      onSelectionChange([]);
      setAction('');
      
      // Show success message
      const { success_count, error_count } = data.summary;
      const message = error_count > 0 
        ? `${success_count} items processed successfully, ${error_count} failed`
        : `${success_count} items processed successfully`;
      
      // You could implement a toast notification here
      alert(message);
    },
    onError: (error) => {
      console.error('Bulk action failed:', error);
      alert('Bulk action failed. Please try again.');
    },
  });

  const handleApply = () => {
    if (!action || selectedPosts.length === 0) {
      return;
    }

    // Confirm destructive actions
    if (['delete', 'trash'].includes(action)) {
      const confirmMessage = action === 'delete' 
        ? `Are you sure you want to permanently delete ${selectedPosts.length} items?`
        : `Are you sure you want to move ${selectedPosts.length} items to trash?`;
      
      if (!confirm(confirmMessage)) {
        return;
      }
    }

    bulkMutation.mutate({ action, postIds: selectedPosts });
  };

  const isDisabled = disabled || bulkMutation.isPending || selectedPosts.length === 0;

  return (
    <div className="flex items-center gap-2">
      <select
        className="uadt-select"
        value={action}
        onChange={(e) => setAction(e.target.value)}
        disabled={isDisabled}
      >
        <option value="">Bulk Actions</option>
        <option value="publish">Publish</option>
        <option value="draft">Move to Draft</option>
        <option value="private">Make Private</option>
        <option value="trash">Move to Trash</option>
        {window.uadtAdmin?.capabilities?.delete_posts && (
          <option value="delete">Delete Permanently</option>
        )}
      </select>

      <button
        onClick={handleApply}
        disabled={isDisabled || !action}
        className="uadt-button-secondary disabled:opacity-50 disabled:cursor-not-allowed"
      >
        {bulkMutation.isPending ? 'Processing...' : 'Apply'}
      </button>

      {selectedPosts.length > 0 && (
        <span className="text-sm text-wp-text-light">
          {selectedPosts.length} selected
        </span>
      )}
    </div>
  );
};

export default BulkActions;
