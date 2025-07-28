import React, { useState } from 'react';
import { FilterState, ExportOptions } from '../types';
import { exportData } from '../utils/api';

interface ExportComponentProps {
  filters: FilterState;
  totalPosts: number;
  onExportComplete?: (result: any) => void;
}

const EXPORT_FORMATS = [
  { value: 'csv', label: 'CSV', description: 'Comma-separated values' },
  { value: 'excel', label: 'Excel', description: 'Microsoft Excel format' },
];

const EXPORT_COLUMNS = [
  { value: 'id', label: 'Post ID', default: true },
  { value: 'title', label: 'Title', default: true },
  { value: 'content', label: 'Content', default: false },
  { value: 'excerpt', label: 'Excerpt', default: true },
  { value: 'status', label: 'Status', default: true },
  { value: 'author', label: 'Author', default: true },
  { value: 'date', label: 'Date', default: true },
  { value: 'modified', label: 'Modified Date', default: false },
  { value: 'categories', label: 'Categories', default: true },
  { value: 'tags', label: 'Tags', default: true },
  { value: 'featured_image', label: 'Featured Image', default: false },
  { value: 'post_type', label: 'Post Type', default: false },
  { value: 'comment_count', label: 'Comment Count', default: false },
];

const ExportComponent: React.FC<ExportComponentProps> = ({
  filters,
  totalPosts,
  onExportComplete,
}) => {
  const [isOpen, setIsOpen] = useState(false);
  const [format, setFormat] = useState<'csv' | 'excel'>('csv');
  const [selectedColumns, setSelectedColumns] = useState<string[]>(
    EXPORT_COLUMNS.filter(col => col.default).map(col => col.value)
  );
  const [filename, setFilename] = useState('');
  const [isExporting, setIsExporting] = useState(false);
  const [exportProgress, setExportProgress] = useState(0);

  const handleColumnToggle = (columnValue: string) => {
    setSelectedColumns(prev => 
      prev.includes(columnValue)
        ? prev.filter(col => col !== columnValue)
        : [...prev, columnValue]
    );
  };

  const handleSelectAllColumns = () => {
    setSelectedColumns(EXPORT_COLUMNS.map(col => col.value));
  };

  const handleSelectDefaultColumns = () => {
    setSelectedColumns(EXPORT_COLUMNS.filter(col => col.default).map(col => col.value));
  };

  const generateDefaultFilename = () => {
    const date = new Date().toISOString().split('T')[0];
    const postType = filters.post_type || 'posts';
    return `${postType}_export_${date}`;
  };

  const handleExport = async () => {
    if (selectedColumns.length === 0) {
      alert('Please select at least one column to export.');
      return;
    }

    setIsExporting(true);
    setExportProgress(0);

    try {
      const exportOptions: ExportOptions = {
        format,
        filters: {
          ...filters,
          per_page: -1, // Export all matching posts
        },
        columns: selectedColumns,
        filename: filename || generateDefaultFilename(),
      };

      // Simulate progress for better UX
      const progressInterval = setInterval(() => {
        setExportProgress(prev => Math.min(prev + 10, 90));
      }, 200);

      const result = await exportData(exportOptions.format, exportOptions.filters);

      clearInterval(progressInterval);
      setExportProgress(100);

      // Create and trigger download
      if (result.download_url) {
        const link = document.createElement('a');
        link.href = result.download_url;
        link.download = `${exportOptions.filename}.${format}`;
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
      }

      if (onExportComplete) {
        onExportComplete(result);
      }

      // Close modal after successful export
      setTimeout(() => {
        setIsOpen(false);
        setExportProgress(0);
      }, 1000);

    } catch (error) {
      console.error('Export failed:', error);
      alert('Export failed. Please try again.');
    } finally {
      setIsExporting(false);
    }
  };

  return (
    <div className="uadt-export-component">
      <button
        onClick={() => setIsOpen(true)}
        className="uadt-export-button"
        type="button"
        disabled={totalPosts === 0}
      >
        📥 Export ({totalPosts} posts)
      </button>

      {isOpen && (
        <div className="uadt-export-modal">
          <div className="uadt-export-backdrop" onClick={() => setIsOpen(false)} />
          <div className="uadt-export-dialog">
            <div className="uadt-export-header">
              <h3>Export Posts</h3>
              <button
                onClick={() => setIsOpen(false)}
                className="uadt-close-button"
                type="button"
              >
                ×
              </button>
            </div>

            <div className="uadt-export-body">
              {/* Format Selection */}
              <div className="uadt-export-section">
                <h4>Export Format</h4>
                <div className="uadt-format-options">
                  {EXPORT_FORMATS.map(fmt => (
                    <label key={fmt.value} className="uadt-format-option">
                      <input
                        type="radio"
                        name="format"
                        value={fmt.value}
                        checked={format === fmt.value}
                        onChange={(e) => setFormat(e.target.value as 'csv' | 'excel')}
                      />
                      <span className="uadt-format-label">
                        <strong>{fmt.label}</strong>
                        <small>{fmt.description}</small>
                      </span>
                    </label>
                  ))}
                </div>
              </div>

              {/* Column Selection */}
              <div className="uadt-export-section">
                <div className="uadt-columns-header">
                  <h4>Columns to Export</h4>
                  <div className="uadt-column-actions">
                    <button
                      onClick={handleSelectAllColumns}
                      className="uadt-column-action"
                      type="button"
                    >
                      Select All
                    </button>
                    <button
                      onClick={handleSelectDefaultColumns}
                      className="uadt-column-action"
                      type="button"
                    >
                      Default
                    </button>
                  </div>
                </div>
                <div className="uadt-columns-grid">
                  {EXPORT_COLUMNS.map(column => (
                    <label key={column.value} className="uadt-column-option">
                      <input
                        type="checkbox"
                        checked={selectedColumns.includes(column.value)}
                        onChange={() => handleColumnToggle(column.value)}
                      />
                      <span>{column.label}</span>
                    </label>
                  ))}
                </div>
              </div>

              {/* Filename */}
              <div className="uadt-export-section">
                <h4>Filename (optional)</h4>
                <input
                  type="text"
                  value={filename}
                  onChange={(e) => setFilename(e.target.value)}
                  placeholder={generateDefaultFilename()}
                  className="uadt-filename-input"
                />
                <small className="uadt-filename-note">
                  Leave empty to use default: {generateDefaultFilename()}.{format}
                </small>
              </div>

              {/* Export Summary */}
              <div className="uadt-export-summary">
                <p>
                  <strong>Export Summary:</strong><br />
                  Format: {format.toUpperCase()}<br />
                  Posts: {totalPosts}<br />
                  Columns: {selectedColumns.length}
                </p>
              </div>

              {/* Progress Bar */}
              {isExporting && (
                <div className="uadt-export-progress">
                  <div className="uadt-progress-bar">
                    <div 
                      className="uadt-progress-fill"
                      style={{ width: `${exportProgress}%` }}
                    />
                  </div>
                  <span className="uadt-progress-text">
                    {exportProgress < 100 ? `Exporting... ${exportProgress}%` : 'Complete!'}
                  </span>
                </div>
              )}
            </div>

            <div className="uadt-export-footer">
              <button
                onClick={() => setIsOpen(false)}
                className="uadt-button uadt-button-secondary"
                disabled={isExporting}
                type="button"
              >
                Cancel
              </button>
              <button
                onClick={handleExport}
                className="uadt-button uadt-button-primary"
                disabled={isExporting || selectedColumns.length === 0}
                type="button"
              >
                {isExporting ? 'Exporting...' : `Export ${format.toUpperCase()}`}
              </button>
            </div>
          </div>
        </div>
      )}
    </div>
  );
};

export default ExportComponent;
