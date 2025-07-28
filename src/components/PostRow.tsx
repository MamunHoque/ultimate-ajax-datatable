import { Post } from '../types';

interface PostRowProps {
  post: Post;
  isSelected: boolean;
  onSelect: (checked: boolean) => void;
  showBulkActions?: boolean;
  showCategories?: boolean;
}

const PostRow = ({ post, isSelected, onSelect, showBulkActions, showCategories }: PostRowProps) => {
  const getStatusBadge = (status: string, label: string) => {
    const statusColors = {
      publish: 'bg-green-100 text-green-800',
      draft: 'bg-yellow-100 text-yellow-800',
      private: 'bg-blue-100 text-blue-800',
      pending: 'bg-orange-100 text-orange-800',
      trash: 'bg-red-100 text-red-800',
    };

    const colorClass = statusColors[status as keyof typeof statusColors] || 'bg-gray-100 text-gray-800';

    return (
      <span className={`inline-flex px-2 py-1 text-xs font-medium rounded-full ${colorClass}`}>
        {label}
      </span>
    );
  };

  const truncateText = (text: string, maxLength: number = 50) => {
    if (text.length <= maxLength) return text;
    return text.substring(0, maxLength) + '...';
  };

  return (
    <tr className="hover:bg-wp-gray hover:bg-opacity-30">
      {showBulkActions && (
        <td>
          <input
            type="checkbox"
            checked={isSelected}
            onChange={(e) => onSelect(e.target.checked)}
            className="rounded border-wp-gray-dark"
          />
        </td>
      )}

      <td>
        <div className="flex items-start gap-2">
          {post.featured_image && (
            <img
              src={post.featured_image}
              alt=""
              className="w-10 h-10 object-cover rounded"
            />
          )}
          <div>
            <div className="font-medium text-wp-text">
              {post.title || '(No title)'}
            </div>
            {post.excerpt && (
              <div className="text-sm text-wp-text-light mt-1">
                {truncateText(post.excerpt)}
              </div>
            )}
          </div>
        </div>
      </td>

      <td>
        <div className="text-sm">
          <div className="font-medium text-wp-text">{post.author}</div>
        </div>
      </td>

      <td>
        {getStatusBadge(post.status, post.status_label)}
      </td>

      {showCategories && (
        <>
          <td>
            <div className="flex flex-wrap gap-1">
              {post.categories.length > 0 ? (
                post.categories.slice(0, 2).map((category) => (
                  <span
                    key={category.id}
                    className="inline-flex px-2 py-1 text-xs bg-wp-gray rounded"
                  >
                    {category.name}
                  </span>
                ))
              ) : (
                <span className="text-wp-text-light text-sm">—</span>
              )}
              {post.categories.length > 2 && (
                <span className="text-xs text-wp-text-light">
                  +{post.categories.length - 2} more
                </span>
              )}
            </div>
          </td>

          <td>
            <div className="flex flex-wrap gap-1">
              {post.tags.length > 0 ? (
                post.tags.slice(0, 2).map((tag) => (
                  <span
                    key={tag.id}
                    className="inline-flex px-2 py-1 text-xs bg-wp-blue bg-opacity-10 text-wp-blue rounded"
                  >
                    {tag.name}
                  </span>
                ))
              ) : (
                <span className="text-wp-text-light text-sm">—</span>
              )}
              {post.tags.length > 2 && (
                <span className="text-xs text-wp-text-light">
                  +{post.tags.length - 2} more
                </span>
              )}
            </div>
          </td>
        </>
      )}

      <td>
        <div className="text-sm">
          <div className="text-wp-text">{post.date_formatted}</div>
          <div className="text-wp-text-light text-xs">
            Modified: {post.modified_formatted}
          </div>
        </div>
      </td>

      <td>
        <div className="flex items-center gap-2">
          {post.can_edit && post.edit_link && (
            <a
              href={post.edit_link}
              className="text-wp-blue hover:text-wp-blue-dark text-sm"
              target="_blank"
              rel="noopener noreferrer"
            >
              Edit
            </a>
          )}
          
          {post.view_link && (
            <a
              href={post.view_link}
              className="text-wp-blue hover:text-wp-blue-dark text-sm"
              target="_blank"
              rel="noopener noreferrer"
            >
              View
            </a>
          )}
        </div>
      </td>
    </tr>
  );
};

export default PostRow;
