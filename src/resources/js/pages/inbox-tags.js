document.addEventListener('DOMContentLoaded', () => {
    const inboxPage = document.getElementById('lcInboxPage');
    if (!inboxPage) {
        return;
    }

    const selectedTagsContainer = document.getElementById('lcConversationTagsSelected');
    const workspaceTagsList = document.getElementById('lcWorkspaceTagsList');
    const tagsSearchInput = document.getElementById('lcTagsSearchInput');
    const saveConversationTagsBtn = document.getElementById('lcSaveConversationTagsBtn');
    const tagsError = document.getElementById('lcTagsError');
    const tagsSaved = document.getElementById('lcTagsSaved');
    const conversationTagsSaveUrl = inboxPage.dataset.conversationTagsSaveUrl || '';
    const csrfTokenMeta = document.querySelector('meta[name="csrf-token"]');
    const csrfToken = csrfTokenMeta ? csrfTokenMeta.getAttribute('content') : '';

    if (!selectedTagsContainer || !workspaceTagsList || !saveConversationTagsBtn) {
        return;
    }

    const selectedTagIds = new Set(
        Array.from(selectedTagsContainer.querySelectorAll('[data-tag-id]'))
            .map((el) => Number(el.getAttribute('data-tag-id')))
            .filter((id) => !Number.isNaN(id))
    );

    const clearTagsState = () => {
        if (tagsError) {
            tagsError.style.display = 'none';
            tagsError.textContent = '';
        }
        if (tagsSaved) {
            tagsSaved.style.display = 'none';
        }
    };

    const showTagsError = (message) => {
        if (!tagsError) {
            return;
        }
        tagsError.textContent = message;
        tagsError.style.display = 'block';
    };

    const flashTagsSaved = () => {
        if (!tagsSaved) {
            return;
        }
        tagsSaved.style.display = 'block';
        setTimeout(() => {
            tagsSaved.style.display = 'none';
        }, 1800);
    };

    const renderSelectedConversationTags = () => {
        const selectedButtons = Array.from(
            workspaceTagsList.querySelectorAll('.lc-workspace-tag-option.is-selected')
        );

        if (!selectedButtons.length) {
            selectedTagsContainer.innerHTML = '<span id="lcConversationTagsEmpty" style="font-size:11px; color:#94a3b8;">No tags assigned</span>';
            return;
        }

        selectedTagsContainer.innerHTML = selectedButtons
            .map((button) => {
                const tagId = button.getAttribute('data-tag-id') || '';
                const color = button.getAttribute('data-tag-color') || '#6366f1';
                const label = button.textContent.trim();

                return `
                    <span
                        data-tag-id="${tagId}"
                        style="display:inline-flex; align-items:center; min-height:24px; border-radius:999px; padding:0 8px; background:${color}20; color:#334155; border:1px solid ${color}55; font-size:11px; font-weight:700;"
                    >
                        ${label}
                    </span>
                `;
            })
            .join('');
    };

    const refreshWorkspaceTagButtons = () => {
        workspaceTagsList.querySelectorAll('.lc-workspace-tag-option').forEach((button) => {
            const tagId = Number(button.getAttribute('data-tag-id'));
            const color = button.getAttribute('data-tag-color') || '#6366f1';
            const isSelected = selectedTagIds.has(tagId);

            button.classList.toggle('is-selected', isSelected);
            button.style.background = isSelected ? `${color}20` : `${color}10`;
            button.style.border = `1px solid ${isSelected ? color + '66' : color + '33'}`;
            button.style.color = '#334155';
        });

        renderSelectedConversationTags();
    };

    const filterWorkspaceTags = () => {
        if (!tagsSearchInput) {
            return;
        }

        const query = tagsSearchInput.value.trim().toLowerCase();
        workspaceTagsList.querySelectorAll('.lc-workspace-tag-option').forEach((button) => {
            const name = (button.getAttribute('data-tag-name') || '').toLowerCase();
            button.style.display = !query || name.includes(query) ? 'inline-flex' : 'none';
        });
    };

    workspaceTagsList.addEventListener('click', (event) => {
        const button = event.target.closest('.lc-workspace-tag-option');
        if (!button) {
            return;
        }

        event.preventDefault();
        clearTagsState();

        const tagId = Number(button.getAttribute('data-tag-id'));
        if (Number.isNaN(tagId)) {
            return;
        }

        if (selectedTagIds.has(tagId)) {
            selectedTagIds.delete(tagId);
        } else {
            selectedTagIds.add(tagId);
        }

        refreshWorkspaceTagButtons();
    });

    if (tagsSearchInput) {
        tagsSearchInput.addEventListener('input', filterWorkspaceTags);
    }

    saveConversationTagsBtn.addEventListener('click', async (event) => {
        event.preventDefault();
        clearTagsState();

        if (!conversationTagsSaveUrl) {
            showTagsError('No conversation selected.');
            return;
        }

        const originalText = saveConversationTagsBtn.textContent;
        saveConversationTagsBtn.disabled = true;
        saveConversationTagsBtn.textContent = 'Saving...';

        try {
            const formData = new FormData();
            formData.append('_token', csrfToken);

            Array.from(selectedTagIds).forEach((id) => {
                formData.append('tag_ids[]', String(id));
            });

            const response = await fetch(conversationTagsSaveUrl, {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    Accept: 'application/json',
                },
                body: formData,
                credentials: 'same-origin',
            });

            const data = await response.json().catch(() => ({}));

            if (!response.ok) {
                showTagsError(data?.errors?.tag_ids?.[0] || data?.message || 'Failed to save tags.');
                return;
            }

            if (Array.isArray(data.tags)) {
                selectedTagIds.clear();
                data.tags.forEach((tag) => {
                    const tagId = Number(tag.id);
                    if (!Number.isNaN(tagId)) {
                        selectedTagIds.add(tagId);
                    }
                });
            }

            refreshWorkspaceTagButtons();
            flashTagsSaved();
        } catch (error) {
            showTagsError('Failed to save tags.');
        } finally {
            saveConversationTagsBtn.disabled = false;
            saveConversationTagsBtn.textContent = originalText;
        }
    });

    refreshWorkspaceTagButtons();
    filterWorkspaceTags();
});
