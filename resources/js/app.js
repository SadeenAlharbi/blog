import { createElement } from 'react';
import { createRoot } from 'react-dom/client';
import PostsExplorer from './components/PostsExplorer';

const postsExplorerRoot = document.getElementById('posts-explorer-root');

if (postsExplorerRoot) {
    createRoot(postsExplorerRoot).render(
        createElement(PostsExplorer, {
            apiUrl: postsExplorerRoot.dataset.apiUrl,
            tagsApiUrl: postsExplorerRoot.dataset.tagsApiUrl,
            initialSearch: postsExplorerRoot.dataset.initialSearch,
            initialTag: postsExplorerRoot.dataset.initialTag,
        })
    );
}
