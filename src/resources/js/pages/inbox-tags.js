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

    const normalizeTagColor = (value) => {
        const color = String(value || '');
        return /^#[0-9a-fA-F]{6}$/.test(color) ? color.toLowerCase() : '#6366f1';
    };

    const renderSelectedConversationTags = () => {
        const selectedButtons = Array.from(
            workspaceTagsList.querySelectorAll('.lc-workspace-tag-option.is-selected')
        );

        selectedTagsContainer.replaceChildren();

        if (!selectedButtons.length) {
            const emptyState = document.createElement('span');
            emptyState.id = 'lcConversationTagsEmpty';
            emptyState.style.fontSize = '11px';
            emptyState.style.color = '#94a3b8';
            emptyState.textContent = 'No tags assigned';
            selectedTagsContainer.append(emptyState);
            return;
        }

        selectedButtons.forEach((button) => {
            const tagId = button.getAttribute('data-tag-id') || '';
            const color = normalizeTagColor(button.getAttribute('data-tag-color'));
            const label = button.textContent.trim();

            const chip = document.createElement('span');
            chip.dataset.tagId = tagId;
            chip.style.display = 'inline-flex';
            chip.style.alignItems = 'center';
            chip.style.minHeight = '24px';
            chip.style.borderRadius = '999px';
            chip.style.padding = '0 8px';
            chip.style.backgroundColor = `${color}20`;
            chip.style.color = '#334155';
            chip.style.border = `1px solid ${color}55`;
            chip.style.fontSize = '11px';
            chip.style.fontWeight = '700';
            chip.textContent = label;
            selectedTagsContainer.append(chip);
        });
    };

    const refreshWorkspaceTagButtons = () => {
        workspaceTagsList.querySelectorAll('.lc-workspace-tag-option').forEach((button) => {
            const tagId = Number(button.getAttribute('data-tag-id'));
            const color = normalizeTagColor(button.getAttribute('data-tag-color'));
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
