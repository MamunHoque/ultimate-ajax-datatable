import React, { useState, useEffect } from 'react';
import { FilterState, FilterPreset } from '../types';

interface FilterPresetsProps {
  filters: FilterState;
  onLoadPreset: (preset: FilterPreset) => void;
  onSavePreset: (name: string, filters: Partial<FilterState>) => void;
  onDeletePreset: (presetId: number) => void;
}

// Default presets that are always available
const DEFAULT_PRESETS: Omit<FilterPreset, 'id' | 'user_id' | 'created_at' | 'updated_at'>[] = [
  {
    name: 'Published Posts',
    filters: { status: 'publish' },
    post_type: 'post',
    is_default: true,
  },
  {
    name: 'Draft Posts',
    filters: { status: 'draft' },
    post_type: 'post',
    is_default: true,
  },
  {
    name: 'Recent Posts',
    filters: { 
      status: 'publish',
      date_preset: 'last_30_days',
      orderby: 'date',
      order: 'DESC'
    },
    post_type: 'post',
    is_default: true,
  },
  {
    name: 'My Posts',
    filters: { 
      author: 'current_user',
      status: ['publish', 'draft', 'private']
    },
    post_type: 'post',
    is_default: true,
  },
];

const FilterPresets: React.FC<FilterPresetsProps> = ({
  filters,
  onLoadPreset,
  onSavePreset,
  onDeletePreset,
}) => {
  const [isOpen, setIsOpen] = useState(false);
  const [userPresets, setUserPresets] = useState<FilterPreset[]>([]);
  const [newPresetName, setNewPresetName] = useState('');
  const [showSaveForm, setShowSaveForm] = useState(false);
  const [isLoading, setIsLoading] = useState(false);

  // Load user presets from API
  useEffect(() => {
    loadUserPresets();
  }, []);

  const loadUserPresets = async () => {
    setIsLoading(true);
    try {
      // This would call the API to get user presets
      // const response = await fetch('/wp-json/uadt/v1/presets');
      // const presets = await response.json();
      // setUserPresets(presets);
      
      // For now, load from localStorage
      const savedPresets = localStorage.getItem('uadt_filter_presets');
      if (savedPresets) {
        setUserPresets(JSON.parse(savedPresets));
      }
    } catch (error) {
      console.error('Failed to load presets:', error);
    } finally {
      setIsLoading(false);
    }
  };

  const saveUserPresets = (presets: FilterPreset[]) => {
    // Save to localStorage for now
    localStorage.setItem('uadt_filter_presets', JSON.stringify(presets));
    setUserPresets(presets);
  };

  const handleSavePreset = () => {
    if (!newPresetName.trim()) return;

    const newPreset: FilterPreset = {
      id: Date.now(), // Simple ID generation
      name: newPresetName.trim(),
      filters: {
        search: filters.search,
        author: filters.author,
        status: filters.status,
        category: filters.category,
        tag: filters.tag,
        date_from: filters.date_from,
        date_to: filters.date_to,
        date_preset: filters.date_preset,
        custom_fields: filters.custom_fields,
        orderby: filters.orderby,
        order: filters.order,
      },
      post_type: filters.post_type,
      is_default: false,
      created_at: new Date().toISOString(),
    };

    const updatedPresets = [...userPresets, newPreset];
    saveUserPresets(updatedPresets);
    
    setNewPresetName('');
    setShowSaveForm(false);
    
    // Call parent callback
    onSavePreset(newPreset.name, newPreset.filters);
  };

  const handleLoadPreset = (preset: FilterPreset | typeof DEFAULT_PRESETS[0]) => {
    onLoadPreset(preset as FilterPreset);
    setIsOpen(false);
  };

  const handleDeletePreset = (presetId: number) => {
    if (confirm('Are you sure you want to delete this preset?')) {
      const updatedPresets = userPresets.filter(p => p.id !== presetId);
      saveUserPresets(updatedPresets);
      onDeletePreset(presetId);
    }
  };

  const hasActiveFilters = () => {
    return filters.search || 
           filters.author || 
           filters.status || 
           filters.category || 
           filters.tag || 
           filters.date_from || 
           filters.date_to ||
           (filters.custom_fields && Object.keys(filters.custom_fields).length > 0);
  };

  const getPresetDescription = (preset: FilterPreset | typeof DEFAULT_PRESETS[0]) => {
    const filterCount = Object.keys(preset.filters).filter(key => {
      const value = preset.filters[key as keyof typeof preset.filters];
      return value && value !== '' && (!Array.isArray(value) || value.length > 0);
    }).length;
    
    return `${filterCount} filter${filterCount !== 1 ? 's' : ''}`;
  };

  return (
    <div className="uadt-filter-presets">
      <button
        onClick={() => setIsOpen(true)}
        className="uadt-presets-button"
        type="button"
      >
        📋 Presets
      </button>

      {isOpen && (
        <div className="uadt-presets-modal">
          <div className="uadt-presets-backdrop" onClick={() => setIsOpen(false)} />
          <div className="uadt-presets-dialog">
            <div className="uadt-presets-header">
              <h3>Filter Presets</h3>
              <button
                onClick={() => setIsOpen(false)}
                className="uadt-close-button"
                type="button"
              >
                ×
              </button>
            </div>

            <div className="uadt-presets-body">
              {/* Save Current Filters */}
              {hasActiveFilters() && (
                <div className="uadt-save-preset-section">
                  <h4>Save Current Filters</h4>
                  {!showSaveForm ? (
                    <button
                      onClick={() => setShowSaveForm(true)}
                      className="uadt-save-preset-button"
                      type="button"
                    >
                      💾 Save as Preset
                    </button>
                  ) : (
                    <div className="uadt-save-preset-form">
                      <input
                        type="text"
                        value={newPresetName}
                        onChange={(e) => setNewPresetName(e.target.value)}
                        placeholder="Enter preset name..."
                        className="uadt-preset-name-input"
                        onKeyPress={(e) => e.key === 'Enter' && handleSavePreset()}
                      />
                      <div className="uadt-save-preset-actions">
                        <button
                          onClick={handleSavePreset}
                          disabled={!newPresetName.trim()}
                          className="uadt-button uadt-button-primary"
                          type="button"
                        >
                          Save
                        </button>
                        <button
                          onClick={() => {
                            setShowSaveForm(false);
                            setNewPresetName('');
                          }}
                          className="uadt-button uadt-button-secondary"
                          type="button"
                        >
                          Cancel
                        </button>
                      </div>
                    </div>
                  )}
                </div>
              )}

              {/* Default Presets */}
              <div className="uadt-presets-section">
                <h4>Default Presets</h4>
                <div className="uadt-presets-list">
                  {DEFAULT_PRESETS.map((preset, index) => (
                    <div key={`default-${index}`} className="uadt-preset-item">
                      <div className="uadt-preset-info">
                        <span className="uadt-preset-name">{preset.name}</span>
                        <span className="uadt-preset-description">
                          {getPresetDescription(preset)}
                        </span>
                      </div>
                      <button
                        onClick={() => handleLoadPreset(preset)}
                        className="uadt-preset-load"
                        type="button"
                      >
                        Load
                      </button>
                    </div>
                  ))}
                </div>
              </div>

              {/* User Presets */}
              {userPresets.length > 0 && (
                <div className="uadt-presets-section">
                  <h4>My Presets</h4>
                  <div className="uadt-presets-list">
                    {userPresets.map((preset) => (
                      <div key={preset.id} className="uadt-preset-item">
                        <div className="uadt-preset-info">
                          <span className="uadt-preset-name">{preset.name}</span>
                          <span className="uadt-preset-description">
                            {getPresetDescription(preset)}
                          </span>
                          <span className="uadt-preset-date">
                            Created: {new Date(preset.created_at!).toLocaleDateString()}
                          </span>
                        </div>
                        <div className="uadt-preset-actions">
                          <button
                            onClick={() => handleLoadPreset(preset)}
                            className="uadt-preset-load"
                            type="button"
                          >
                            Load
                          </button>
                          <button
                            onClick={() => handleDeletePreset(preset.id!)}
                            className="uadt-preset-delete"
                            type="button"
                          >
                            Delete
                          </button>
                        </div>
                      </div>
                    ))}
                  </div>
                </div>
              )}

              {/* Loading State */}
              {isLoading && (
                <div className="uadt-presets-loading">
                  Loading presets...
                </div>
              )}

              {/* Empty State */}
              {!isLoading && userPresets.length === 0 && !hasActiveFilters() && (
                <div className="uadt-presets-empty">
                  <p>No saved presets yet.</p>
                  <p>Apply some filters and save them as a preset for quick access later.</p>
                </div>
              )}
            </div>
          </div>
        </div>
      )}
    </div>
  );
};

export default FilterPresets;
