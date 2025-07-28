/**
 * Ultimate Ajax DataTable - Admin App
 *
 * @package UltimateAjaxDataTable
 * @since 1.0.0
 */

(function() {
    'use strict';

    // Wait for DOM to be ready
    document.addEventListener('DOMContentLoaded', function() {
        const appContainer = document.getElementById('uadt-admin-app');

        if (!appContainer || typeof React === 'undefined' || typeof ReactDOM === 'undefined') {
            console.error('UADT: React or ReactDOM not loaded, or container not found');
            return;
        }

        // Show loading state initially
        appContainer.innerHTML = `
            <div class="uadt-loading">
                Loading DataTable Manager...
            </div>
        `;

        // Initialize React app
        setTimeout(function() {
            try {
                initializeReactApp();
            } catch (error) {
                console.error('UADT: Error initializing React app:', error);
                showFallbackContent();
            }
        }, 100);
    });

    function initializeReactApp() {
        const { useState, useEffect } = React;
        const appContainer = document.getElementById('uadt-admin-app');

        // Simple React component for now
        function DataTableApp() {
            const [posts, setPosts] = useState([]);
            const [loading, setLoading] = useState(true);
            const [error, setError] = useState(null);
            const [filters, setFilters] = useState({
                search: '',
                page: 1,
                per_page: 25
            });

            useEffect(() => {
                loadPosts();
            }, [filters]);

            const loadPosts = async () => {
                try {
                    setLoading(true);
                    const response = await window.uadtAPI.getPosts(filters);
                    setPosts(response.posts || []);
                    setError(null);
                } catch (err) {
                    setError('Failed to load posts: ' + err.message);
                    console.error('Error loading posts:', err);
                } finally {
                    setLoading(false);
                }
            };

            const handleSearchChange = (e) => {
                setFilters(prev => ({
                    ...prev,
                    search: e.target.value,
                    page: 1
                }));
            };

            return React.createElement('div', { className: 'uadt-app' },
                React.createElement('div', { className: 'mb-6' },
                    React.createElement('h1', { className: 'text-2xl font-semibold text-wp-text mb-2' },
                        'DataTable Manager'
                    ),
                    React.createElement('p', { className: 'text-wp-text-light' },
                        'Manage your posts with advanced filtering and bulk operations.'
                    )
                ),

                // Search Filter
                React.createElement('div', { className: 'uadt-filter-panel' },
                    React.createElement('div', { className: 'mb-4' },
                        React.createElement('label', { className: 'uadt-filter-label' }, 'Search'),
                        React.createElement('input', {
                            type: 'text',
                            className: 'uadt-input',
                            placeholder: 'Search posts...',
                            value: filters.search,
                            onChange: handleSearchChange
                        })
                    )
                ),

                // Content Area
                React.createElement('div', { className: 'bg-white border border-wp-gray-dark rounded-lg' },
                    loading ?
                        React.createElement('div', { className: 'p-8 text-center' }, 'Loading posts...') :
                    error ?
                        React.createElement('div', { className: 'p-8 text-center text-red-600' }, error) :
                    posts.length === 0 ?
                        React.createElement('div', { className: 'p-8 text-center text-wp-text-light' }, 'No posts found') :
                        React.createElement('div', { className: 'overflow-x-auto' },
                            React.createElement('table', { className: 'uadt-table' },
                                React.createElement('thead', null,
                                    React.createElement('tr', null,
                                        React.createElement('th', null, 'Title'),
                                        React.createElement('th', null, 'Author'),
                                        React.createElement('th', null, 'Status'),
                                        React.createElement('th', null, 'Date'),
                                        React.createElement('th', null, 'Actions')
                                    )
                                ),
                                React.createElement('tbody', null,
                                    posts.map(post =>
                                        React.createElement('tr', { key: post.id },
                                            React.createElement('td', null, post.title || '(No title)'),
                                            React.createElement('td', null, post.author),
                                            React.createElement('td', null, post.status_label),
                                            React.createElement('td', null, post.date_formatted),
                                            React.createElement('td', null,
                                                post.edit_link ?
                                                    React.createElement('a', {
                                                        href: post.edit_link,
                                                        className: 'text-wp-blue hover:text-wp-blue-dark',
                                                        target: '_blank'
                                                    }, 'Edit') : ''
                                            )
                                        )
                                    )
                                )
                            )
                        )
                )
            );
        }

        // Render the app
        const root = ReactDOM.createRoot(appContainer);
        root.render(React.createElement(DataTableApp));
    }

    function showFallbackContent() {
        const appContainer = document.getElementById('uadt-admin-app');
        appContainer.innerHTML = `
            <div style="text-align: center; padding: 40px;">
                <h2>Ultimate Ajax DataTable</h2>
                <p>React application failed to load. Please check console for errors.</p>
                <div style="margin-top: 30px; padding: 20px; background: #f0f0f1; border-radius: 4px;">
                    <h3>Status:</h3>
                    <ul style="text-align: left; display: inline-block;">
                        <li>✅ Core plugin foundation completed</li>
                        <li>✅ REST API with filtering completed</li>
                        <li>⚠️ React frontend loading issue</li>
                        <li>⏳ Testing and documentation</li>
                    </ul>
                </div>
            </div>
        `;
    }

    // API helper
    window.uadtAPI = {
        request: function(endpoint, options = {}) {
            const url = window.uadtAdmin.apiUrl + endpoint;
            const defaultOptions = {
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': window.uadtAdmin.nonce
                }
            };

            return fetch(url, { ...defaultOptions, ...options })
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok: ' + response.status);
                    }
                    return response.json();
                });
        },

        getPosts: function(filters = {}) {
            const params = new URLSearchParams();
            Object.keys(filters).forEach(key => {
                if (filters[key] !== '' && filters[key] !== null && filters[key] !== undefined) {
                    params.append(key, filters[key]);
                }
            });
            return this.request('posts?' + params.toString());
        },

        bulkAction: function(action, postIds) {
            return this.request('posts/bulk', {
                method: 'POST',
                body: JSON.stringify({
                    action: action,
                    post_ids: postIds
                })
            });
        }
    };

})();
