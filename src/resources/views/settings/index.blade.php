<x-app-layout>
    <style>
        .ws-page {
            background: #f8fafc;
            min-height: calc(100vh - 65px);
            padding: 24px;
        }

        .ws-shell {
            display: grid;
            grid-template-columns: 260px minmax(0, 1fr);
            gap: 20px;
            align-items: start;
        }

        .ws-sidebar,
        .ws-content {
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 16px;
            box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04);
        }

        .ws-sidebar {
            padding: 14px;
            position: sticky;
            top: 24px;
        }

        .ws-content {
            padding: 18px;
        }

        .ws-title {
            margin: 0;
            font-size: 22px;
            font-weight: 800;
            color: #0f172a;
        }

        .ws-subtitle {
            margin-top: 6px;
            font-size: 13px;
            color: #64748b;
        }

        .ws-nav {
            margin-top: 16px;
            display: grid;
            gap: 8px;
        }

        .ws-nav-link {
            display: flex;
            align-items: center;
            justify-content: space-between;
            min-height: 40px;
            padding: 0 12px;
            border-radius: 10px;
            text-decoration: none;
            font-size: 14px;
            font-weight: 700;
            color: #475569;
            background: transparent;
            border: 1px solid transparent;
        }

        .ws-nav-link:hover {
            background: #f8fafc;
            border-color: #e2e8f0;
        }

        .ws-nav-link.is-active {
            background: #eef2ff;
            color: #4338ca;
            border-color: #c7d2fe;
        }

        .ws-nav-link-text {
            min-width: 0;
        }

        .ws-nav-pill {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 20px;
            padding: 0 8px;
            border-radius: 999px;
            background: #f1f5f9;
            color: #64748b;
            font-size: 10px;
            font-weight: 800;
            line-height: 1;
            white-space: nowrap;
        }

        .ws-nav-link.is-active .ws-nav-pill {
            background: #c7d2fe;
            color: #3730a3;
        }

        .ws-section-title {
            margin: 0;
            font-size: 18px;
            font-weight: 800;
            color: #0f172a;
        }

        .ws-section-subtitle {
            margin-top: 6px;
            font-size: 13px;
            color: #64748b;
        }

        .ws-placeholder {
            margin-top: 18px;
            border: 1px dashed #cbd5e1;
            border-radius: 14px;
            background: #f8fafc;
            padding: 18px;
            color: #64748b;
            font-size: 14px;
            line-height: 1.6;
        }

        .ws-badge {
            display: inline-flex;
            align-items: center;
            min-height: 24px;
            border-radius: 999px;
            padding: 0 10px;
            background: #ede9fe;
            color: #6d28d9;
            font-size: 11px;
            font-weight: 800;
            margin-bottom: 10px;
        }

        @media (max-width: 980px) {
            .ws-shell {
                grid-template-columns: 1fr;
            }

            .ws-sidebar {
                position: static;
            }
        }
    </style>

    <div class="ws-page">
        <div class="ws-shell">
            <aside class="ws-sidebar">
                <h1 class="ws-title">Settings</h1>
                <div class="ws-subtitle">
                    {{ $workspace?->name ? $workspace->name . ' workspace settings' : 'Workspace settings' }}
                </div>

                <nav class="ws-nav">
                    @foreach ($sections as $key => $label)
                        <a
                            href="{{ route('settings.index', ['section' => $key]) }}"
                            class="ws-nav-link {{ $section === $key ? 'is-active' : '' }}"
                        >
                            <span class="ws-nav-link-text">{{ $label }}</span>
                            @if (in_array($key, ['ai', 'billing', 'security', 'advanced'], true))
                                <span class="ws-nav-pill">Soon</span>
                            @endif
                        </a>
                    @endforeach
                </nav>
            </aside>

            <section class="ws-content">
                <div class="ws-badge">
                    {{ $sections[$section] }}
                </div>

                <h2 class="ws-section-title">
                    {{ $sections[$section] }}
                </h2>

                <div class="ws-section-subtitle">
                    @if ($section === 'general')
                        Update the basic details for your current workspace.
                    @elseif ($section === 'tags')
                        Manage workspace tags here. Inbox will only select tags from this section.
                    @elseif ($section === 'departments')
                        Manage workspace departments here. Inbox will assign each conversation to one department.
                    @elseif ($section === 'team')
                        Create and manage teammate accounts for this workspace. Every workspace member can manage their own profile details.
                    @elseif ($section === 'channels')
                        Manage connected channels for this workspace. Reuse the existing Instagram flow here and keep Facebook and WhatsApp ready for the next phases.
                    @elseif ($section === 'catalogs')
                        Manage product catalogs that agents can send inside Instagram direct messages.
                    @elseif ($section === 'commerce')
                        Discover real Meta Commerce assets, catalog access, and product-tag readiness for App Review.
                    @elseif ($section === 'automation')
                        Configure automatic Instagram direct messages for story replies and new comments.
                    @elseif (in_array($section, ['ai', 'billing', 'security', 'advanced'], true))
                        This section will be added soon.
                    @else
                        This section is reserved and will be implemented in the next phases.
                    @endif
                </div>

                @if ($section === 'general')
                    <style>
                        .ws-general-card {
                            margin-top: 18px;
                            max-width: 720px;
                            border: 1px solid #e2e8f0;
                            border-radius: 14px;
                            background: #fff;
                            padding: 16px;
                        }

                        .ws-general-label {
                            display: block;
                            margin-bottom: 8px;
                            font-size: 13px;
                            font-weight: 700;
                            color: #334155;
                        }

                        .ws-general-input {
                            width: 100%;
                            height: 42px;
                            border: 1px solid #cbd5e1;
                            border-radius: 10px;
                            padding: 0 12px;
                            font-size: 14px;
                            color: #0f172a;
                            background: #fff;
                            outline: none;
                        }

                        .ws-general-input:focus {
                            border-color: #818cf8;
                            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.12);
                        }

                        .ws-general-help {
                            margin-top: 8px;
                            font-size: 12px;
                            line-height: 1.5;
                            color: #64748b;
                        }

                        .ws-general-error {
                            margin-top: 8px;
                            font-size: 12px;
                            font-weight: 700;
                            color: #dc2626;
                        }

                        .ws-general-success {
                            margin-bottom: 12px;
                            border: 1px solid #bbf7d0;
                            border-radius: 10px;
                            background: #f0fdf4;
                            padding: 10px 12px;
                            font-size: 13px;
                            font-weight: 700;
                            color: #166534;
                        }

                        .ws-general-actions {
                            margin-top: 14px;
                            display: flex;
                            justify-content: flex-end;
                        }
                    </style>

                    <div class="ws-general-card">
                        @if (session('status'))
                            <div class="ws-general-success">
                                {{ session('status') }}
                            </div>
                        @endif

                        <form method="POST" action="{{ route('settings.general.update') }}">
                            @csrf
                            @method('PATCH')

                            <label class="ws-general-label" for="wsWorkspaceName">
                                Workspace name
                            </label>

                            <input
                                id="wsWorkspaceName"
                                name="name"
                                class="ws-general-input"
                                type="text"
                                maxlength="255"
                                value="{{ old('name', $workspace?->name) }}"
                                placeholder="Enter workspace name"
                            >

                            <div class="ws-general-help">
                                This name is shown across your dashboard and settings.
                            </div>

                            @error('name')
                                <div class="ws-general-error">
                                    {{ $message }}
                                </div>
                            @enderror

                            <div class="ws-general-actions">
                                <button class="ws-btn" type="submit">
                                    Save
                                </button>
                            </div>
                        </form>
                    </div>
                @elseif ($section === 'tags')
                    <style>
                        .ws-tags-shell {
                            margin-top: 18px;
                            display: grid;
                            gap: 14px;
                        }

                        .ws-tags-create,
                        .ws-tags-list-card {
                            border: 1px solid #e2e8f0;
                            border-radius: 14px;
                            background: #fff;
                            padding: 14px;
                        }

                        .ws-tags-row {
                            display: grid;
                            grid-template-columns: minmax(0, 1fr) 52px 90px;
                            gap: 10px;
                            align-items: center;
                        }

                        .ws-input {
                            width: 100%;
                            height: 38px;
                            border: 1px solid #cbd5e1;
                            border-radius: 10px;
                            padding: 0 12px;
                            font-size: 13px;
                            color: #334155;
                            background: #fff;
                            outline: none;
                        }

                        .ws-color {
                            width: 52px;
                            height: 38px;
                            border: 1px solid #cbd5e1;
                            border-radius: 10px;
                            background: #fff;
                            padding: 4px;
                        }

                        .ws-btn {
                            min-height: 38px;
                            border: 1px solid #e2e8f0;
                            border-radius: 10px;
                            background: #fff;
                            padding: 0 12px;
                            font-size: 13px;
                            font-weight: 700;
                            color: #334155;
                            cursor: pointer;
                        }

                        .ws-btn:disabled {
                            opacity: .55;
                            cursor: not-allowed;
                        }

                        .ws-tags-feedback {
                            margin-top: 10px;
                            font-size: 12px;
                            font-weight: 700;
                            display: none;
                        }

                        .ws-tags-feedback.is-error {
                            display: block;
                            color: #dc2626;
                        }

                        .ws-tags-feedback.is-success {
                            display: block;
                            color: #16a34a;
                        }

                        .ws-empty {
                            border: 1px dashed #cbd5e1;
                            border-radius: 12px;
                            padding: 18px;
                            background: #f8fafc;
                            color: #64748b;
                            font-size: 13px;
                        }

                        .ws-tags-inline-list {
                            display: flex;
                            flex-direction: column;
                            gap: 10px;
                            align-items: stretch;
                        }

                        .ws-tag-row {
                            display: flex;
                            align-items: center;
                            justify-content: space-between;
                            gap: 14px;
                            width: 100%;
                            max-width: 520px;
                            border: 1px solid #e2e8f0;
                            border-radius: 12px;
                            background: #fff;
                            padding: 8px 10px;
                        }

                        .ws-tag-chip-wrap {
                            flex: 1 1 auto;
                            min-width: 0;
                        }

                        .ws-tag-chip {
                            display: inline-flex;
                            align-items: center;
                            min-height: 30px;
                            border-radius: 999px;
                            padding: 0 12px;
                            font-size: 12px;
                            font-weight: 700;
                            color: #334155;
                            border: 1px solid transparent;
                            white-space: nowrap;
                            max-width: 100%;
                        }

                        .ws-tag-actions {
                            flex: 0 0 auto;
                            display: flex;
                            align-items: center;
                        }

                        .ws-tag-delete {
                            display: inline-flex;
                            align-items: center;
                            justify-content: center;
                            min-height: 30px;
                            border: 1px solid #e2e8f0;
                            border-radius: 999px;
                            background: #fff;
                            color: #334155;
                            font-size: 12px;
                            font-weight: 700;
                            cursor: pointer;
                            padding: 0 12px;
                            line-height: 1;
                            transition: background .15s ease, border-color .15s ease;
                        }

                        .ws-tag-delete:hover {
                            background: #f8fafc;
                            border-color: #cbd5e1;
                        }

                        @media (max-width: 920px) {
                            .ws-tags-row { grid-template-columns: 1fr; }
                        }
                    </style>

                    <div class="ws-tags-shell">
                        <div class="ws-tags-create">
                            <div class="ws-section-subtitle" style="margin-top:0; margin-bottom:10px;">
                                Create workspace tags here. Inbox will use these tags for assignment only.
                            </div>

                            <div class="ws-tags-row">
                                <input id="wsNewTagName" class="ws-input" type="text" placeholder="New tag name">
                                <input id="wsNewTagColor" class="ws-color" type="color" value="#6366f1">
                                <button id="wsCreateTagBtn" class="ws-btn" type="button">Add tag</button>
                            </div>

                            <div id="wsTagsCreateFeedback" class="ws-tags-feedback"></div>
                        </div>

                        <div class="ws-tags-list-card">
                            <div class="ws-section-subtitle" style="margin-top:0; margin-bottom:12px;">
                                Existing workspace tags
                            </div>

                            <div id="wsTagsList" class="ws-tags-inline-list">
                                @forelse ($workspaceTags as $tag)
                                    <div class="ws-tag-row" data-tag-id="{{ $tag->id }}">
                                        <div class="ws-tag-chip-wrap">
                                            <span
                                                class="ws-tag-chip"
                                                style="background: {{ $tag->color }}20; border-color: {{ $tag->color }}55;"
                                            >
                                                <span class="ws-tag-label">{{ $tag->name }}</span>
                                            </span>
                                        </div>

                                        <div class="ws-tag-actions">
                                            <button
                                                type="button"
                                                class="ws-tag-delete"
                                                data-delete-url="{{ route('settings.tags.delete', $tag) }}"
                                                title="Remove tag"
                                            >
                                                Remove
                                            </button>
                                        </div>
                                    </div>
                                @empty
                                    <div class="ws-empty" id="wsTagsEmpty">No tags created yet.</div>
                                @endforelse
                            </div>
                        </div>
                    </div>

                    <script>
                        document.addEventListener('DOMContentLoaded', function () {
                            const csrfToken = '{{ csrf_token() }}';

                            const createBtn = document.getElementById('wsCreateTagBtn');
                            const createName = document.getElementById('wsNewTagName');
                            const createColor = document.getElementById('wsNewTagColor');
                            const createFeedback = document.getElementById('wsTagsCreateFeedback');
                            const tagsList = document.getElementById('wsTagsList');

                            const setFeedback = (el, message, type) => {
                                if (!el) return;
                                el.textContent = message || '';
                                el.classList.remove('is-error', 'is-success');

                                if (!message) {
                                    el.style.display = 'none';
                                    return;
                                }

                                el.classList.add(type === 'success' ? 'is-success' : 'is-error');
                                el.style.display = 'block';
                            };

                            const normalizeTagColor = (value) => {
                                const color = String(value || '');
                                return /^#[0-9a-fA-F]{6}$/.test(color) ? color.toLowerCase() : '#6366f1';
                            };

                            const buildTagRow = (tag) => {
                                const wrapper = document.createElement('div');
                                wrapper.className = 'ws-tag-row';
                                wrapper.setAttribute('data-tag-id', String(tag.id));

                                const color = normalizeTagColor(tag.color);
                                const chipWrap = document.createElement('div');
                                chipWrap.className = 'ws-tag-chip-wrap';

                                const chip = document.createElement('span');
                                chip.className = 'ws-tag-chip';
                                chip.style.backgroundColor = `${color}20`;
                                chip.style.borderColor = `${color}55`;

                                const label = document.createElement('span');
                                label.className = 'ws-tag-label';
                                label.textContent = String(tag.name || '');
                                chip.append(label);
                                chipWrap.append(chip);

                                const actions = document.createElement('div');
                                actions.className = 'ws-tag-actions';

                                const deleteButton = document.createElement('button');
                                deleteButton.type = 'button';
                                deleteButton.className = 'ws-tag-delete';
                                deleteButton.dataset.deleteUrl = `/settings/tags/${encodeURIComponent(String(tag.id))}`;
                                deleteButton.title = 'Remove tag';
                                deleteButton.textContent = 'Remove';
                                actions.append(deleteButton);

                                wrapper.append(chipWrap, actions);
                                return wrapper;
                            };

                            if (createBtn && createName && createColor && tagsList) {
                                createBtn.addEventListener('click', async function () {
                                    setFeedback(createFeedback, '', 'error');

                                    const name = createName.value.trim();
                                    const color = createColor.value || '#6366f1';

                                    if (!name) {
                                        setFeedback(createFeedback, 'Tag name is required.', 'error');
                                        return;
                                    }

                                    const originalText = createBtn.textContent;
                                    createBtn.disabled = true;
                                    createBtn.textContent = 'Adding...';

                                    try {
                                        const formData = new FormData();
                                        formData.append('_token', csrfToken);
                                        formData.append('name', name);
                                        formData.append('color', color);

                                        const response = await fetch(@json(route('settings.tags.create')), {
                                            method: 'POST',
                                            headers: {
                                                'X-Requested-With': 'XMLHttpRequest',
                                                'Accept': 'application/json'
                                            },
                                            body: formData,
                                            credentials: 'same-origin'
                                        });

                                        const data = await response.json().catch(() => ({}));

                                        if (!response.ok) {
                                            setFeedback(
                                                createFeedback,
                                                data?.errors?.name?.[0] || data?.message || 'Failed to create tag.',
                                                'error'
                                            );
                                            return;
                                        }

                                        const emptyState = tagsList.querySelector('.ws-empty');
                                        if (emptyState) {
                                            emptyState.remove();
                                        }

                                        tagsList.appendChild(buildTagRow(data.tag));
                                        createName.value = '';
                                        setFeedback(createFeedback, 'Tag created.', 'success');
                                    } catch (e) {
                                        setFeedback(createFeedback, 'Failed to create tag.', 'error');
                                    } finally {
                                        createBtn.disabled = false;
                                        createBtn.textContent = originalText;
                                    }
                                });

                                tagsList.addEventListener('click', async function (event) {
                                    const button = event.target.closest('.ws-tag-delete');
                                    if (!button) return;

                                    event.preventDefault();
                                    event.stopPropagation();

                                    const row = button.closest('.ws-tag-row');
                                    if (!row) return;

                                    setFeedback(createFeedback, '', 'error');

                                    const deleteUrl = button.getAttribute('data-delete-url');
                                    if (!deleteUrl) {
                                        setFeedback(createFeedback, 'Delete URL not found.', 'error');
                                        return;
                                    }

                                    const originalText = button.textContent;
                                    button.disabled = true;
                                    button.textContent = 'Removing...';

                                    try {
                                        const response = await fetch(deleteUrl, {
                                            method: 'DELETE',
                                            headers: {
                                                'X-Requested-With': 'XMLHttpRequest',
                                                'Accept': 'application/json',
                                                'X-CSRF-TOKEN': csrfToken
                                            },
                                            credentials: 'same-origin'
                                        });

                                        const data = await response.json().catch(() => ({}));

                                        if (!response.ok) {
                                            setFeedback(
                                                createFeedback,
                                                data?.message || 'Failed to delete tag.',
                                                'error'
                                            );
                                            button.disabled = false;
                                            button.textContent = originalText;
                                            return;
                                        }

                                        row.remove();

                                        if (!tagsList.querySelector('.ws-tag-row')) {
                                            const empty = document.createElement('div');
                                            empty.className = 'ws-empty';
                                            empty.id = 'wsTagsEmpty';
                                            empty.textContent = 'No tags created yet.';
                                            tagsList.appendChild(empty);
                                        }

                                        setFeedback(createFeedback, 'Tag deleted.', 'success');
                                    } catch (e) {
                                        setFeedback(createFeedback, 'Failed to delete tag.', 'error');
                                        button.disabled = false;
                                        button.textContent = originalText;
                                    }
                                });
                            }
                        });
                    </script>
                @elseif ($section === 'departments')
                    <style>
                        .ws-departments-shell {
                            margin-top: 18px;
                            display: grid;
                            gap: 14px;
                        }

                        .ws-departments-create,
                        .ws-departments-list-card {
                            border: 1px solid #e2e8f0;
                            border-radius: 14px;
                            background: #fff;
                            padding: 14px;
                        }

                        .ws-departments-row {
                            display: grid;
                            grid-template-columns: minmax(0, 1fr) 52px 126px;
                            gap: 10px;
                            align-items: center;
                        }

                        .ws-departments-feedback {
                            margin-top: 10px;
                            font-size: 12px;
                            font-weight: 700;
                            display: none;
                        }

                        .ws-departments-feedback.is-error {
                            display: block;
                            color: #dc2626;
                        }

                        .ws-departments-feedback.is-success {
                            display: block;
                            color: #16a34a;
                        }

                        .ws-departments-list {
                            display: flex;
                            flex-direction: column;
                            gap: 10px;
                            align-items: stretch;
                        }

                        .ws-department-row {
                            display: flex;
                            align-items: center;
                            justify-content: space-between;
                            gap: 14px;
                            width: 100%;
                            max-width: 560px;
                            border: 1px solid #e2e8f0;
                            border-radius: 12px;
                            background: #fff;
                            padding: 8px 10px;
                        }

                        .ws-department-chip-wrap {
                            flex: 1 1 auto;
                            min-width: 0;
                        }

                        .ws-department-chip {
                            display: inline-flex;
                            align-items: center;
                            min-height: 30px;
                            border-radius: 999px;
                            padding: 0 12px;
                            font-size: 12px;
                            font-weight: 700;
                            color: #334155;
                            border: 1px solid transparent;
                            white-space: nowrap;
                            max-width: 100%;
                        }

                        .ws-department-actions {
                            flex: 0 0 auto;
                            display: flex;
                            align-items: center;
                        }

                        .ws-department-delete {
                            display: inline-flex;
                            align-items: center;
                            justify-content: center;
                            min-height: 30px;
                            border: 1px solid #e2e8f0;
                            border-radius: 999px;
                            background: #fff;
                            color: #334155;
                            font-size: 12px;
                            font-weight: 700;
                            cursor: pointer;
                            padding: 0 12px;
                            line-height: 1;
                            transition: background .15s ease, border-color .15s ease;
                        }

                        .ws-department-delete:hover {
                            background: #f8fafc;
                            border-color: #cbd5e1;
                        }

                        @media (max-width: 920px) {
                            .ws-departments-row {
                                grid-template-columns: 1fr;
                            }
                        }
                    </style>

                    <div class="ws-departments-shell">
                        <div class="ws-departments-create">
                            <div class="ws-section-subtitle" style="margin-top:0; margin-bottom:10px;">
                                Create workspace departments here. Inbox will later use these departments for conversation assignment.
                            </div>

                            <div class="ws-departments-row">
                                <input id="wsNewDepartmentName" class="ws-input" type="text" placeholder="New department name">
                                <input id="wsNewDepartmentColor" class="ws-color" type="color" value="#6366f1">
                                <button id="wsCreateDepartmentBtn" class="ws-btn" type="button">Add department</button>
                            </div>

                            <div id="wsDepartmentsCreateFeedback" class="ws-departments-feedback"></div>
                        </div>

                        <div class="ws-departments-list-card">
                            <div class="ws-section-subtitle" style="margin-top:0; margin-bottom:12px;">
                                Existing workspace departments
                            </div>

                            <div id="wsDepartmentsList" class="ws-departments-list">
                                @forelse ($workspaceDepartments as $department)
                                    <div class="ws-department-row" data-department-id="{{ $department->id }}">
                                        <div class="ws-department-chip-wrap">
                                            <span
                                                class="ws-department-chip"
                                                style="background: {{ $department->color }}20; border-color: {{ $department->color }}55;"
                                            >
                                                <span class="ws-department-label">{{ $department->name }}</span>
                                            </span>
                                        </div>

                                        <div class="ws-department-actions">
                                            <button
                                                type="button"
                                                class="ws-department-delete"
                                                data-delete-url="{{ route('settings.departments.delete', $department) }}"
                                                title="Remove department"
                                            >
                                                Remove
                                            </button>
                                        </div>
                                    </div>
                                @empty
                                    <div class="ws-empty" id="wsDepartmentsEmpty">No departments created yet.</div>
                                @endforelse
                            </div>
                        </div>
                    </div>

                    <script>
                        document.addEventListener('DOMContentLoaded', function () {
                            const csrfToken = '{{ csrf_token() }}';

                            const createBtn = document.getElementById('wsCreateDepartmentBtn');
                            const createName = document.getElementById('wsNewDepartmentName');
                            const createColor = document.getElementById('wsNewDepartmentColor');
                            const createFeedback = document.getElementById('wsDepartmentsCreateFeedback');
                            const departmentsList = document.getElementById('wsDepartmentsList');

                            const setDepartmentFeedback = (el, message, type) => {
                                if (!el) return;
                                el.textContent = message || '';
                                el.classList.remove('is-error', 'is-success');

                                if (!message) {
                                    el.style.display = 'none';
                                    return;
                                }

                                el.classList.add(type === 'success' ? 'is-success' : 'is-error');
                                el.style.display = 'block';
                            };

                            const normalizeDepartmentColor = (value) => {
                                const color = String(value || '');
                                return /^#[0-9a-fA-F]{6}$/.test(color) ? color.toLowerCase() : '#6366f1';
                            };

                            const buildDepartmentRow = (department) => {
                                const wrapper = document.createElement('div');
                                wrapper.className = 'ws-department-row';
                                wrapper.setAttribute('data-department-id', String(department.id));

                                const color = normalizeDepartmentColor(department.color);
                                const chipWrap = document.createElement('div');
                                chipWrap.className = 'ws-department-chip-wrap';

                                const chip = document.createElement('span');
                                chip.className = 'ws-department-chip';
                                chip.style.backgroundColor = `${color}20`;
                                chip.style.borderColor = `${color}55`;

                                const label = document.createElement('span');
                                label.className = 'ws-department-label';
                                label.textContent = String(department.name || '');
                                chip.append(label);
                                chipWrap.append(chip);

                                const actions = document.createElement('div');
                                actions.className = 'ws-department-actions';

                                const deleteButton = document.createElement('button');
                                deleteButton.type = 'button';
                                deleteButton.className = 'ws-department-delete';
                                deleteButton.dataset.deleteUrl = `/settings/departments/${encodeURIComponent(String(department.id))}`;
                                deleteButton.title = 'Remove department';
                                deleteButton.textContent = 'Remove';
                                actions.append(deleteButton);

                                wrapper.append(chipWrap, actions);
                                return wrapper;
                            };

                            if (createBtn && createName && createColor && departmentsList) {
                                createBtn.addEventListener('click', async function () {
                                    setDepartmentFeedback(createFeedback, '', 'error');

                                    const name = createName.value.trim();
                                    const color = createColor.value || '#6366f1';

                                    if (!name) {
                                        setDepartmentFeedback(createFeedback, 'Department name is required.', 'error');
                                        return;
                                    }

                                    const originalText = createBtn.textContent;
                                    createBtn.disabled = true;
                                    createBtn.textContent = 'Adding...';

                                    try {
                                        const formData = new FormData();
                                        formData.append('_token', csrfToken);
                                        formData.append('name', name);
                                        formData.append('color', color);

                                        const response = await fetch(@json(route('settings.departments.create')), {
                                            method: 'POST',
                                            headers: {
                                                'X-Requested-With': 'XMLHttpRequest',
                                                'Accept': 'application/json'
                                            },
                                            body: formData,
                                            credentials: 'same-origin'
                                        });

                                        const data = await response.json().catch(() => ({}));

                                        if (!response.ok) {
                                            setDepartmentFeedback(
                                                createFeedback,
                                                data?.errors?.name?.[0] || data?.message || 'Failed to create department.',
                                                'error'
                                            );
                                            return;
                                        }

                                        const emptyState = departmentsList.querySelector('.ws-empty');
                                        if (emptyState) {
                                            emptyState.remove();
                                        }

                                        departmentsList.appendChild(buildDepartmentRow(data.department));
                                        createName.value = '';
                                        setDepartmentFeedback(createFeedback, 'Department created.', 'success');
                                    } catch (e) {
                                        setDepartmentFeedback(createFeedback, 'Failed to create department.', 'error');
                                    } finally {
                                        createBtn.disabled = false;
                                        createBtn.textContent = originalText;
                                    }
                                });

                                departmentsList.addEventListener('click', async function (event) {
                                    const button = event.target.closest('.ws-department-delete');
                                    if (!button) return;

                                    event.preventDefault();
                                    event.stopPropagation();

                                    const row = button.closest('.ws-department-row');
                                    if (!row) return;

                                    setDepartmentFeedback(createFeedback, '', 'error');

                                    const deleteUrl = button.getAttribute('data-delete-url');
                                    if (!deleteUrl) {
                                        setDepartmentFeedback(createFeedback, 'Delete URL not found.', 'error');
                                        return;
                                    }

                                    const originalText = button.textContent;
                                    button.disabled = true;
                                    button.textContent = 'Removing...';

                                    try {
                                        const response = await fetch(deleteUrl, {
                                            method: 'DELETE',
                                            headers: {
                                                'X-Requested-With': 'XMLHttpRequest',
                                                'Accept': 'application/json',
                                                'X-CSRF-TOKEN': csrfToken
                                            },
                                            credentials: 'same-origin'
                                        });

                                        const data = await response.json().catch(() => ({}));

                                        if (!response.ok) {
                                            setDepartmentFeedback(
                                                createFeedback,
                                                data?.message || 'Failed to delete department.',
                                                'error'
                                            );
                                            button.disabled = false;
                                            button.textContent = originalText;
                                            return;
                                        }

                                        row.remove();

                                        if (!departmentsList.querySelector('.ws-department-row')) {
                                            const empty = document.createElement('div');
                                            empty.className = 'ws-empty';
                                            empty.id = 'wsDepartmentsEmpty';
                                            empty.textContent = 'No departments created yet.';
                                            departmentsList.appendChild(empty);
                                        }

                                        setDepartmentFeedback(createFeedback, 'Department deleted.', 'success');
                                    } catch (e) {
                                        setDepartmentFeedback(createFeedback, 'Failed to delete department.', 'error');
                                        button.disabled = false;
                                        button.textContent = originalText;
                                    }
                                });
                            }
                        });
                    </script>
                @elseif ($section === 'team')
                    <style>
                        .ws-team-shell {
                            margin-top: 18px;
                            display: grid;
                            gap: 14px;
                        }

                        .ws-team-card,
                        .ws-team-create-card,
                        .ws-team-list-card {
                            border: 1px solid #e2e8f0;
                            border-radius: 14px;
                            background: #fff;
                            padding: 14px;
                        }

                        .ws-team-note {
                            border: 1px dashed #cbd5e1;
                            border-radius: 12px;
                            background: #f8fafc;
                            padding: 14px;
                            color: #475569;
                            font-size: 13px;
                            line-height: 1.6;
                        }

                        .ws-team-list {
                            display: flex;
                            flex-direction: column;
                            gap: 10px;
                            align-items: stretch;
                        }

                        .ws-team-form {
                            display: grid;
                            gap: 12px;
                        }

                        .ws-team-form-grid {
                            display: grid;
                            grid-template-columns: repeat(2, minmax(0, 1fr));
                            gap: 10px;
                        }

                        .ws-team-form-field {
                            display: grid;
                            gap: 6px;
                        }

                        .ws-team-label {
                            font-size: 12px;
                            font-weight: 800;
                            color: #334155;
                        }

                        .ws-team-input {
                            width: 100%;
                            height: 40px;
                            border: 1px solid #cbd5e1;
                            border-radius: 10px;
                            padding: 0 12px;
                            font-size: 13px;
                            color: #0f172a;
                            background: #fff;
                            outline: none;
                        }

                        .ws-team-input:focus {
                            border-color: #818cf8;
                            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.12);
                        }

                        .ws-team-help {
                            font-size: 12px;
                            line-height: 1.5;
                            color: #64748b;
                        }

                        .ws-team-error {
                            font-size: 12px;
                            font-weight: 700;
                            color: #dc2626;
                        }

                        .ws-team-success {
                            border: 1px solid #bbf7d0;
                            border-radius: 10px;
                            background: #f0fdf4;
                            padding: 10px 12px;
                            font-size: 13px;
                            font-weight: 700;
                            color: #166534;
                        }

                        .ws-team-row {
                            display: flex;
                            align-items: center;
                            justify-content: space-between;
                            gap: 14px;
                            width: 100%;
                            max-width: 760px;
                            border: 1px solid #e2e8f0;
                            border-radius: 12px;
                            background: #fff;
                            padding: 10px 12px;
                        }

                        .ws-team-left-wrap {
                            display: flex;
                            align-items: center;
                            gap: 10px;
                            min-width: 0;
                            flex: 1 1 auto;
                        }

                        .ws-team-avatar {
                            width: 42px;
                            height: 42px;
                            border-radius: 999px;
                            object-fit: cover;
                            flex: 0 0 auto;
                            border: 1px solid #cbd5e1;
                            background: #f8fafc;
                        }

                        .ws-team-left {
                            min-width: 0;
                            flex: 1 1 auto;
                        }

                        .ws-team-name {
                            font-size: 14px;
                            font-weight: 800;
                            color: #0f172a;
                            line-height: 1.4;
                        }

                        .ws-team-meta {
                            margin-top: 4px;
                            font-size: 12px;
                            color: #64748b;
                            line-height: 1.5;
                            word-break: break-word;
                        }

                        .ws-team-badges {
                            flex: 0 0 auto;
                            display: flex;
                            align-items: center;
                            gap: 8px;
                            flex-wrap: wrap;
                            justify-content: flex-end;
                        }

                        .ws-team-role {
                            display: inline-flex;
                            align-items: center;
                            min-height: 28px;
                            border-radius: 999px;
                            padding: 0 10px;
                            background: #eef2ff;
                            color: #4338ca;
                            border: 1px solid #c7d2fe;
                            font-size: 11px;
                            font-weight: 800;
                            text-transform: capitalize;
                        }

                        .ws-team-agent {
                            display: inline-flex;
                            align-items: center;
                            min-height: 28px;
                            border-radius: 999px;
                            padding: 0 10px;
                            background: #ecfeff;
                            color: #0f766e;
                            border: 1px solid #a5f3fc;
                            font-size: 11px;
                            font-weight: 800;
                        }

                        @media (max-width: 920px) {
                            .ws-team-form-grid {
                                grid-template-columns: 1fr;
                            }

                            .ws-team-row {
                                flex-direction: column;
                                align-items: flex-start;
                            }

                            .ws-team-badges {
                                justify-content: flex-start;
                            }
                        }
                    </style>

                    <div class="ws-team-shell">
                        <div class="ws-team-card">
                            <div class="ws-section-subtitle" style="margin-top:0; margin-bottom:10px;">
                                Workspace team
                            </div>

                            <div class="ws-team-note">
                                Every member in this workspace can sign in with their own email and password, update their own name, and manage their own profile picture.
                            </div>
                        </div>

                        @if ($canManageTeam)
                            <div class="ws-team-create-card">
                                <div class="ws-section-subtitle" style="margin-top:0; margin-bottom:12px;">
                                    Add team member
                                </div>

                                @if (session('status'))
                                    <div class="ws-team-success" style="margin-bottom:12px;">
                                        {{ session('status') }}
                                    </div>
                                @endif

                                <form method="POST" action="{{ route('settings.team.create') }}" class="ws-team-form">
                                    @csrf

                                    <div class="ws-team-form-grid">
                                        <div class="ws-team-form-field">
                                            <label class="ws-team-label" for="wsTeamName">Full name</label>
                                            <input
                                                id="wsTeamName"
                                                name="name"
                                                type="text"
                                                class="ws-team-input"
                                                value="{{ old('name') }}"
                                                maxlength="255"
                                                required
                                            >
                                            @error('name')
                                                <div class="ws-team-error">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <div class="ws-team-form-field">
                                            <label class="ws-team-label" for="wsTeamEmail">Email</label>
                                            <input
                                                id="wsTeamEmail"
                                                name="email"
                                                type="email"
                                                class="ws-team-input"
                                                value="{{ old('email') }}"
                                                maxlength="255"
                                                required
                                            >
                                            @error('email')
                                                <div class="ws-team-error">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>

                                    <div class="ws-team-form-grid">
                                        <div class="ws-team-form-field">
                                            <label class="ws-team-label" for="wsTeamPassword">Password</label>
                                            <input
                                                id="wsTeamPassword"
                                                name="password"
                                                type="password"
                                                class="ws-team-input"
                                                required
                                            >
                                            @error('password')
                                                <div class="ws-team-error">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <div class="ws-team-form-field">
                                            <label class="ws-team-label" for="wsTeamPasswordConfirmation">Confirm password</label>
                                            <input
                                                id="wsTeamPasswordConfirmation"
                                                name="password_confirmation"
                                                type="password"
                                                class="ws-team-input"
                                                required
                                            >
                                        </div>
                                    </div>

                                    <div class="ws-team-help">
                                        The teammate will receive a verification email and cannot access the workspace until they verify the address.
                                    </div>

                                    <div style="display:flex; justify-content:flex-end;">
                                        <button class="ws-btn" type="submit">Create member</button>
                                    </div>
                                </form>
                            </div>
                        @endif

                        <div class="ws-team-list-card">
                            <div class="ws-section-subtitle" style="margin-top:0; margin-bottom:12px;">
                                Workspace members
                            </div>

                            <div class="ws-team-list">
                                @forelse ($workspaceMembers as $member)
                                    @php
                                        $memberAvatar = $member->avatar_url ?: ('https://ui-avatars.com/api/?name=' . urlencode($member->name ?: 'User') . '&background=e2e8f0&color=334155');
                                    @endphp
                                    <div class="ws-team-row">
                                        <div class="ws-team-left-wrap">
                                            <img class="ws-team-avatar" src="{{ $memberAvatar }}" alt="{{ $member->name ?: 'User' }}">

                                            <div class="ws-team-left">
                                                <div class="ws-team-name">
                                                    {{ $member->name ?: 'Unnamed user' }}
                                                </div>
                                                <div class="ws-team-meta">
                                                    {{ $member->email ?: 'No email' }}
                                                </div>
                                            </div>
                                        </div>

                                        <div class="ws-team-badges">
                                            <span class="ws-team-role">
                                                {{ $member->pivot->role ?: 'member' }}
                                            </span>
                                            @if (auth()->id() === $member->id)
                                                <span class="ws-team-agent">
                                                    You
                                                </span>
                                            @endif
                                            <span class="ws-team-agent">
                                                Agent
                                            </span>
                                        </div>
                                    </div>
                                @empty
                                    <div class="ws-empty">
                                        No workspace members found.
                                    </div>
                                @endforelse
                            </div>
                        </div>
                    </div>
                @elseif ($section === 'channels')
                    <style>
                        .ws-channels-shell {
                            margin-top: 18px;
                            display: grid;
                            gap: 14px;
                        }

                        .ws-channels-overview,
                        .ws-channels-providers,
                        .ws-channels-saved {
                            border: 1px solid #e2e8f0;
                            border-radius: 14px;
                            background: #fff;
                            padding: 14px;
                        }

                        .ws-channels-overview-grid {
                            display: grid;
                            grid-template-columns: repeat(3, minmax(0, 1fr));
                            gap: 12px;
                            margin-top: 12px;
                        }

                        .ws-channels-stat {
                            border: 1px solid #e2e8f0;
                            border-radius: 12px;
                            background: #f8fafc;
                            padding: 12px;
                        }

                        .ws-channels-stat-label {
                            font-size: 12px;
                            font-weight: 700;
                            color: #64748b;
                        }

                        .ws-channels-stat-value {
                            margin-top: 6px;
                            font-size: 20px;
                            font-weight: 800;
                            color: #0f172a;
                        }

                        .ws-provider-grid {
                            margin-top: 12px;
                            display: grid;
                            grid-template-columns: repeat(3, minmax(0, 1fr));
                            gap: 12px;
                        }

                        .ws-provider-card {
                            border: 1px solid #e2e8f0;
                            border-radius: 14px;
                            background: #fff;
                            padding: 14px;
                            display: flex;
                            flex-direction: column;
                            gap: 12px;
                            min-height: 220px;
                        }

                        .ws-provider-top {
                            display: flex;
                            align-items: flex-start;
                            justify-content: space-between;
                            gap: 10px;
                        }

                        .ws-provider-name {
                            font-size: 16px;
                            font-weight: 800;
                            color: #0f172a;
                            line-height: 1.4;
                        }

                        .ws-provider-sub {
                            margin-top: 6px;
                            font-size: 13px;
                            color: #64748b;
                            line-height: 1.6;
                        }

                        .ws-provider-badge {
                            display: inline-flex;
                            align-items: center;
                            min-height: 24px;
                            border-radius: 999px;
                            padding: 0 10px;
                            font-size: 11px;
                            font-weight: 800;
                            white-space: nowrap;
                        }

                        .ws-provider-badge.ready {
                            background: #ecfeff;
                            color: #0f766e;
                            border: 1px solid #a5f3fc;
                        }

                        .ws-provider-badge.soon {
                            background: #fff7ed;
                            color: #c2410c;
                            border: 1px solid #fdba74;
                        }

                        .ws-provider-meta {
                            display: flex;
                            flex-direction: column;
                            gap: 8px;
                            font-size: 13px;
                            color: #475569;
                        }

                        .ws-provider-meta-row {
                            display: flex;
                            justify-content: space-between;
                            gap: 10px;
                        }

                        .ws-provider-meta-key {
                            color: #64748b;
                            font-weight: 700;
                        }

                        .ws-provider-meta-val {
                            color: #0f172a;
                            font-weight: 700;
                            text-align: right;
                        }

                        .ws-provider-actions {
                            margin-top: auto;
                            display: flex;
                            gap: 8px;
                            flex-wrap: wrap;
                        }

                        .ws-provider-btn,
                        .ws-provider-btn-muted {
                            display: inline-flex;
                            align-items: center;
                            justify-content: center;
                            min-height: 36px;
                            border-radius: 10px;
                            padding: 0 12px;
                            font-size: 13px;
                            font-weight: 800;
                            text-decoration: none;
                            border: 1px solid #e2e8f0;
                        }

                        .ws-provider-btn {
                            background: #fff;
                            color: #334155;
                        }

                        .ws-provider-btn:hover {
                            background: #f8fafc;
                            border-color: #cbd5e1;
                        }

                        .ws-provider-btn-muted {
                            background: #f8fafc;
                            color: #94a3b8;
                            cursor: not-allowed;
                        }

                        .ws-connections-list {
                            margin-top: 12px;
                            display: flex;
                            flex-direction: column;
                            gap: 10px;
                        }

                        .ws-connection-row {
                            display: flex;
                            align-items: center;
                            justify-content: space-between;
                            gap: 14px;
                            border: 1px solid #e2e8f0;
                            border-radius: 12px;
                            background: #fff;
                            padding: 12px 14px;
                        }

                        .ws-connection-left {
                            min-width: 0;
                            flex: 1 1 auto;
                        }

                        .ws-connection-title {
                            font-size: 14px;
                            font-weight: 800;
                            color: #0f172a;
                            line-height: 1.4;
                        }

                        .ws-connection-meta {
                            margin-top: 4px;
                            font-size: 12px;
                            color: #64748b;
                            line-height: 1.6;
                        }

                        .ws-connection-badges {
                            flex: 0 0 auto;
                            display: flex;
                            align-items: center;
                            gap: 8px;
                            flex-wrap: wrap;
                            justify-content: flex-end;
                        }

                        .ws-connection-status,
                        .ws-connection-kind {
                            display: inline-flex;
                            align-items: center;
                            min-height: 28px;
                            border-radius: 999px;
                            padding: 0 10px;
                            font-size: 11px;
                            font-weight: 800;
                            text-transform: capitalize;
                        }

                        .ws-connection-status.connected {
                            background: #ecfdf5;
                            color: #166534;
                            border: 1px solid #86efac;
                        }

                        .ws-connection-status.pending {
                            background: #fff7ed;
                            color: #c2410c;
                            border: 1px solid #fdba74;
                        }

                        .ws-connection-status.failed {
                            background: #fef2f2;
                            color: #b91c1c;
                            border: 1px solid #fca5a5;
                        }

                        .ws-connection-kind {
                            background: #eef2ff;
                            color: #4338ca;
                            border: 1px solid #c7d2fe;
                        }

                        @media (max-width: 1080px) {
                            .ws-provider-grid {
                                grid-template-columns: 1fr;
                            }

                            .ws-channels-overview-grid {
                                grid-template-columns: 1fr;
                            }
                        }

                        @media (max-width: 920px) {
                            .ws-connection-row {
                                flex-direction: column;
                                align-items: flex-start;
                            }

                            .ws-connection-badges {
                                justify-content: flex-start;
                            }
                        }
                    </style>

                    @php
                        $connectedCount = ($providerConnections ?? collect())->where('status', 'connected')->count();
                    @endphp

                    <div class="ws-channels-shell">
                        <div class="ws-channels-overview">
                            <div class="ws-section-subtitle" style="margin-top:0; margin-bottom:2px;">
                                Channel connection overview
                            </div>

                            <div class="ws-channels-overview-grid">
                                <div class="ws-channels-stat">
                                    <div class="ws-channels-stat-label">Workspace</div>
                                    <div class="ws-channels-stat-value">{{ $workspace?->name ?: '-' }}</div>
                                </div>

                                <div class="ws-channels-stat">
                                    <div class="ws-channels-stat-label">Total saved connections</div>
                                    <div class="ws-channels-stat-value">{{ ($providerConnections ?? collect())->count() }}</div>
                                </div>

                                <div class="ws-channels-stat">
                                    <div class="ws-channels-stat-label">Connected</div>
                                    <div class="ws-channels-stat-value">{{ $connectedCount }}</div>
                                </div>
                            </div>
                        </div>

                        <div class="ws-channels-providers">
                            <div class="ws-section-subtitle" style="margin-top:0; margin-bottom:2px;">
                                Available channel providers
                            </div>

                            <div class="ws-provider-grid">
                                @foreach (($providerCards ?? collect()) as $card)
                                    @php
                                        $providerKey = $card['key'];
                                        $savedForProvider = ($providerConnections ?? collect())->where('provider', $providerKey);
                                        $hasConnectedProvider = $savedForProvider->where('status', 'connected')->isNotEmpty();
                                    @endphp

                                    <div class="ws-provider-card">
                                        <div class="ws-provider-top">
                                            <div>
                                                <div class="ws-provider-name">{{ $card['title'] }}</div>
                                                <div class="ws-provider-sub">{{ $card['subtitle'] }}</div>
                                            </div>

                                            <span class="ws-provider-badge {{ !empty($card['is_ready']) ? 'ready' : 'soon' }}">
                                                {{ !empty($card['is_ready']) ? 'Ready' : 'Coming next' }}
                                            </span>
                                        </div>

                                        <div class="ws-provider-meta">
                                            <div class="ws-provider-meta-row">
                                                <span class="ws-provider-meta-key">Account type</span>
                                                <span class="ws-provider-meta-val">{{ $card['account_type'] }}</span>
                                            </div>
                                            <div class="ws-provider-meta-row">
                                                <span class="ws-provider-meta-key">Saved connections</span>
                                                <span class="ws-provider-meta-val">{{ $savedForProvider->count() }}</span>
                                            </div>
                                            <div class="ws-provider-meta-row">
                                                <span class="ws-provider-meta-key">Current status</span>
                                                <span class="ws-provider-meta-val">{{ $hasConnectedProvider ? 'Connected' : 'Not connected' }}</span>
                                            </div>
                                        </div>

                                        <div class="ws-provider-actions">
                                            @if (!empty($card['connect_url']))
                                                <a href="{{ $card['connect_url'] }}" class="ws-provider-btn">
                                                    {{ $hasConnectedProvider ? 'Reconnect' : 'Connect' }}
                                                </a>
                                            @else
                                                <span class="ws-provider-btn-muted">Connect coming next</span>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        <div class="ws-channels-saved">
                            <div class="ws-section-subtitle" style="margin-top:0; margin-bottom:2px;">
                                Saved connections
                            </div>

                            <div class="ws-connections-list">
                                @forelse (($providerConnections ?? collect()) as $connection)
                                    <div class="ws-connection-row">
                                        <div class="ws-connection-left">
                                            <div class="ws-connection-title">
                                                {{ ucfirst($connection->provider) }}
                                                @if ($connection->account_name)
                                                    — {{ $connection->account_name }}
                                                @endif
                                            </div>
                                            <div class="ws-connection-meta">
                                                <div>Account type: {{ $connection->account_type ?: '-' }}</div>
                                                <div>Connected at: {{ optional($connection->created_at)->format('M d, Y H:i') ?: '-' }}</div>
                                            </div>
                                        </div>

                                        <div class="ws-connection-badges">
                                            <span class="ws-connection-status {{ $connection->status }}">
                                                {{ $connection->status }}
                                            </span>
                                            <span class="ws-connection-kind">
                                                {{ $connection->connection_kind ?: 'standard' }}
                                            </span>
                                        </div>
                                    </div>
                                @empty
                                    <div class="ws-empty">
                                        No provider connections have been saved for this workspace yet.
                                    </div>
                                @endforelse
                            </div>
                        </div>
                    </div>
                @elseif ($section === 'catalogs')
                    <style>
                        .ws-catalog-shell {
                            margin-top: 18px;
                            display: grid;
                            gap: 14px;
                        }

                        .ws-catalog-card {
                            border: 1px solid #e2e8f0;
                            border-radius: 8px;
                            background: #fff;
                            padding: 14px;
                        }

                        .ws-catalog-grid {
                            display: grid;
                            grid-template-columns: repeat(2, minmax(0, 1fr));
                            gap: 12px;
                        }

                        .ws-catalog-form {
                            display: grid;
                            gap: 12px;
                            margin-top: 12px;
                        }

                        .ws-catalog-form-grid {
                            display: grid;
                            grid-template-columns: repeat(3, minmax(0, 1fr));
                            gap: 12px;
                        }

                        .ws-catalog-form-field {
                            display: grid;
                            gap: 6px;
                        }

                        .ws-catalog-form-field.full {
                            grid-column: 1 / -1;
                        }

                        .ws-catalog-label {
                            font-size: 12px;
                            font-weight: 800;
                            color: #334155;
                        }

                        .ws-catalog-input,
                        .ws-catalog-select,
                        .ws-catalog-textarea {
                            width: 100%;
                            border: 1px solid #cbd5e1;
                            border-radius: 8px;
                            background: #fff;
                            color: #0f172a;
                            font-size: 14px;
                            outline: none;
                        }

                        .ws-catalog-input,
                        .ws-catalog-select {
                            height: 40px;
                            padding: 0 11px;
                        }

                        .ws-catalog-textarea {
                            min-height: 86px;
                            padding: 10px 11px;
                            resize: vertical;
                        }

                        .ws-catalog-input:focus,
                        .ws-catalog-select:focus,
                        .ws-catalog-textarea:focus {
                            border-color: #0f766e;
                            box-shadow: 0 0 0 3px rgba(15, 118, 110, .12);
                        }

                        .ws-catalog-submit,
                        .ws-catalog-danger,
                        .ws-catalog-secondary {
                            display: inline-flex;
                            align-items: center;
                            justify-content: center;
                            min-height: 38px;
                            border-radius: 8px;
                            padding: 0 13px;
                            font-size: 13px;
                            font-weight: 900;
                            border: 0;
                            cursor: pointer;
                        }

                        .ws-catalog-submit {
                            background: #0f766e;
                            color: #fff;
                        }

                        .ws-catalog-danger {
                            background: #fef2f2;
                            color: #b91c1c;
                            border: 1px solid #fecaca;
                        }

                        .ws-catalog-secondary {
                            background: #f8fafc;
                            color: #334155;
                            border: 1px solid #cbd5e1;
                        }

                        .ws-catalog-help {
                            color: #64748b;
                            font-size: 12px;
                            line-height: 1.55;
                        }

                        .ws-catalog-error {
                            color: #dc2626;
                            font-size: 12px;
                            font-weight: 800;
                        }

                        .ws-catalog-success {
                            border: 1px solid #bbf7d0;
                            border-radius: 8px;
                            background: #f0fdf4;
                            padding: 10px 12px;
                            font-size: 13px;
                            font-weight: 800;
                            color: #166534;
                        }

                        .ws-catalog-list {
                            display: grid;
                            gap: 12px;
                            margin-top: 12px;
                        }

                        .ws-catalog-row {
                            border: 1px solid #e2e8f0;
                            border-radius: 8px;
                            background: #fff;
                            overflow: hidden;
                        }

                        .ws-catalog-row-head {
                            display: flex;
                            justify-content: space-between;
                            gap: 12px;
                            padding: 14px;
                            border-bottom: 1px solid #e2e8f0;
                            background: #f8fafc;
                        }

                        .ws-catalog-name {
                            font-size: 15px;
                            font-weight: 900;
                            color: #0f172a;
                        }

                        .ws-catalog-meta {
                            margin-top: 4px;
                            color: #64748b;
                            font-size: 12px;
                            line-height: 1.55;
                        }

                        .ws-catalog-pill {
                            display: inline-flex;
                            align-items: center;
                            min-height: 24px;
                            border-radius: 999px;
                            padding: 0 9px;
                            background: #ecfdf5;
                            border: 1px solid #86efac;
                            color: #166534;
                            font-size: 11px;
                            font-weight: 900;
                            white-space: nowrap;
                        }

                        .ws-product-list {
                            display: grid;
                            gap: 10px;
                            padding: 14px;
                        }

                        .ws-product-row {
                            display: grid;
                            grid-template-columns: 64px minmax(0, 1fr) auto;
                            align-items: center;
                            gap: 12px;
                            border: 1px solid #e2e8f0;
                            border-radius: 8px;
                            padding: 10px;
                        }

                        .ws-product-thumb {
                            width: 64px;
                            height: 64px;
                            border-radius: 8px;
                            background: #f1f5f9;
                            border: 1px solid #e2e8f0;
                            overflow: hidden;
                            display: grid;
                            place-items: center;
                            color: #64748b;
                            font-weight: 900;
                            font-size: 11px;
                        }

                        .ws-product-thumb img {
                            width: 100%;
                            height: 100%;
                            object-fit: cover;
                        }

                        .ws-product-title {
                            font-size: 14px;
                            font-weight: 900;
                            color: #0f172a;
                            line-height: 1.35;
                        }

                        .ws-product-meta {
                            margin-top: 4px;
                            color: #64748b;
                            font-size: 12px;
                            line-height: 1.55;
                        }

                        .ws-product-actions {
                            display: flex;
                            flex-wrap: wrap;
                            justify-content: flex-end;
                            gap: 8px;
                        }

                        .ws-product-status {
                            display: inline-flex;
                            align-items: center;
                            min-height: 22px;
                            border-radius: 999px;
                            padding: 0 8px;
                            font-size: 11px;
                            font-weight: 900;
                        }

                        .ws-product-status.active {
                            background: #ecfdf5;
                            border: 1px solid #86efac;
                            color: #166534;
                        }

                        .ws-product-status.inactive {
                            background: #f1f5f9;
                            border: 1px solid #cbd5e1;
                            color: #475569;
                        }

                        .ws-product-edit {
                            margin-top: 10px;
                        }

                        .ws-product-edit summary {
                            color: #0f766e;
                            cursor: pointer;
                            font-size: 12px;
                            font-weight: 900;
                        }

                        .ws-catalog-import {
                            border-top: 1px solid #e2e8f0;
                            padding: 14px;
                            background: #f8fafc;
                        }

                        .ws-product-empty {
                            padding: 14px;
                            color: #64748b;
                            font-size: 13px;
                            line-height: 1.6;
                        }

                        @media (max-width: 1080px) {
                            .ws-catalog-grid,
                            .ws-catalog-form-grid {
                                grid-template-columns: 1fr;
                            }

                            .ws-product-row {
                                grid-template-columns: 56px minmax(0, 1fr);
                            }

                            .ws-product-row form {
                                grid-column: 1 / -1;
                            }
                        }
                    </style>

                    <div class="ws-catalog-shell">
                        @if (session('status'))
                            <div class="ws-catalog-success">
                                {{ session('status') }}
                            </div>
                        @endif

                        <div class="ws-catalog-grid">
                            <div class="ws-catalog-card">
                                <h3 class="ws-section-title">Create catalog</h3>
                                <div class="ws-section-subtitle">
                                    Start with a Leadochat catalog, optionally tied to one connected Instagram account.
                                </div>

                                <form method="POST" action="{{ route('settings.catalogs.store') }}" class="ws-catalog-form">
                                    @csrf

                                    <div class="ws-catalog-form-field">
                                        <label class="ws-catalog-label" for="catalogName">Catalog name</label>
                                        <input
                                            id="catalogName"
                                            name="name"
                                            class="ws-catalog-input"
                                            type="text"
                                            value="{{ old('name') }}"
                                            placeholder="Instagram Shop Catalog"
                                            required
                                        >
                                        @error('name')
                                            <div class="ws-catalog-error">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="ws-catalog-form-field">
                                        <label class="ws-catalog-label" for="catalogProviderConnection">Instagram account</label>
                                        <select id="catalogProviderConnection" name="provider_connection_id" class="ws-catalog-select">
                                            <option value="">All Instagram conversations</option>
                                            @foreach (($catalogProviderConnections ?? collect()) as $connection)
                                                <option value="{{ $connection->id }}" @selected((string) old('provider_connection_id') === (string) $connection->id)>
                                                    {{ $connection->provider_account_name ?: $connection->provider_account_id }}
                                                </option>
                                            @endforeach
                                        </select>
                                        <div class="ws-catalog-help">
                                            Use account-specific catalogs when multiple Instagram accounts are connected.
                                        </div>
                                    </div>

                                    <div>
                                        <button type="submit" class="ws-catalog-submit">Create catalog</button>
                                    </div>
                                </form>
                            </div>

                            <div class="ws-catalog-card">
                                <h3 class="ws-section-title">Catalog sending</h3>
                                <div class="ws-section-subtitle">
                                    Agents will see active products in the inbox product picker and can send them as Instagram DM product cards.
                                </div>
                                <div class="ws-placeholder" style="margin-top:12px;">
                                    Products now send to Instagram as native DM cards. Keep this list clean with SKU updates, active/inactive status, and CSV import when the catalog grows.
                                </div>
                            </div>
                        </div>

                        <div class="ws-catalog-card">
                            <h3 class="ws-section-title">Catalogs and products</h3>

                            <div class="ws-catalog-list">
                                @forelse (($catalogs ?? collect()) as $catalog)
                                    <div class="ws-catalog-row">
                                        <div class="ws-catalog-row-head">
                                            <div>
                                                <div class="ws-catalog-name">{{ $catalog->name }}</div>
                                                <div class="ws-catalog-meta">
                                                    Source: {{ ucfirst($catalog->source) }}
                                                    @if ($catalog->providerConnection)
                                                        · Instagram: {{ $catalog->providerConnection->provider_account_name ?: $catalog->providerConnection->provider_account_id }}
                                                    @else
                                                        · Available for all connected Instagram accounts
                                                    @endif
                                                </div>
                                            </div>

                                            <span class="ws-catalog-pill">
                                                {{ $catalog->products->count() }} products
                                            </span>
                                        </div>

                                        <div class="ws-product-list">
                                            @forelse ($catalog->products as $product)
                                                <div class="ws-product-row">
                                                    <div class="ws-product-thumb">
                                                        @if ($product->image_url)
                                                            <img src="{{ $product->image_url }}" alt="{{ $product->title }}">
                                                        @else
                                                            No image
                                                        @endif
                                                    </div>

                                                    <div>
                                                        @php
                                                            $productBrand = $product->brand ?: data_get($product->metadata, 'brand');
                                                            $productCondition = $product->product_condition ?: data_get($product->metadata, 'condition');
                                                            $productInventory = $product->inventory_quantity ?? data_get($product->metadata, 'inventory');
                                                            $now = now();
                                                            $activeBaseOffer = $product->offers
                                                                ->whereNull('catalog_product_market_override_id')
                                                                ->first(function ($offer) use ($now) {
                                                                    return $offer->isActiveNow($now);
                                                                });
                                                        @endphp
                                                        <div class="ws-product-title">{{ $product->title }}</div>
                                                        <div class="ws-product-meta">
                                                            @if ($product->price !== null)
                                                                {{ strtoupper($product->currency) }} {{ number_format((float) $product->price, 2) }}
                                                            @else
                                                                No price
                                                            @endif
                                                            · {{ str_replace('_', ' ', $product->availability) }}
                                                            @if ($product->sku)
                                                                · SKU: {{ $product->sku }}
                                                            @endif
                                                            ·
                                                            <span class="ws-product-status {{ $product->is_active ? 'active' : 'inactive' }}">
                                                                {{ $product->is_active ? 'Active' : 'Inactive' }}
                                                            </span>
                                                        </div>
                                                        <div class="ws-product-meta">
                                                            @if ($productBrand)
                                                                Brand: {{ $productBrand }}
                                                            @endif
                                                            @if ($productCondition)
                                                                · Condition: {{ str_replace('_', ' ', $productCondition) }}
                                                            @endif
                                                            @if ($productInventory !== null && $productInventory !== '')
                                                                · Inventory: {{ $productInventory }}
                                                            @endif
                                                            @if ($product->sale_price !== null)
                                                                · Sale: {{ strtoupper($product->currency) }} {{ number_format((float) $product->sale_price, 2) }}
                                                            @endif
                                                            @if ($product->content_language)
                                                                · Language: {{ strtoupper(str_replace('_', '-', $product->content_language)) }}
                                                            @endif
                                                            @if ($product->target_country)
                                                                · Market: {{ strtoupper($product->target_country) }}
                                                            @endif
                                                            @if ($activeBaseOffer)
                                                                · Offer: {{ $activeBaseOffer->name }}
                                                            @endif
                                                        </div>
                                                        @if ($product->description)
                                                            <div class="ws-product-meta">{{ $product->description }}</div>
                                                        @endif

                                                        <details class="ws-product-edit">
                                                            <summary>Edit product</summary>

                                                            <form method="POST" action="{{ route('settings.catalogs.products.update', $product) }}" class="ws-catalog-form">
                                                                @csrf
                                                                @method('PATCH')

                                                                <div class="ws-catalog-form-grid">
                                                                    <div class="ws-catalog-form-field">
                                                                        <label class="ws-catalog-label" for="editProductTitle{{ $product->id }}">Product title</label>
                                                                        <input id="editProductTitle{{ $product->id }}" name="title" class="ws-catalog-input" type="text" value="{{ $product->title }}" required>
                                                                    </div>

                                                                    <div class="ws-catalog-form-field">
                                                                        <label class="ws-catalog-label" for="editProductSku{{ $product->id }}">SKU</label>
                                                                        <input id="editProductSku{{ $product->id }}" name="sku" class="ws-catalog-input" type="text" value="{{ $product->sku }}">
                                                                    </div>

                                                                    <div class="ws-catalog-form-field">
                                                                        <label class="ws-catalog-label" for="editProductAvailability{{ $product->id }}">Availability</label>
                                                                        <select id="editProductAvailability{{ $product->id }}" name="availability" class="ws-catalog-select">
                                                                            <option value="in_stock" @selected($product->availability === 'in_stock')>In stock</option>
                                                                            <option value="out_of_stock" @selected($product->availability === 'out_of_stock')>Out of stock</option>
                                                                            <option value="preorder" @selected($product->availability === 'preorder')>Preorder</option>
                                                                        </select>
                                                                    </div>

                                                                    <div class="ws-catalog-form-field">
                                                                        <label class="ws-catalog-label" for="editProductPrice{{ $product->id }}">Price</label>
                                                                        <input id="editProductPrice{{ $product->id }}" name="price" class="ws-catalog-input" type="number" min="0" step="0.01" value="{{ $product->price }}">
                                                                    </div>

                                                                    <div class="ws-catalog-form-field">
                                                                        <label class="ws-catalog-label" for="editProductSalePrice{{ $product->id }}">Sale price</label>
                                                                        <input id="editProductSalePrice{{ $product->id }}" name="sale_price" class="ws-catalog-input" type="number" min="0" step="0.01" value="{{ $product->sale_price }}">
                                                                    </div>

                                                                    <div class="ws-catalog-form-field">
                                                                        <label class="ws-catalog-label" for="editProductCurrency{{ $product->id }}">Currency</label>
                                                                        <input id="editProductCurrency{{ $product->id }}" name="currency" class="ws-catalog-input" type="text" value="{{ strtoupper($product->currency) }}" maxlength="3" required>
                                                                    </div>

                                                                    <div class="ws-catalog-form-field">
                                                                        <label class="ws-catalog-label" for="editProductActive{{ $product->id }}">Inbox picker</label>
                                                                        <select id="editProductActive{{ $product->id }}" name="is_active" class="ws-catalog-select">
                                                                            <option value="1" @selected($product->is_active)>Active</option>
                                                                            <option value="0" @selected(! $product->is_active)>Inactive</option>
                                                                        </select>
                                                                    </div>

                                                                    <div class="ws-catalog-form-field">
                                                                        <label class="ws-catalog-label" for="editProductBrand{{ $product->id }}">Brand</label>
                                                                        <input id="editProductBrand{{ $product->id }}" name="brand" class="ws-catalog-input" type="text" value="{{ $productBrand }}">
                                                                    </div>

                                                                    <div class="ws-catalog-form-field">
                                                                        <label class="ws-catalog-label" for="editProductCondition{{ $product->id }}">Condition</label>
                                                                        <select id="editProductCondition{{ $product->id }}" name="product_condition" class="ws-catalog-select">
                                                                            <option value="" @selected(! $productCondition)>Not set</option>
                                                                            <option value="new" @selected(($productCondition ?? null) === 'new')>New</option>
                                                                            <option value="refurbished" @selected(($productCondition ?? null) === 'refurbished')>Refurbished</option>
                                                                            <option value="used" @selected(($productCondition ?? null) === 'used')>Used</option>
                                                                        </select>
                                                                    </div>

                                                                    <div class="ws-catalog-form-field">
                                                                        <label class="ws-catalog-label" for="editProductInventory{{ $product->id }}">Inventory</label>
                                                                        <input id="editProductInventory{{ $product->id }}" name="inventory_quantity" class="ws-catalog-input" type="number" min="0" step="1" value="{{ $productInventory }}">
                                                                    </div>

                                                                    <div class="ws-catalog-form-field">
                                                                        <label class="ws-catalog-label" for="editProductLanguage{{ $product->id }}">Content language</label>
                                                                        <input id="editProductLanguage{{ $product->id }}" name="content_language" class="ws-catalog-input" type="text" value="{{ $product->content_language }}" placeholder="en or fa_IR">
                                                                    </div>

                                                                    <div class="ws-catalog-form-field">
                                                                        <label class="ws-catalog-label" for="editProductCountry{{ $product->id }}">Target country</label>
                                                                        <input id="editProductCountry{{ $product->id }}" name="target_country" class="ws-catalog-input" type="text" value="{{ $product->target_country }}" maxlength="2" placeholder="US">
                                                                    </div>

                                                                    <div class="ws-catalog-form-field full">
                                                                        <label class="ws-catalog-label" for="editProductCategory{{ $product->id }}">Google product category</label>
                                                                        <input id="editProductCategory{{ $product->id }}" name="google_product_category" class="ws-catalog-input" type="text" value="{{ $product->google_product_category }}" placeholder="Books > Education">
                                                                    </div>

                                                                    <div class="ws-catalog-form-field full">
                                                                        <label class="ws-catalog-label" for="editProductUrl{{ $product->id }}">Product URL</label>
                                                                        <input id="editProductUrl{{ $product->id }}" name="product_url" class="ws-catalog-input" type="url" value="{{ $product->product_url }}" placeholder="https://...">
                                                                    </div>

                                                                    <div class="ws-catalog-form-field full">
                                                                        <label class="ws-catalog-label" for="editProductImage{{ $product->id }}">Image URL</label>
                                                                        <input id="editProductImage{{ $product->id }}" name="image_url" class="ws-catalog-input" type="url" value="{{ $product->image_url }}" placeholder="https://...">
                                                                    </div>

                                                                    <div class="ws-catalog-form-field full">
                                                                        <label class="ws-catalog-label" for="editProductDescription{{ $product->id }}">Description</label>
                                                                        <textarea id="editProductDescription{{ $product->id }}" name="description" class="ws-catalog-textarea">{{ $product->description }}</textarea>
                                                                    </div>

                                                                    <div class="ws-catalog-form-field">
                                                                        <label class="ws-catalog-label" for="editProductSaleStart{{ $product->id }}">Sale starts</label>
                                                                        <input id="editProductSaleStart{{ $product->id }}" name="sale_price_effective_start_at" class="ws-catalog-input" type="datetime-local" value="{{ optional($product->sale_price_effective_start_at)->format('Y-m-d\TH:i') }}">
                                                                    </div>

                                                                    <div class="ws-catalog-form-field">
                                                                        <label class="ws-catalog-label" for="editProductSaleEnd{{ $product->id }}">Sale ends</label>
                                                                        <input id="editProductSaleEnd{{ $product->id }}" name="sale_price_effective_end_at" class="ws-catalog-input" type="datetime-local" value="{{ optional($product->sale_price_effective_end_at)->format('Y-m-d\TH:i') }}">
                                                                    </div>
                                                                </div>

                                                                <div>
                                                                    <button type="submit" class="ws-catalog-submit">Save product</button>
                                                                </div>
                                                            </form>

                                                            <div class="ws-catalog-import" style="margin-top:14px;">
                                                                <h4 class="ws-product-title">Localized market profiles</h4>
                                                                <div class="ws-catalog-help">
                                                                    Add country and language specific price, copy, URL, and category overrides without duplicating the base product.
                                                                </div>

                                                                <div style="display:grid; gap:12px; margin-top:12px;">
                                                                    @forelse ($product->marketOverrides as $marketOverride)
                                                                        <div style="border:1px solid #e2e8f0; border-radius:8px; padding:12px; display:grid; gap:12px;">
                                                                            <div class="ws-product-meta">
                                                                                {{ strtoupper($marketOverride->target_country) }} · {{ strtoupper(str_replace('_', '-', $marketOverride->content_language)) }}
                                                                                · {{ $marketOverride->is_active ? 'Active' : 'Inactive' }}
                                                                                @if ($marketOverride->price !== null)
                                                                                    · {{ strtoupper($marketOverride->currency ?: $product->currency) }} {{ number_format((float) $marketOverride->price, 2) }}
                                                                                @endif
                                                                                @if ($marketOverride->sale_price !== null)
                                                                                    · Sale {{ strtoupper($marketOverride->currency ?: $product->currency) }} {{ number_format((float) $marketOverride->sale_price, 2) }}
                                                                                @endif
                                                                            </div>

                                                                            <form method="POST" action="{{ route('settings.catalogs.products.market-overrides.update', $marketOverride) }}" class="ws-catalog-form">
                                                                                @csrf
                                                                                @method('PATCH')

                                                                                <div class="ws-catalog-form-grid">
                                                                                    <div class="ws-catalog-form-field">
                                                                                        <label class="ws-catalog-label" for="overrideCountry{{ $marketOverride->id }}">Target country</label>
                                                                                        <input id="overrideCountry{{ $marketOverride->id }}" name="target_country" class="ws-catalog-input" type="text" value="{{ $marketOverride->target_country }}" maxlength="2" required>
                                                                                    </div>

                                                                                    <div class="ws-catalog-form-field">
                                                                                        <label class="ws-catalog-label" for="overrideLanguage{{ $marketOverride->id }}">Content language</label>
                                                                                        <input id="overrideLanguage{{ $marketOverride->id }}" name="content_language" class="ws-catalog-input" type="text" value="{{ $marketOverride->content_language }}" required>
                                                                                    </div>

                                                                                    <div class="ws-catalog-form-field">
                                                                                        <label class="ws-catalog-label" for="overridePrice{{ $marketOverride->id }}">Price</label>
                                                                                        <input id="overridePrice{{ $marketOverride->id }}" name="price" class="ws-catalog-input" type="number" min="0" step="0.01" value="{{ $marketOverride->price }}">
                                                                                    </div>

                                                                                    <div class="ws-catalog-form-field">
                                                                                        <label class="ws-catalog-label" for="overrideSalePrice{{ $marketOverride->id }}">Sale price</label>
                                                                                        <input id="overrideSalePrice{{ $marketOverride->id }}" name="sale_price" class="ws-catalog-input" type="number" min="0" step="0.01" value="{{ $marketOverride->sale_price }}">
                                                                                    </div>

                                                                                    <div class="ws-catalog-form-field">
                                                                                        <label class="ws-catalog-label" for="overrideCurrency{{ $marketOverride->id }}">Currency</label>
                                                                                        <input id="overrideCurrency{{ $marketOverride->id }}" name="currency" class="ws-catalog-input" type="text" value="{{ strtoupper($marketOverride->currency ?: $product->currency) }}" maxlength="3">
                                                                                    </div>

                                                                                    <div class="ws-catalog-form-field">
                                                                                        <label class="ws-catalog-label" for="overrideStatus{{ $marketOverride->id }}">Status</label>
                                                                                        <select id="overrideStatus{{ $marketOverride->id }}" name="is_active" class="ws-catalog-select">
                                                                                            <option value="1" @selected($marketOverride->is_active)>Active</option>
                                                                                            <option value="0" @selected(! $marketOverride->is_active)>Inactive</option>
                                                                                        </select>
                                                                                    </div>

                                                                                    <div class="ws-catalog-form-field full">
                                                                                        <label class="ws-catalog-label" for="overrideTitle{{ $marketOverride->id }}">Localized title</label>
                                                                                        <input id="overrideTitle{{ $marketOverride->id }}" name="title" class="ws-catalog-input" type="text" value="{{ $marketOverride->title }}">
                                                                                    </div>

                                                                                    <div class="ws-catalog-form-field full">
                                                                                        <label class="ws-catalog-label" for="overrideDescription{{ $marketOverride->id }}">Localized description</label>
                                                                                        <textarea id="overrideDescription{{ $marketOverride->id }}" name="description" class="ws-catalog-textarea">{{ $marketOverride->description }}</textarea>
                                                                                    </div>

                                                                                    <div class="ws-catalog-form-field full">
                                                                                        <label class="ws-catalog-label" for="overrideProductUrl{{ $marketOverride->id }}">Localized product URL</label>
                                                                                        <input id="overrideProductUrl{{ $marketOverride->id }}" name="product_url" class="ws-catalog-input" type="url" value="{{ $marketOverride->product_url }}" placeholder="https://...">
                                                                                    </div>

                                                                                    <div class="ws-catalog-form-field full">
                                                                                        <label class="ws-catalog-label" for="overrideCheckoutUrl{{ $marketOverride->id }}">Checkout URL</label>
                                                                                        <input id="overrideCheckoutUrl{{ $marketOverride->id }}" name="checkout_url" class="ws-catalog-input" type="url" value="{{ $marketOverride->checkout_url }}" placeholder="https://...">
                                                                                    </div>

                                                                                    <div class="ws-catalog-form-field full">
                                                                                        <label class="ws-catalog-label" for="overrideCategory{{ $marketOverride->id }}">Localized category</label>
                                                                                        <input id="overrideCategory{{ $marketOverride->id }}" name="google_product_category" class="ws-catalog-input" type="text" value="{{ $marketOverride->google_product_category }}">
                                                                                    </div>
                                                                                </div>

                                                                                <div style="display:flex; gap:8px; flex-wrap:wrap;">
                                                                                    <button type="submit" class="ws-catalog-secondary">Save profile</button>
                                                                                </div>
                                                                            </form>

                                                                            <form method="POST" action="{{ route('settings.catalogs.products.market-overrides.delete', $marketOverride) }}">
                                                                                @csrf
                                                                                @method('DELETE')
                                                                                <button type="submit" class="ws-catalog-danger">Delete profile</button>
                                                                            </form>
                                                                        </div>
                                                                    @empty
                                                                        <div class="ws-product-empty">
                                                                            No localized market profiles yet.
                                                                        </div>
                                                                    @endforelse
                                                                </div>

                                                                <form method="POST" action="{{ route('settings.catalogs.products.market-overrides.store', $product) }}" class="ws-catalog-form" style="margin-top:14px;">
                                                                    @csrf

                                                                    <div class="ws-catalog-form-grid">
                                                                        <div class="ws-catalog-form-field">
                                                                            <label class="ws-catalog-label" for="newOverrideCountry{{ $product->id }}">Target country</label>
                                                                            <input id="newOverrideCountry{{ $product->id }}" name="target_country" class="ws-catalog-input" type="text" maxlength="2" placeholder="US" required>
                                                                        </div>

                                                                        <div class="ws-catalog-form-field">
                                                                            <label class="ws-catalog-label" for="newOverrideLanguage{{ $product->id }}">Content language</label>
                                                                            <input id="newOverrideLanguage{{ $product->id }}" name="content_language" class="ws-catalog-input" type="text" placeholder="en or fa_IR" required>
                                                                        </div>

                                                                        <div class="ws-catalog-form-field">
                                                                            <label class="ws-catalog-label" for="newOverridePrice{{ $product->id }}">Price</label>
                                                                            <input id="newOverridePrice{{ $product->id }}" name="price" class="ws-catalog-input" type="number" min="0" step="0.01">
                                                                        </div>

                                                                        <div class="ws-catalog-form-field">
                                                                            <label class="ws-catalog-label" for="newOverrideSalePrice{{ $product->id }}">Sale price</label>
                                                                            <input id="newOverrideSalePrice{{ $product->id }}" name="sale_price" class="ws-catalog-input" type="number" min="0" step="0.01">
                                                                        </div>

                                                                        <div class="ws-catalog-form-field">
                                                                            <label class="ws-catalog-label" for="newOverrideCurrency{{ $product->id }}">Currency</label>
                                                                            <input id="newOverrideCurrency{{ $product->id }}" name="currency" class="ws-catalog-input" type="text" value="{{ strtoupper($product->currency) }}" maxlength="3">
                                                                        </div>

                                                                        <div class="ws-catalog-form-field full">
                                                                            <label class="ws-catalog-label" for="newOverrideTitle{{ $product->id }}">Localized title</label>
                                                                            <input id="newOverrideTitle{{ $product->id }}" name="title" class="ws-catalog-input" type="text">
                                                                        </div>

                                                                        <div class="ws-catalog-form-field full">
                                                                            <label class="ws-catalog-label" for="newOverrideDescription{{ $product->id }}">Localized description</label>
                                                                            <textarea id="newOverrideDescription{{ $product->id }}" name="description" class="ws-catalog-textarea"></textarea>
                                                                        </div>

                                                                        <div class="ws-catalog-form-field full">
                                                                            <label class="ws-catalog-label" for="newOverrideProductUrl{{ $product->id }}">Localized product URL</label>
                                                                            <input id="newOverrideProductUrl{{ $product->id }}" name="product_url" class="ws-catalog-input" type="url" placeholder="https://...">
                                                                        </div>

                                                                        <div class="ws-catalog-form-field full">
                                                                            <label class="ws-catalog-label" for="newOverrideCheckoutUrl{{ $product->id }}">Checkout URL</label>
                                                                            <input id="newOverrideCheckoutUrl{{ $product->id }}" name="checkout_url" class="ws-catalog-input" type="url" placeholder="https://...">
                                                                        </div>

                                                                        <div class="ws-catalog-form-field full">
                                                                            <label class="ws-catalog-label" for="newOverrideCategory{{ $product->id }}">Localized category</label>
                                                                            <input id="newOverrideCategory{{ $product->id }}" name="google_product_category" class="ws-catalog-input" type="text">
                                                                        </div>
                                                                    </div>

                                                                    <div>
                                                                        <button type="submit" class="ws-catalog-submit">Add localized profile</button>
                                                                    </div>
                                                                </form>
                                                            </div>

                                                            <div class="ws-catalog-import" style="margin-top:14px;">
                                                                <h4 class="ws-product-title">Offers and promos</h4>
                                                                <div class="ws-catalog-help">
                                                                    Create reusable timed offers for the base product or attach them to one localized market profile.
                                                                </div>

                                                                <div style="display:grid; gap:12px; margin-top:12px;">
                                                                    @forelse ($product->offers as $offer)
                                                                        @php
                                                                            $offerTarget = $offer->marketOverride
                                                                                ? strtoupper($offer->marketOverride->target_country) . ' · ' . strtoupper(str_replace('_', '-', $offer->marketOverride->content_language))
                                                                                : 'Base product';
                                                                            $offerState = $offer->status;
                                                                            if ($offer->status === 'active' && $offer->starts_at && $offer->starts_at->gt($now)) {
                                                                                $offerState = 'scheduled';
                                                                            } elseif ($offer->status === 'active' && $offer->ends_at && $offer->ends_at->lt($now)) {
                                                                                $offerState = 'expired';
                                                                            }
                                                                        @endphp
                                                                        <div style="border:1px solid #e2e8f0; border-radius:8px; padding:12px; display:grid; gap:12px;">
                                                                            <div class="ws-product-meta">
                                                                                {{ $offer->name }}
                                                                                · {{ $offerTarget }}
                                                                                · {{ ucfirst($offerState) }}
                                                                                · {{ str_replace('_', ' ', $offer->discount_type) }}
                                                                                · {{ rtrim(rtrim(number_format((float) $offer->discount_value, 2, '.', ''), '0'), '.') }}
                                                                                @if ($offer->discount_type === 'percentage')
                                                                                    %
                                                                                @else
                                                                                    {{ strtoupper($offer->currency ?: $product->currency) }}
                                                                                @endif
                                                                            </div>

                                                                            <form method="POST" action="{{ route('settings.catalogs.products.offers.update', $offer) }}" class="ws-catalog-form">
                                                                                @csrf
                                                                                @method('PATCH')

                                                                                <div class="ws-catalog-form-grid">
                                                                                    <div class="ws-catalog-form-field">
                                                                                        <label class="ws-catalog-label" for="offerName{{ $offer->id }}">Offer name</label>
                                                                                        <input id="offerName{{ $offer->id }}" name="name" class="ws-catalog-input" type="text" value="{{ $offer->name }}" required>
                                                                                    </div>

                                                                                    <div class="ws-catalog-form-field">
                                                                                        <label class="ws-catalog-label" for="offerStatus{{ $offer->id }}">Status</label>
                                                                                        <select id="offerStatus{{ $offer->id }}" name="status" class="ws-catalog-select">
                                                                                            <option value="draft" @selected($offer->status === 'draft')>Draft</option>
                                                                                            <option value="active" @selected($offer->status === 'active')>Active</option>
                                                                                            <option value="paused" @selected($offer->status === 'paused')>Paused</option>
                                                                                        </select>
                                                                                    </div>

                                                                                    <div class="ws-catalog-form-field">
                                                                                        <label class="ws-catalog-label" for="offerTarget{{ $offer->id }}">Target</label>
                                                                                        <select id="offerTarget{{ $offer->id }}" name="market_override_id" class="ws-catalog-select">
                                                                                            <option value="">Base product</option>
                                                                                            @foreach ($product->marketOverrides as $marketOverrideOption)
                                                                                                <option value="{{ $marketOverrideOption->id }}" @selected($offer->catalog_product_market_override_id === $marketOverrideOption->id)>
                                                                                                    {{ strtoupper($marketOverrideOption->target_country) }} · {{ strtoupper(str_replace('_', '-', $marketOverrideOption->content_language)) }}
                                                                                                </option>
                                                                                            @endforeach
                                                                                        </select>
                                                                                    </div>

                                                                                    <div class="ws-catalog-form-field">
                                                                                        <label class="ws-catalog-label" for="offerDiscountType{{ $offer->id }}">Discount type</label>
                                                                                        <select id="offerDiscountType{{ $offer->id }}" name="discount_type" class="ws-catalog-select">
                                                                                            <option value="percentage" @selected($offer->discount_type === 'percentage')>Percentage</option>
                                                                                            <option value="fixed_amount" @selected($offer->discount_type === 'fixed_amount')>Fixed amount</option>
                                                                                            <option value="price_override" @selected($offer->discount_type === 'price_override')>Price override</option>
                                                                                        </select>
                                                                                    </div>

                                                                                    <div class="ws-catalog-form-field">
                                                                                        <label class="ws-catalog-label" for="offerDiscountValue{{ $offer->id }}">Discount value</label>
                                                                                        <input id="offerDiscountValue{{ $offer->id }}" name="discount_value" class="ws-catalog-input" type="number" min="0" step="0.01" value="{{ $offer->discount_value }}" required>
                                                                                    </div>

                                                                                    <div class="ws-catalog-form-field">
                                                                                        <label class="ws-catalog-label" for="offerCurrency{{ $offer->id }}">Currency</label>
                                                                                        <input id="offerCurrency{{ $offer->id }}" name="currency" class="ws-catalog-input" type="text" value="{{ strtoupper($offer->currency ?: $product->currency) }}" maxlength="3">
                                                                                    </div>

                                                                                    <div class="ws-catalog-form-field">
                                                                                        <label class="ws-catalog-label" for="offerPriority{{ $offer->id }}">Priority</label>
                                                                                        <input id="offerPriority{{ $offer->id }}" name="priority" class="ws-catalog-input" type="number" min="1" max="999" step="1" value="{{ $offer->priority }}">
                                                                                    </div>

                                                                                    <div class="ws-catalog-form-field">
                                                                                        <label class="ws-catalog-label" for="offerStarts{{ $offer->id }}">Starts at</label>
                                                                                        <input id="offerStarts{{ $offer->id }}" name="starts_at" class="ws-catalog-input" type="datetime-local" value="{{ optional($offer->starts_at)->format('Y-m-d\TH:i') }}">
                                                                                    </div>

                                                                                    <div class="ws-catalog-form-field">
                                                                                        <label class="ws-catalog-label" for="offerEnds{{ $offer->id }}">Ends at</label>
                                                                                        <input id="offerEnds{{ $offer->id }}" name="ends_at" class="ws-catalog-input" type="datetime-local" value="{{ optional($offer->ends_at)->format('Y-m-d\TH:i') }}">
                                                                                    </div>

                                                                                    <div class="ws-catalog-form-field full">
                                                                                        <label class="ws-catalog-label" for="offerCheckout{{ $offer->id }}">Offer checkout URL</label>
                                                                                        <input id="offerCheckout{{ $offer->id }}" name="checkout_url" class="ws-catalog-input" type="url" value="{{ $offer->checkout_url }}" placeholder="https://...">
                                                                                    </div>
                                                                                </div>

                                                                                <div style="display:flex; gap:8px; flex-wrap:wrap;">
                                                                                    <button type="submit" class="ws-catalog-secondary">Save offer</button>
                                                                                </div>
                                                                            </form>

                                                                            <form method="POST" action="{{ route('settings.catalogs.products.offers.delete', $offer) }}">
                                                                                @csrf
                                                                                @method('DELETE')
                                                                                <button type="submit" class="ws-catalog-danger">Delete offer</button>
                                                                            </form>
                                                                        </div>
                                                                    @empty
                                                                        <div class="ws-product-empty">
                                                                            No offers yet.
                                                                        </div>
                                                                    @endforelse
                                                                </div>

                                                                <form method="POST" action="{{ route('settings.catalogs.products.offers.store', $product) }}" class="ws-catalog-form" style="margin-top:14px;">
                                                                    @csrf

                                                                    <div class="ws-catalog-form-grid">
                                                                        <div class="ws-catalog-form-field">
                                                                            <label class="ws-catalog-label" for="newOfferName{{ $product->id }}">Offer name</label>
                                                                            <input id="newOfferName{{ $product->id }}" name="name" class="ws-catalog-input" type="text" placeholder="Spring launch promo" required>
                                                                        </div>

                                                                        <div class="ws-catalog-form-field">
                                                                            <label class="ws-catalog-label" for="newOfferStatus{{ $product->id }}">Status</label>
                                                                            <select id="newOfferStatus{{ $product->id }}" name="status" class="ws-catalog-select">
                                                                                <option value="draft">Draft</option>
                                                                                <option value="active">Active</option>
                                                                                <option value="paused">Paused</option>
                                                                            </select>
                                                                        </div>

                                                                        <div class="ws-catalog-form-field">
                                                                            <label class="ws-catalog-label" for="newOfferTarget{{ $product->id }}">Target</label>
                                                                            <select id="newOfferTarget{{ $product->id }}" name="market_override_id" class="ws-catalog-select">
                                                                                <option value="">Base product</option>
                                                                                @foreach ($product->marketOverrides as $marketOverrideOption)
                                                                                    <option value="{{ $marketOverrideOption->id }}">
                                                                                        {{ strtoupper($marketOverrideOption->target_country) }} · {{ strtoupper(str_replace('_', '-', $marketOverrideOption->content_language)) }}
                                                                                    </option>
                                                                                @endforeach
                                                                            </select>
                                                                        </div>

                                                                        <div class="ws-catalog-form-field">
                                                                            <label class="ws-catalog-label" for="newOfferDiscountType{{ $product->id }}">Discount type</label>
                                                                            <select id="newOfferDiscountType{{ $product->id }}" name="discount_type" class="ws-catalog-select">
                                                                                <option value="percentage">Percentage</option>
                                                                                <option value="fixed_amount">Fixed amount</option>
                                                                                <option value="price_override">Price override</option>
                                                                            </select>
                                                                        </div>

                                                                        <div class="ws-catalog-form-field">
                                                                            <label class="ws-catalog-label" for="newOfferDiscountValue{{ $product->id }}">Discount value</label>
                                                                            <input id="newOfferDiscountValue{{ $product->id }}" name="discount_value" class="ws-catalog-input" type="number" min="0" step="0.01" required>
                                                                        </div>

                                                                        <div class="ws-catalog-form-field">
                                                                            <label class="ws-catalog-label" for="newOfferCurrency{{ $product->id }}">Currency</label>
                                                                            <input id="newOfferCurrency{{ $product->id }}" name="currency" class="ws-catalog-input" type="text" value="{{ strtoupper($product->currency) }}" maxlength="3">
                                                                        </div>

                                                                        <div class="ws-catalog-form-field">
                                                                            <label class="ws-catalog-label" for="newOfferPriority{{ $product->id }}">Priority</label>
                                                                            <input id="newOfferPriority{{ $product->id }}" name="priority" class="ws-catalog-input" type="number" min="1" max="999" step="1" value="100">
                                                                        </div>

                                                                        <div class="ws-catalog-form-field">
                                                                            <label class="ws-catalog-label" for="newOfferStarts{{ $product->id }}">Starts at</label>
                                                                            <input id="newOfferStarts{{ $product->id }}" name="starts_at" class="ws-catalog-input" type="datetime-local">
                                                                        </div>

                                                                        <div class="ws-catalog-form-field">
                                                                            <label class="ws-catalog-label" for="newOfferEnds{{ $product->id }}">Ends at</label>
                                                                            <input id="newOfferEnds{{ $product->id }}" name="ends_at" class="ws-catalog-input" type="datetime-local">
                                                                        </div>

                                                                        <div class="ws-catalog-form-field full">
                                                                            <label class="ws-catalog-label" for="newOfferCheckout{{ $product->id }}">Offer checkout URL</label>
                                                                            <input id="newOfferCheckout{{ $product->id }}" name="checkout_url" class="ws-catalog-input" type="url" placeholder="https://...">
                                                                        </div>
                                                                    </div>

                                                                    <div>
                                                                        <button type="submit" class="ws-catalog-submit">Add offer</button>
                                                                    </div>
                                                                </form>
                                                            </div>
                                                        </details>
                                                    </div>

                                                    <div class="ws-product-actions">
                                                        <form method="POST" action="{{ route('settings.catalogs.products.status', $product) }}">
                                                            @csrf
                                                            @method('PATCH')
                                                            <button type="submit" class="ws-catalog-secondary">
                                                                {{ $product->is_active ? 'Disable' : 'Enable' }}
                                                            </button>
                                                        </form>

                                                        <form method="POST" action="{{ route('settings.catalogs.products.delete', $product) }}">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" class="ws-catalog-danger">Delete</button>
                                                        </form>
                                                    </div>
                                                </div>
                                            @empty
                                                <div class="ws-product-empty">
                                                    No products yet. Add the first product below.
                                                </div>
                                            @endforelse

                                            <div class="ws-catalog-import">
                                                <h4 class="ws-product-title">Bulk import products</h4>
                                                <div class="ws-catalog-help">
                                                    Upload a CSV with columns like title, sku, description, price, sale_price, currency, brand, product_condition, inventory_quantity, image_url, product_url, google_product_category, content_language, target_country, availability, is_active. Existing SKUs will be updated.
                                                </div>
                                                <div class="ws-catalog-help">
                                                    Extra CSV columns are preserved as product metadata for later commerce mapping.
                                                </div>

                                                <form method="POST" action="{{ route('settings.catalogs.products.import', $catalog) }}" enctype="multipart/form-data" class="ws-catalog-form">
                                                    @csrf

                                                    <div class="ws-catalog-form-grid">
                                                        <div class="ws-catalog-form-field full">
                                                            <label class="ws-catalog-label" for="productImport{{ $catalog->id }}">CSV file</label>
                                                            <input id="productImport{{ $catalog->id }}" name="products_csv" class="ws-catalog-input" type="file" accept=".csv,text/csv,text/plain" required>
                                                        </div>
                                                    </div>

                                                    <div>
                                                        <button type="submit" class="ws-catalog-secondary">Import CSV</button>
                                                    </div>
                                                </form>
                                            </div>

                                            <form method="POST" action="{{ route('settings.catalogs.products.store', $catalog) }}" class="ws-catalog-form">
                                                @csrf

                                                <div class="ws-catalog-form-grid">
                                                    <div class="ws-catalog-form-field">
                                                        <label class="ws-catalog-label" for="productTitle{{ $catalog->id }}">Product title</label>
                                                        <input id="productTitle{{ $catalog->id }}" name="title" class="ws-catalog-input" type="text" required>
                                                    </div>

                                                    <div class="ws-catalog-form-field">
                                                        <label class="ws-catalog-label" for="productSku{{ $catalog->id }}">SKU</label>
                                                        <input id="productSku{{ $catalog->id }}" name="sku" class="ws-catalog-input" type="text">
                                                    </div>

                                                    <div class="ws-catalog-form-field">
                                                        <label class="ws-catalog-label" for="productAvailability{{ $catalog->id }}">Availability</label>
                                                        <select id="productAvailability{{ $catalog->id }}" name="availability" class="ws-catalog-select">
                                                            <option value="in_stock">In stock</option>
                                                            <option value="out_of_stock">Out of stock</option>
                                                            <option value="preorder">Preorder</option>
                                                        </select>
                                                    </div>

                                                    <div class="ws-catalog-form-field">
                                                        <label class="ws-catalog-label" for="productPrice{{ $catalog->id }}">Price</label>
                                                        <input id="productPrice{{ $catalog->id }}" name="price" class="ws-catalog-input" type="number" min="0" step="0.01">
                                                    </div>

                                                    <div class="ws-catalog-form-field">
                                                        <label class="ws-catalog-label" for="productSalePrice{{ $catalog->id }}">Sale price</label>
                                                        <input id="productSalePrice{{ $catalog->id }}" name="sale_price" class="ws-catalog-input" type="number" min="0" step="0.01">
                                                    </div>

                                                    <div class="ws-catalog-form-field">
                                                        <label class="ws-catalog-label" for="productCurrency{{ $catalog->id }}">Currency</label>
                                                        <input id="productCurrency{{ $catalog->id }}" name="currency" class="ws-catalog-input" type="text" value="USD" maxlength="3" required>
                                                    </div>

                                                    <div class="ws-catalog-form-field">
                                                        <label class="ws-catalog-label" for="productBrand{{ $catalog->id }}">Brand</label>
                                                        <input id="productBrand{{ $catalog->id }}" name="brand" class="ws-catalog-input" type="text" placeholder="Leadochat">
                                                    </div>

                                                    <div class="ws-catalog-form-field">
                                                        <label class="ws-catalog-label" for="productCondition{{ $catalog->id }}">Condition</label>
                                                        <select id="productCondition{{ $catalog->id }}" name="product_condition" class="ws-catalog-select">
                                                            <option value="new">New</option>
                                                            <option value="refurbished">Refurbished</option>
                                                            <option value="used">Used</option>
                                                        </select>
                                                    </div>

                                                    <div class="ws-catalog-form-field">
                                                        <label class="ws-catalog-label" for="productInventory{{ $catalog->id }}">Inventory</label>
                                                        <input id="productInventory{{ $catalog->id }}" name="inventory_quantity" class="ws-catalog-input" type="number" min="0" step="1" placeholder="25">
                                                    </div>

                                                    <div class="ws-catalog-form-field">
                                                        <label class="ws-catalog-label" for="productLanguage{{ $catalog->id }}">Content language</label>
                                                        <input id="productLanguage{{ $catalog->id }}" name="content_language" class="ws-catalog-input" type="text" placeholder="en or fa_IR">
                                                    </div>

                                                    <div class="ws-catalog-form-field">
                                                        <label class="ws-catalog-label" for="productCountry{{ $catalog->id }}">Target country</label>
                                                        <input id="productCountry{{ $catalog->id }}" name="target_country" class="ws-catalog-input" type="text" maxlength="2" placeholder="US">
                                                    </div>

                                                    <div class="ws-catalog-form-field">
                                                        <label class="ws-catalog-label" for="productUrl{{ $catalog->id }}">Product URL</label>
                                                        <input id="productUrl{{ $catalog->id }}" name="product_url" class="ws-catalog-input" type="url" placeholder="https://...">
                                                    </div>

                                                    <div class="ws-catalog-form-field full">
                                                        <label class="ws-catalog-label" for="productImage{{ $catalog->id }}">Image URL</label>
                                                        <input id="productImage{{ $catalog->id }}" name="image_url" class="ws-catalog-input" type="url" placeholder="https://...">
                                                    </div>

                                                    <div class="ws-catalog-form-field full">
                                                        <label class="ws-catalog-label" for="productDescription{{ $catalog->id }}">Description</label>
                                                        <textarea id="productDescription{{ $catalog->id }}" name="description" class="ws-catalog-textarea" placeholder="Short product description for agents and DM context."></textarea>
                                                    </div>

                                                    <div class="ws-catalog-form-field full">
                                                        <label class="ws-catalog-label" for="productCategory{{ $catalog->id }}">Google product category</label>
                                                        <input id="productCategory{{ $catalog->id }}" name="google_product_category" class="ws-catalog-input" type="text" placeholder="Books > Education">
                                                    </div>

                                                    <div class="ws-catalog-form-field">
                                                        <label class="ws-catalog-label" for="productSaleStart{{ $catalog->id }}">Sale starts</label>
                                                        <input id="productSaleStart{{ $catalog->id }}" name="sale_price_effective_start_at" class="ws-catalog-input" type="datetime-local">
                                                    </div>

                                                    <div class="ws-catalog-form-field">
                                                        <label class="ws-catalog-label" for="productSaleEnd{{ $catalog->id }}">Sale ends</label>
                                                        <input id="productSaleEnd{{ $catalog->id }}" name="sale_price_effective_end_at" class="ws-catalog-input" type="datetime-local">
                                                    </div>
                                                </div>

                                                <div>
                                                    <button type="submit" class="ws-catalog-submit">Add product</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                @empty
                                    <div class="ws-empty">
                                        No catalogs created yet.
                                    </div>
                                @endforelse
                            </div>
                        </div>
                    </div>
                @elseif ($section === 'commerce')
                    <style>
                        .ws-commerce-shell {
                            margin-top: 18px;
                            display: grid;
                            gap: 14px;
                        }

                        .ws-commerce-grid {
                            display: grid;
                            grid-template-columns: repeat(2, minmax(0, 1fr));
                            gap: 14px;
                        }

                        .ws-commerce-card {
                            border: 1px solid #e2e8f0;
                            border-radius: 8px;
                            background: #fff;
                            padding: 14px;
                        }

                        .ws-commerce-account {
                            display: grid;
                            gap: 10px;
                            border: 1px solid #e2e8f0;
                            border-radius: 8px;
                            padding: 12px;
                            background: #fff;
                        }

                        .ws-commerce-head {
                            display: flex;
                            justify-content: space-between;
                            gap: 12px;
                            align-items: flex-start;
                        }

                        .ws-commerce-title {
                            font-size: 15px;
                            font-weight: 900;
                            color: #0f172a;
                        }

                        .ws-commerce-meta,
                        .ws-commerce-check {
                            color: #64748b;
                            font-size: 12px;
                            line-height: 1.55;
                        }

                        .ws-commerce-pill {
                            display: inline-flex;
                            align-items: center;
                            min-height: 24px;
                            border-radius: 999px;
                            padding: 0 9px;
                            background: #f8fafc;
                            border: 1px solid #cbd5e1;
                            color: #334155;
                            font-size: 11px;
                            font-weight: 900;
                            white-space: nowrap;
                        }

                        .ws-commerce-pill.ok {
                            background: #ecfdf5;
                            border-color: #86efac;
                            color: #166534;
                        }

                        .ws-commerce-pill.fail {
                            background: #fef2f2;
                            border-color: #fecaca;
                            color: #b91c1c;
                        }

                        .ws-commerce-button {
                            display: inline-flex;
                            align-items: center;
                            justify-content: center;
                            min-height: 38px;
                            border-radius: 8px;
                            padding: 0 13px;
                            border: 0;
                            background: #0f766e;
                            color: #fff;
                            cursor: pointer;
                            font-size: 13px;
                            font-weight: 900;
                        }

                        .ws-commerce-select {
                            min-height: 38px;
                            border: 1px solid #cbd5e1;
                            border-radius: 8px;
                            background: #fff;
                            color: #0f172a;
                            padding: 0 10px;
                            font-size: 13px;
                            outline: none;
                        }

                        .ws-commerce-input,
                        .ws-commerce-textarea {
                            width: 100%;
                            min-height: 38px;
                            border: 1px solid #cbd5e1;
                            border-radius: 8px;
                            background: #fff;
                            color: #0f172a;
                            padding: 0 10px;
                            font-size: 13px;
                            outline: none;
                            box-sizing: border-box;
                        }

                        .ws-commerce-textarea {
                            min-height: 84px;
                            padding: 10px;
                            resize: vertical;
                        }

                        .ws-commerce-select.is-multi {
                            min-height: 132px;
                            padding: 8px 10px;
                        }

                        .ws-commerce-sync-form {
                            display: flex;
                            flex-wrap: wrap;
                            align-items: center;
                            gap: 8px;
                            margin-top: 10px;
                        }

                        .ws-commerce-list {
                            display: grid;
                            gap: 8px;
                            margin-top: 10px;
                        }

                        .ws-commerce-catalog {
                            border: 1px solid #e2e8f0;
                            border-radius: 8px;
                            padding: 10px;
                            background: #f8fafc;
                        }

                        .ws-commerce-summary {
                            display: grid;
                            gap: 10px;
                            border: 1px solid #dbeafe;
                            border-radius: 8px;
                            background: #f8fbff;
                            padding: 12px;
                        }

                        .ws-commerce-summary-head {
                            display: flex;
                            justify-content: space-between;
                            gap: 12px;
                            align-items: flex-start;
                            flex-wrap: wrap;
                        }

                        .ws-commerce-summary-copy {
                            display: grid;
                            gap: 6px;
                        }

                        .ws-commerce-summary-title {
                            font-size: 14px;
                            font-weight: 900;
                            color: #0f172a;
                        }

                        .ws-commerce-summary-text {
                            color: #475569;
                            font-size: 12px;
                            line-height: 1.6;
                        }

                        .ws-commerce-metric-row,
                        .ws-commerce-evidence-grid,
                        .ws-commerce-action-list {
                            display: grid;
                            gap: 8px;
                        }

                        .ws-commerce-metric-row {
                            grid-template-columns: repeat(4, minmax(0, 1fr));
                        }

                        .ws-commerce-metric {
                            border: 1px solid #e2e8f0;
                            border-radius: 8px;
                            background: #fff;
                            padding: 10px;
                        }

                        .ws-commerce-metric-label {
                            font-size: 11px;
                            font-weight: 800;
                            color: #64748b;
                            text-transform: uppercase;
                        }

                        .ws-commerce-metric-value {
                            margin-top: 6px;
                            font-size: 18px;
                            font-weight: 900;
                            color: #0f172a;
                        }

                        .ws-commerce-evidence-grid {
                            grid-template-columns: repeat(2, minmax(0, 1fr));
                        }

                        .ws-commerce-evidence-item {
                            border: 1px solid #e2e8f0;
                            border-radius: 8px;
                            background: #fff;
                            padding: 10px;
                            display: grid;
                            gap: 8px;
                        }

                        .ws-commerce-evidence-label {
                            font-size: 13px;
                            font-weight: 800;
                            color: #0f172a;
                        }

                        .ws-commerce-action-item {
                            border: 1px solid #e2e8f0;
                            border-radius: 8px;
                            background: #fff;
                            padding: 10px;
                        }

                        .ws-commerce-action-title {
                            font-size: 13px;
                            font-weight: 800;
                            color: #0f172a;
                        }

                        .ws-commerce-details {
                            border: 1px solid #e2e8f0;
                            border-radius: 8px;
                            background: #fff;
                            padding: 10px;
                        }

                        .ws-commerce-details summary {
                            cursor: pointer;
                            font-size: 13px;
                            font-weight: 800;
                            color: #0f172a;
                            list-style: none;
                        }

                        .ws-commerce-details summary::-webkit-details-marker {
                            display: none;
                        }

                        .ws-commerce-packet-grid {
                            display: grid;
                            grid-template-columns: repeat(2, minmax(0, 1fr));
                            gap: 8px;
                        }

                        .ws-commerce-step-list,
                        .ws-commerce-history-list {
                            display: grid;
                            gap: 8px;
                        }

                        .ws-commerce-step,
                        .ws-commerce-history-item {
                            border: 1px solid #e2e8f0;
                            border-radius: 8px;
                            background: #fff;
                            padding: 10px;
                        }

                        .ws-commerce-step-title,
                        .ws-commerce-history-title {
                            font-size: 13px;
                            font-weight: 800;
                            color: #0f172a;
                        }

                        .ws-commerce-order-list {
                            display: grid;
                            gap: 10px;
                        }

                        .ws-commerce-order-item {
                            border: 1px solid #e2e8f0;
                            border-radius: 8px;
                            background: #fff;
                            padding: 12px;
                            display: grid;
                            gap: 10px;
                        }

                        .ws-commerce-order-meta-row {
                            display: flex;
                            flex-wrap: wrap;
                            gap: 6px;
                        }

                        .ws-commerce-code {
                            display: block;
                            margin-top: 6px;
                            color: #475569;
                            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
                            font-size: 11px;
                            overflow-wrap: anywhere;
                        }

                        .ws-commerce-product-set-products {
                            display: flex;
                            flex-wrap: wrap;
                            gap: 6px;
                            margin-top: 8px;
                        }

                        @media (max-width: 1080px) {
                            .ws-commerce-grid {
                                grid-template-columns: 1fr;
                            }

                            .ws-commerce-evidence-grid,
                            .ws-commerce-metric-row,
                            .ws-commerce-packet-grid {
                                grid-template-columns: 1fr;
                            }
                        }
                    </style>

                    <div class="ws-commerce-shell">
                        @if (session('status'))
                            <div class="ws-catalog-success">
                                {{ session('status') }}
                            </div>
                        @endif

                        <div class="ws-commerce-grid">
                            <div class="ws-commerce-card">
                                <h3 class="ws-section-title">App Review readiness</h3>
                                <div class="ws-section-subtitle">
                                    The current submission is limited to the five implemented Instagram Login permissions.
                                </div>
                                <div class="ws-commerce-list">
                                    @foreach (array_filter(array_map('trim', explode(',', (string) config('services.instagram.scopes', '')))) as $scope)
                                        <span class="ws-commerce-pill">{{ $scope }}</span>
                                    @endforeach
                                </div>
                                <div class="ws-commerce-meta" style="margin-top:10px;">
                                    Commerce, catalog, tagging, and ads permissions are not part of the current Instagram App Review submission.
                                </div>
                            </div>

                            <div class="ws-commerce-card">
                                <h3 class="ws-section-title">Commerce groundwork</h3>
                                <div class="ws-section-subtitle">
                                    Local previews and records are product groundwork only. They do not prove a Meta ads, catalog, or product-tagging permission.
                                </div>
                                <div class="ws-commerce-list">
                                    <span class="ws-commerce-pill">Local collection-ad preview</span>
                                    <span class="ws-commerce-pill">Local promoted-post preview</span>
                                    <span class="ws-commerce-pill">Local campaign records</span>
                                </div>
                                <div class="ws-commerce-meta" style="margin-top:10px;">Not part of the current Instagram App Review submission.</div>
                            </div>

                            <div class="ws-commerce-card">
                                <h3 class="ws-section-title">Evidence packet tools</h3>
                                <div class="ws-section-subtitle">
                                    Generate a redacted summary of available test evidence. Live provider calls and grants must still be verified separately.
                                </div>
                                <div class="ws-commerce-list">
                                    <span class="ws-commerce-pill">Inbox summary</span>
                                    <span class="ws-commerce-pill">Social summary</span>
                                    <span class="ws-commerce-pill">Webhook summary</span>
                                    <span class="ws-commerce-pill">Downloadable JSON</span>
                                </div>
                                <div class="ws-commerce-meta" style="margin-top:10px;">Evidence tools available; provider proof still required.</div>
                            </div>
                        </div>

                        <div class="ws-commerce-card">
                            <h3 class="ws-section-title">Connected Instagram accounts</h3>

                            <div class="ws-commerce-list">
                                @forelse (($commerceConnections ?? collect()) as $connection)
                                    @php
                                        $connectionMeta = is_array($connection->meta ?? null) ? $connection->meta : [];
                                        $commerceMeta = $connectionMeta['meta_commerce'] ?? [];
                                        $discovery = $connectionMeta['meta_commerce_discovery'] ?? [];
                                        $diagnostics = $connectionMeta['meta_commerce_diagnostics'] ?? [];
                                        $checks = is_array($discovery['checks'] ?? null) ? $discovery['checks'] : [];
                                        $readinessChecks = is_array($diagnostics['readiness'] ?? null) ? $diagnostics['readiness'] : [];
                                        $diagnosticAccount = is_array($diagnostics['account'] ?? null) ? $diagnostics['account'] : [];
                                        $diagnosticPermissions = is_array($diagnostics['permissions'] ?? null) ? $diagnostics['permissions'] : [];
                                        $diagnosticChannel = is_array($diagnostics['channel'] ?? null) ? $diagnostics['channel'] : [];
                                        $diagnosticWebhook = is_array($diagnostics['webhook'] ?? null) ? $diagnostics['webhook'] : [];
                                        $diagnosticShop = is_array($diagnostics['shop'] ?? null) ? $diagnostics['shop'] : [];
                                        $diagnosticCheckout = is_array($diagnostics['checkout_urls'] ?? null) ? $diagnostics['checkout_urls'] : [];
                                        $diagnosticReview = is_array($diagnostics['review'] ?? null) ? $diagnostics['review'] : [];
                                        $reviewPacket = is_array($connectionMeta['meta_commerce_review_packet'] ?? null) ? $connectionMeta['meta_commerce_review_packet'] : [];
                                        $reviewPacketSummary = is_array($reviewPacket['summary'] ?? null) ? $reviewPacket['summary'] : [];
                                        $reviewPacketAccount = is_array($reviewPacket['account'] ?? null) ? $reviewPacket['account'] : [];
                                        $reviewPacketChannel = is_array($reviewPacket['channel'] ?? null) ? $reviewPacket['channel'] : [];
                                        $reviewPacketCatalogs = is_array($reviewPacket['catalogs'] ?? null) ? $reviewPacket['catalogs'] : [];
                                        $reviewPacketLocalShop = is_array($reviewPacket['local_shop'] ?? null) ? $reviewPacket['local_shop'] : [];
                                        $reviewPacketCheckout = is_array($reviewPacket['checkout'] ?? null) ? $reviewPacket['checkout'] : [];
                                        $reviewPacketOrders = is_array($reviewPacket['orders'] ?? null) ? $reviewPacket['orders'] : [];
                                        $reviewPacketPromotions = is_array($reviewPacket['promotions'] ?? null) ? $reviewPacket['promotions'] : [];
                                        $reviewPacketEvidence = is_array($reviewPacket['review_evidence'] ?? null) ? $reviewPacket['review_evidence'] : [];
                                        $reviewPacketNotes = is_array($reviewPacket['review_notes'] ?? null) ? $reviewPacket['review_notes'] : [];
                                        $reviewPacketSteps = is_array($reviewPacket['demo_script'] ?? null) ? $reviewPacket['demo_script'] : [];
                                        $reviewPacketHistory = is_array($connectionMeta['meta_commerce_review_packet_history'] ?? null) ? $connectionMeta['meta_commerce_review_packet_history'] : [];
                                        $appReviewEvidencePacket = is_array($connectionMeta['meta_app_review_evidence'] ?? null) ? $connectionMeta['meta_app_review_evidence'] : [];
                                        $appReviewSummary = is_array($appReviewEvidencePacket['summary'] ?? null) ? $appReviewEvidencePacket['summary'] : [];
                                        $appReviewCoverage = is_array($appReviewEvidencePacket['coverage'] ?? null) ? $appReviewEvidencePacket['coverage'] : [];
                                        $appReviewCoverageInbox = is_array($appReviewCoverage['inbox'] ?? null) ? $appReviewCoverage['inbox'] : [];
                                        $appReviewCoverageSocial = is_array($appReviewCoverage['social'] ?? null) ? $appReviewCoverage['social'] : [];
                                        $appReviewCoverageWebhooks = is_array($appReviewCoverage['webhooks'] ?? null) ? $appReviewCoverage['webhooks'] : [];
                                        $appReviewEvidence = is_array($appReviewEvidencePacket['evidence'] ?? null) ? $appReviewEvidencePacket['evidence'] : [];
                                        $appReviewSteps = is_array($appReviewEvidencePacket['demo_script'] ?? null) ? $appReviewEvidencePacket['demo_script'] : [];
                                        $appReviewHistory = is_array($connectionMeta['meta_app_review_evidence_history'] ?? null) ? $connectionMeta['meta_app_review_evidence_history'] : [];
                                    @endphp

                                    <div class="ws-commerce-account">
                                        <div class="ws-commerce-head">
                                            <div>
                                                <div class="ws-commerce-title">
                                                    {{ $connection->provider_account_name ?: $connection->provider_account_id }}
                                                </div>
                                                <div class="ws-commerce-meta">
                                                    Instagram account ID: {{ $connection->provider_account_id }}
                                                    @if (!empty($commerceMeta['last_discovered_at']))
                                                        · Last discovery: {{ $commerceMeta['last_discovered_at'] }}
                                                    @endif
                                                </div>
                                            </div>

                                            <div style="display:flex; gap:8px; flex-wrap:wrap;">
                                                @if (filled(config('services.meta.commerce_review_scopes')))
                                                    <form method="POST" action="{{ route('settings.commerce.sync', $connection) }}">
                                                        @csrf
                                                        <button type="submit" class="ws-commerce-button">Run discovery</button>
                                                    </form>

                                                    <form method="POST" action="{{ route('settings.commerce.diagnostics', $connection) }}">
                                                        @csrf
                                                        <button type="submit" class="ws-commerce-button" style="background:#1d4ed8;">Run diagnostics</button>
                                                    </form>

                                                    <form method="POST" action="{{ route('settings.commerce.review-packet.generate', $connection) }}">
                                                        @csrf
                                                        <button type="submit" class="ws-commerce-button" style="background:#7c3aed;">Build commerce groundwork packet</button>
                                                    </form>
                                                @endif

                                                <form method="POST" action="{{ route('settings.commerce.app-review-evidence.generate', $connection) }}">
                                                    @csrf
                                                    <button type="submit" class="ws-commerce-button" style="background:#059669;">Build Instagram review evidence</button>
                                                </form>

                                                @if ($reviewPacket !== [] && filled(config('services.meta.commerce_review_scopes')))
                                                    <a href="{{ route('settings.commerce.review-packet.download', $connection) }}" class="ws-commerce-button" style="background:#334155; text-decoration:none;">
                                                        Download packet
                                                    </a>
                                                @endif

                                                @if ($appReviewEvidencePacket !== [])
                                                    <a href="{{ route('settings.commerce.app-review-evidence.download', $connection) }}" class="ws-commerce-button" style="background:#0f766e; text-decoration:none;">
                                                        Download evidence
                                                    </a>
                                                @endif
                                            </div>
                                        </div>

                                        <div class="ws-commerce-list">
                                            <div>
                                                <span class="ws-commerce-pill {{ (int) ($commerceMeta['catalog_count'] ?? 0) > 0 ? 'ok' : '' }}">
                                                    {{ (int) ($commerceMeta['catalog_count'] ?? 0) }} Meta catalogs
                                                </span>
                                                @if (!empty($commerceMeta['last_diagnostics_at']))
                                                    <span class="ws-commerce-pill {{ !empty($diagnostics['ok']) ? 'ok' : 'fail' }}">
                                                        Diagnostics {{ !empty($diagnostics['ok']) ? 'ready' : 'needs work' }}
                                                    </span>
                                                @endif
                                            </div>

                                            @if (!empty($commerceMeta['business_ids']))
                                                <div class="ws-commerce-meta">
                                                    Business IDs:
                                                    <span class="ws-commerce-code">{{ implode(', ', (array) $commerceMeta['business_ids']) }}</span>
                                                </div>
                                            @endif

                                            @if (!empty($commerceMeta['last_diagnostics_at']))
                                                <div class="ws-commerce-meta">
                                                    Last diagnostics: {{ $commerceMeta['last_diagnostics_at'] }}
                                                </div>
                                            @endif

                                            @if ($diagnosticAccount !== [])
                                                <div class="ws-commerce-meta">
                                                    Account:
                                                    {{ $diagnosticAccount['username'] ?? ($connection->provider_account_name ?: $connection->provider_account_id) }}
                                                    @if (!empty($diagnosticAccount['shopping_review_status']))
                                                        · Review: {{ $diagnosticAccount['shopping_review_status'] }}
                                                    @endif
                                                    @if (array_key_exists('shopping_product_tag_eligibility', $diagnosticAccount))
                                                        · Product tags: {{ !empty($diagnosticAccount['shopping_product_tag_eligibility']) ? 'eligible' : 'not eligible yet' }}
                                                    @endif
                                                </div>
                                            @endif

                                            @if ($diagnosticReview !== [])
                                                @php
                                                    $reviewStatusClass = match ($diagnosticReview['status'] ?? 'needs_attention') {
                                                        'ready' => 'ok',
                                                        'blocked' => 'fail',
                                                        default => '',
                                                    };
                                                    $reviewCounts = is_array($diagnosticReview['counts'] ?? null) ? $diagnosticReview['counts'] : [];
                                                    $reviewEvidence = is_array($diagnosticReview['evidence'] ?? null) ? $diagnosticReview['evidence'] : [];
                                                    $reviewActions = is_array($diagnosticReview['next_actions'] ?? null) ? $diagnosticReview['next_actions'] : [];
                                                @endphp

                                                <div class="ws-commerce-summary">
                                                    <div class="ws-commerce-summary-head">
                                                        <div class="ws-commerce-summary-copy">
                                                            <div class="ws-commerce-summary-title">App Review summary</div>
                                                            <div class="ws-commerce-summary-text">
                                                                {{ $diagnosticReview['headline'] ?? 'Run diagnostics to build the latest review summary.' }}
                                                            </div>
                                                        </div>

                                                        <span class="ws-commerce-pill {{ $reviewStatusClass }}">
                                                            {{ strtoupper(str_replace('_', ' ', (string) ($diagnosticReview['status'] ?? 'needs_attention'))) }}
                                                        </span>
                                                    </div>

                                                    <div class="ws-commerce-metric-row">
                                                        <div class="ws-commerce-metric">
                                                            <div class="ws-commerce-metric-label">Checks passed</div>
                                                            <div class="ws-commerce-metric-value">{{ $reviewCounts['ok'] ?? 0 }}</div>
                                                        </div>
                                                        <div class="ws-commerce-metric">
                                                            <div class="ws-commerce-metric-label">Warnings</div>
                                                            <div class="ws-commerce-metric-value">{{ $reviewCounts['warn'] ?? 0 }}</div>
                                                        </div>
                                                        <div class="ws-commerce-metric">
                                                            <div class="ws-commerce-metric-label">Blockers</div>
                                                            <div class="ws-commerce-metric-value">{{ $reviewCounts['fail'] ?? 0 }}</div>
                                                        </div>
                                                        <div class="ws-commerce-metric">
                                                            <div class="ws-commerce-metric-label">Total checks</div>
                                                            <div class="ws-commerce-metric-value">{{ $reviewCounts['total'] ?? 0 }}</div>
                                                        </div>
                                                    </div>

                                                    @if ($reviewActions !== [])
                                                        <div class="ws-commerce-action-list">
                                                            @foreach ($reviewActions as $action)
                                                                <div class="ws-commerce-action-item">
                                                                    <div class="ws-commerce-action-title">{{ $action['title'] ?? 'Next action' }}</div>
                                                                    <div class="ws-commerce-summary-text">{{ $action['summary'] ?? '' }}</div>
                                                                </div>
                                                            @endforeach
                                                        </div>
                                                    @endif

                                                    @if ($reviewEvidence !== [])
                                                        <div class="ws-commerce-evidence-grid">
                                                            @foreach ($reviewEvidence as $evidenceItem)
                                                                @php
                                                                    $evidenceClass = match ($evidenceItem['status'] ?? 'warn') {
                                                                        'ok' => 'ok',
                                                                        'fail' => 'fail',
                                                                        default => '',
                                                                    };
                                                                @endphp
                                                                <div class="ws-commerce-evidence-item">
                                                                    <div style="display:flex; justify-content:space-between; gap:10px; align-items:flex-start;">
                                                                        <div class="ws-commerce-evidence-label">{{ $evidenceItem['label'] ?? 'Evidence' }}</div>
                                                                        <span class="ws-commerce-pill {{ $evidenceClass }}">
                                                                            {{ strtoupper($evidenceItem['status'] ?? 'warn') }}
                                                                        </span>
                                                                    </div>
                                                                    <div class="ws-commerce-summary-text">{{ $evidenceItem['summary'] ?? '' }}</div>
                                                                </div>
                                                            @endforeach
                                                        </div>
                                                    @endif
                                                </div>
                                            @endif

                                            @if ($reviewPacket !== [] && filled(config('services.meta.commerce_review_scopes')))
                                                @php
                                                    $packetStatusClass = match ($reviewPacketSummary['status'] ?? 'needs_attention') {
                                                        'ready' => 'ok',
                                                        'blocked' => 'fail',
                                                        default => '',
                                                    };
                                                @endphp

                                                <div class="ws-commerce-summary">
                                                    <div class="ws-commerce-summary-head">
                                                        <div class="ws-commerce-summary-copy">
                                                            <div class="ws-commerce-summary-title">Review packet</div>
                                                            <div class="ws-commerce-summary-text">
                                                                Generated {{ $reviewPacket['generated_at'] ?? 'just now' }}.
                                                                This packet is a saved proof snapshot for App Review and can be exported as JSON.
                                                            </div>
                                                        </div>

                                                        <span class="ws-commerce-pill {{ $packetStatusClass }}">
                                                            {{ strtoupper(str_replace('_', ' ', (string) ($reviewPacketSummary['status'] ?? 'needs_attention'))) }}
                                                        </span>
                                                    </div>

                                                    <div class="ws-commerce-packet-grid">
                                                        <div class="ws-commerce-action-item">
                                                            <div class="ws-commerce-action-title">Packet summary</div>
                                                            <div class="ws-commerce-summary-text">{{ $reviewPacketSummary['headline'] ?? 'No summary available.' }}</div>
                                                            @if ($reviewPacketAccount !== [])
                                                                <span class="ws-commerce-code">
                                                                    {{ $reviewPacketAccount['username'] ?? ($reviewPacketAccount['provider_account_name'] ?? $connection->provider_account_id) }}
                                                                    @if (!empty($reviewPacketAccount['review_status']))
                                                                        · review {{ $reviewPacketAccount['review_status'] }}
                                                                    @endif
                                                                    @if (array_key_exists('product_tag_eligible', $reviewPacketAccount))
                                                                        · product tags {{ !empty($reviewPacketAccount['product_tag_eligible']) ? 'eligible' : 'not ready yet' }}
                                                                    @endif
                                                                </span>
                                                            @endif
                                                            @if ($reviewPacketChannel !== [])
                                                                <span class="ws-commerce-code">
                                                                    channel {{ $reviewPacketChannel['status'] ?? '-' }}
                                                                    · token {{ !empty($reviewPacketChannel['token_expired']) ? 'expired' : 'healthy' }}
                                                                </span>
                                                            @endif
                                                        </div>

                                                        <div class="ws-commerce-action-item">
                                                            <div class="ws-commerce-action-title">Packet totals</div>
                                                            <span class="ws-commerce-code">
                                                                catalogs {{ $reviewPacketCatalogs['discovered_count'] ?? 0 }}
                                                                · active products {{ $reviewPacketLocalShop['active_product_count'] ?? 0 }}
                                                                · sets {{ $reviewPacketLocalShop['product_set_count'] ?? 0 }}
                                                                · collections {{ $reviewPacketLocalShop['collection_count'] ?? 0 }}
                                                            </span>
                                                            <span class="ws-commerce-code">
                                                                checkout links {{ $reviewPacketCheckout['checked_count'] ?? 0 }}
                                                                · invalid {{ $reviewPacketCheckout['invalid_count'] ?? 0 }}
                                                            </span>
                                                            <span class="ws-commerce-code">
                                                                orders {{ $reviewPacketOrders['order_count'] ?? 0 }}
                                                                · test {{ $reviewPacketOrders['test_order_count'] ?? 0 }}
                                                                · snapshots {{ $reviewPacketOrders['snapshot_count'] ?? 0 }}
                                                            </span>
                                                            <span class="ws-commerce-code">
                                                                campaigns {{ $reviewPacketPromotions['campaign_count'] ?? 0 }}
                                                                · prepared {{ $reviewPacketPromotions['prepared_campaign_count'] ?? 0 }}
                                                                · collection ads {{ $reviewPacketPromotions['collection_ad_campaign_count'] ?? 0 }}
                                                                · promoted posts {{ $reviewPacketPromotions['promoted_post_campaign_count'] ?? 0 }}
                                                            </span>
                                                            @if (!empty($reviewPacketNotes['requested_scopes']))
                                                                <span class="ws-commerce-code">
                                                                    scopes {{ implode(', ', (array) $reviewPacketNotes['requested_scopes']) }}
                                                                </span>
                                                            @endif
                                                        </div>
                                                    </div>

                                                    @if (!empty($reviewPacketEvidence['next_actions']))
                                                        <div class="ws-commerce-action-list">
                                                            @foreach ((array) $reviewPacketEvidence['next_actions'] as $action)
                                                                <div class="ws-commerce-action-item">
                                                                    <div class="ws-commerce-action-title">{{ $action['title'] ?? 'Next action' }}</div>
                                                                    <div class="ws-commerce-summary-text">{{ $action['summary'] ?? '' }}</div>
                                                                </div>
                                                            @endforeach
                                                        </div>
                                                    @endif

                                                    @if ($reviewPacketSteps !== [])
                                                        <details class="ws-commerce-details">
                                                            <summary>Review demo script</summary>
                                                            <div class="ws-commerce-step-list">
                                                                @foreach ($reviewPacketSteps as $step)
                                                                    <div class="ws-commerce-step">
                                                                        <div class="ws-commerce-step-title">Step {{ $step['step'] ?? '?' }} · {{ $step['title'] ?? 'Demo step' }}</div>
                                                                        <div class="ws-commerce-summary-text">{{ $step['summary'] ?? '' }}</div>
                                                                    </div>
                                                                @endforeach
                                                            </div>
                                                        </details>
                                                    @endif

                                                    @if ($reviewPacketHistory !== [])
                                                        <details class="ws-commerce-details">
                                                            <summary>Recent packet history</summary>
                                                            <div class="ws-commerce-history-list">
                                                                @foreach ($reviewPacketHistory as $historyEntry)
                                                                    <div class="ws-commerce-history-item">
                                                                        <div class="ws-commerce-history-title">
                                                                            {{ $historyEntry['generated_at'] ?? 'Unknown time' }}
                                                                        </div>
                                                                        <div class="ws-commerce-summary-text">
                                                                            {{ strtoupper(str_replace('_', ' ', (string) ($historyEntry['status'] ?? 'needs_attention'))) }}
                                                                            · blockers {{ $historyEntry['blocker_count'] ?? 0 }}
                                                                            · warnings {{ $historyEntry['warning_count'] ?? 0 }}
                                                                            · catalogs {{ $historyEntry['discovered_catalog_count'] ?? 0 }}
                                                                            · orders {{ $historyEntry['order_count'] ?? 0 }}
                                                                            · snapshots {{ $historyEntry['snapshot_count'] ?? 0 }}
                                                                            · campaigns {{ $historyEntry['campaign_count'] ?? 0 }}
                                                                            · prepared {{ $historyEntry['prepared_campaign_count'] ?? 0 }}
                                                                        </div>
                                                                        @if (!empty($historyEntry['headline']))
                                                                            <span class="ws-commerce-code">{{ $historyEntry['headline'] }}</span>
                                                                        @endif
                                                                    </div>
                                                                @endforeach
                                                            </div>
                                                        </details>
                                                    @endif
                                                </div>
                                            @endif

                                            @if ($appReviewEvidencePacket !== [])
                                                @php
                                                    $appReviewStatusClass = match ($appReviewSummary['status'] ?? 'needs_attention') {
                                                        'ready' => 'ok',
                                                        'blocked' => 'fail',
                                                        default => '',
                                                    };
                                                @endphp

                                                <div class="ws-commerce-summary">
                                                    <div class="ws-commerce-summary-head">
                                                        <div class="ws-commerce-summary-copy">
                                                            <div class="ws-commerce-summary-title">Instagram App Review evidence</div>
                                                            <div class="ws-commerce-summary-text">
                                                                Generated {{ $appReviewEvidencePacket['generated_at'] ?? 'just now' }}.
                                                                This redacted packet combines Instagram inbox, comments, publishing, insights, and webhook evidence in one saved snapshot.
                                                            </div>
                                                        </div>

                                                        <span class="ws-commerce-pill {{ $appReviewStatusClass }}">
                                                            {{ strtoupper(str_replace('_', ' ', (string) ($appReviewSummary['status'] ?? 'needs_attention'))) }}
                                                        </span>
                                                    </div>

                                                    <div class="ws-commerce-summary-text">
                                                        {{ $appReviewSummary['headline'] ?? 'Build the final app review evidence packet to capture cross-product proof.' }}
                                                    </div>

                                                    <div class="ws-commerce-metric-row">
                                                        <div class="ws-commerce-metric">
                                                            <div class="ws-commerce-metric-label">Inbox conversations</div>
                                                            <div class="ws-commerce-metric-value">{{ $appReviewCoverageInbox['conversation_count'] ?? 0 }}</div>
                                                        </div>
                                                        <div class="ws-commerce-metric">
                                                            <div class="ws-commerce-metric-label">Messages</div>
                                                            <div class="ws-commerce-metric-value">{{ $appReviewCoverageInbox['message_count'] ?? 0 }}</div>
                                                        </div>
                                                        <div class="ws-commerce-metric">
                                                            <div class="ws-commerce-metric-label">Comments</div>
                                                            <div class="ws-commerce-metric-value">{{ $appReviewCoverageSocial['comment_count'] ?? 0 }}</div>
                                                        </div>
                                                        <div class="ws-commerce-metric">
                                                            <div class="ws-commerce-metric-label">Stories</div>
                                                            <div class="ws-commerce-metric-value">{{ $appReviewCoverageSocial['story_count'] ?? 0 }}</div>
                                                        </div>
                                                        <div class="ws-commerce-metric">
                                                            <div class="ws-commerce-metric-label">Webhook events</div>
                                                            <div class="ws-commerce-metric-value">{{ $appReviewCoverageWebhooks['event_count'] ?? 0 }}</div>
                                                        </div>
                                                    </div>

                                                    @if (!empty($appReviewEvidence['next_actions']))
                                                        <div class="ws-commerce-action-list">
                                                            @foreach ((array) $appReviewEvidence['next_actions'] as $action)
                                                                <div class="ws-commerce-action-item">
                                                                    <div class="ws-commerce-action-title">{{ $action['title'] ?? 'Next action' }}</div>
                                                                    <div class="ws-commerce-summary-text">{{ $action['summary'] ?? '' }}</div>
                                                                </div>
                                                            @endforeach
                                                        </div>
                                                    @endif

                                                    @if (!empty($appReviewEvidence['items']))
                                                        <div class="ws-commerce-evidence-grid">
                                                            @foreach ((array) $appReviewEvidence['items'] as $evidenceItem)
                                                                @php
                                                                    $appEvidenceClass = match ($evidenceItem['status'] ?? 'warn') {
                                                                        'ok' => 'ok',
                                                                        'fail' => 'fail',
                                                                        default => '',
                                                                    };
                                                                @endphp
                                                                <div class="ws-commerce-evidence-item">
                                                                    <div style="display:flex; justify-content:space-between; gap:10px; align-items:flex-start;">
                                                                        <div class="ws-commerce-evidence-label">{{ $evidenceItem['label'] ?? 'Evidence' }}</div>
                                                                        <span class="ws-commerce-pill {{ $appEvidenceClass }}">
                                                                            {{ strtoupper($evidenceItem['status'] ?? 'warn') }}
                                                                        </span>
                                                                    </div>
                                                                    <div class="ws-commerce-summary-text">{{ $evidenceItem['summary'] ?? '' }}</div>
                                                                </div>
                                                            @endforeach
                                                        </div>
                                                    @endif

                                                    @if ($appReviewSteps !== [])
                                                        <details class="ws-commerce-details">
                                                            <summary>Final review demo script</summary>
                                                            <div class="ws-commerce-step-list">
                                                                @foreach ($appReviewSteps as $step)
                                                                    <div class="ws-commerce-step">
                                                                        <div class="ws-commerce-step-title">Step {{ $step['step'] ?? '?' }} · {{ $step['title'] ?? 'Demo step' }}</div>
                                                                        <div class="ws-commerce-summary-text">{{ $step['summary'] ?? '' }}</div>
                                                                    </div>
                                                                @endforeach
                                                            </div>
                                                        </details>
                                                    @endif

                                                    <details class="ws-commerce-details">
                                                        <summary>Instagram evidence detail</summary>
                                                        <div class="ws-commerce-list">
                                                            <div class="ws-commerce-meta">
                                                                Inbox coverage:
                                                                <span class="ws-commerce-code">
                                                                    Conversations {{ $appReviewCoverageInbox['conversation_count'] ?? 0 }},
                                                                    assigned {{ $appReviewCoverageInbox['assigned_conversation_count'] ?? 0 }},
                                                                    unread {{ $appReviewCoverageInbox['unread_conversation_count'] ?? 0 }},
                                                                    attachments {{ $appReviewCoverageInbox['attachment_count'] ?? 0 }}
                                                                </span>
                                                            </div>

                                                            <div class="ws-commerce-meta">
                                                                Social coverage:
                                                                <span class="ws-commerce-code">
                                                                    Posts {{ $appReviewCoverageSocial['post_count'] ?? 0 }},
                                                                    comments {{ $appReviewCoverageSocial['comment_count'] ?? 0 }},
                                                                    DM replies {{ $appReviewCoverageSocial['dm_reply_count'] ?? 0 }},
                                                                    stories {{ $appReviewCoverageSocial['story_count'] ?? 0 }}
                                                                </span>
                                                            </div>

                                                            <div class="ws-commerce-meta">
                                                                Webhook coverage:
                                                                <span class="ws-commerce-code">
                                                                    Events {{ $appReviewCoverageWebhooks['event_count'] ?? 0 }},
                                                                    processed {{ $appReviewCoverageWebhooks['processed_count'] ?? 0 }},
                                                                    failed {{ $appReviewCoverageWebhooks['failed_count'] ?? 0 }},
                                                                    pending {{ $appReviewCoverageWebhooks['pending_count'] ?? 0 }}
                                                                </span>
                                                            </div>

                                                        </div>
                                                    </details>

                                                    @if ($appReviewHistory !== [])
                                                        <details class="ws-commerce-details">
                                                            <summary>Final evidence history</summary>
                                                            <div class="ws-commerce-history-list">
                                                                @foreach ($appReviewHistory as $historyEntry)
                                                                    <div class="ws-commerce-history-item">
                                                                        <div class="ws-commerce-history-title">
                                                                            {{ $historyEntry['generated_at'] ?? 'Unknown time' }}
                                                                        </div>
                                                                        <div class="ws-commerce-summary-text">
                                                                            {{ strtoupper(str_replace('_', ' ', (string) ($historyEntry['status'] ?? 'needs_attention'))) }}
                                                                            · conversations {{ $historyEntry['conversation_count'] ?? 0 }}
                                                                            · messages {{ $historyEntry['message_count'] ?? 0 }}
                                                                            · comments {{ $historyEntry['comment_count'] ?? 0 }}
                                                                            · stories {{ $historyEntry['story_count'] ?? 0 }}
                                                                            · webhooks {{ $historyEntry['webhook_event_count'] ?? 0 }}
                                                                            · blockers {{ $historyEntry['blocker_count'] ?? 0 }}
                                                                            · warnings {{ $historyEntry['warning_count'] ?? 0 }}
                                                                        </div>
                                                                        @if (!empty($historyEntry['headline']))
                                                                            <span class="ws-commerce-code">{{ $historyEntry['headline'] }}</span>
                                                                        @endif
                                                                    </div>
                                                                @endforeach
                                                            </div>
                                                        </details>
                                                    @endif
                                                </div>
                                            @endif

                                            @if ($readinessChecks !== [])
                                                <details class="ws-commerce-details">
                                                    <summary>Live readiness checks</summary>
                                                    <div class="ws-commerce-list">
                                                        @foreach ($readinessChecks as $item)
                                                            @php
                                                                $pillClass = match ($item['status'] ?? 'warn') {
                                                                    'ok' => 'ok',
                                                                    'fail' => 'fail',
                                                                    default => '',
                                                                };
                                                            @endphp
                                                            <div class="ws-commerce-check">
                                                                <span class="ws-commerce-pill {{ $pillClass }}">
                                                                    {{ strtoupper($item['status'] ?? 'warn') }}
                                                                </span>
                                                                {{ str_replace('_', ' ', $item['key'] ?? 'check') }}
                                                                @if (!empty($item['summary']))
                                                                    <span class="ws-commerce-code">{{ $item['summary'] }}</span>
                                                                @endif
                                                            </div>
                                                        @endforeach
                                                    </div>
                                                </details>
                                            @endif

                                            <details class="ws-commerce-details">
                                                <summary>Live diagnostics detail</summary>

                                                <div class="ws-commerce-list">
                                                    @if ($diagnosticPermissions !== [])
                                                        <div class="ws-commerce-meta">
                                                            Permission readiness:
                                                            <span class="ws-commerce-code">
                                                                Required {{ count((array) ($diagnosticPermissions['required'] ?? [])) }},
                                                                granted {{ count((array) ($diagnosticPermissions['granted'] ?? [])) }},
                                                                missing {{ count((array) ($diagnosticPermissions['missing'] ?? [])) }}
                                                            </span>
                                                            @if (!empty($diagnosticPermissions['missing']))
                                                                <span class="ws-commerce-code">Missing: {{ implode(', ', (array) $diagnosticPermissions['missing']) }}</span>
                                                            @endif
                                                        </div>
                                                    @endif

                                                    @if ($diagnosticChannel !== [])
                                                        <div class="ws-commerce-meta">
                                                            Channel health:
                                                            <span class="ws-commerce-code">
                                                                Status {{ $diagnosticChannel['status'] ?? '-' }},
                                                                type {{ $diagnosticChannel['provider_account_type'] ?? '-' }},
                                                                token {{ !empty($diagnosticChannel['token_expired']) ? 'expired' : 'healthy' }}
                                                            </span>
                                                            @if (!empty($diagnosticChannel['connected_at']))
                                                                <span class="ws-commerce-code">Connected at: {{ $diagnosticChannel['connected_at'] }}</span>
                                                            @endif
                                                            @if (!empty($diagnosticChannel['token_expires_at']))
                                                                <span class="ws-commerce-code">Token expires at: {{ $diagnosticChannel['token_expires_at'] }}</span>
                                                            @endif
                                                            @if (!empty($diagnosticChannel['live_subscribed_fields']))
                                                                <span class="ws-commerce-code">Live subscribed fields: {{ implode(', ', (array) $diagnosticChannel['live_subscribed_fields']) }}</span>
                                                            @endif
                                                        </div>
                                                    @endif

                                                    @if ($diagnosticWebhook !== [])
                                                        <div class="ws-commerce-meta">
                                                            Webhook fields:
                                                            <span class="ws-commerce-code">
                                                                {{ !empty($diagnosticWebhook['verified_fields']) ? implode(', ', (array) $diagnosticWebhook['verified_fields']) : 'No verified fields stored' }}
                                                            </span>
                                                            @if (!empty($diagnosticWebhook['missing_fields']))
                                                                <span class="ws-commerce-code">Missing fields: {{ implode(', ', (array) $diagnosticWebhook['missing_fields']) }}</span>
                                                            @endif
                                                        </div>
                                                    @endif

                                                    @if (!empty($diagnosticShop['local_stats']))
                                                        <div class="ws-commerce-meta">
                                                            Shop structure:
                                                            <span class="ws-commerce-code">
                                                                Source catalogs {{ $diagnosticShop['local_stats']['source_catalog_count'] ?? 0 }},
                                                                active products {{ $diagnosticShop['local_stats']['active_product_count'] ?? 0 }},
                                                                offers {{ $diagnosticShop['local_stats']['offer_count'] ?? 0 }},
                                                                product sets {{ $diagnosticShop['local_stats']['product_set_count'] ?? 0 }},
                                                                collections {{ $diagnosticShop['local_stats']['collection_count'] ?? 0 }},
                                                                orders {{ $diagnosticShop['local_stats']['order_count'] ?? 0 }},
                                                                snapshots {{ $diagnosticShop['local_stats']['order_snapshot_count'] ?? 0 }},
                                                                campaigns {{ $diagnosticShop['local_stats']['promotion_campaign_count'] ?? 0 }},
                                                                prepared campaigns {{ $diagnosticShop['local_stats']['prepared_promotion_campaign_count'] ?? 0 }}
                                                            </span>
                                                        </div>
                                                    @endif

                                                    @if (!empty($diagnosticShop['meta_catalogs']))
                                                        <div class="ws-commerce-meta">
                                                            Live shop assets:
                                                            @foreach ((array) $diagnosticShop['meta_catalogs'] as $shopCatalog)
                                                                <span class="ws-commerce-code">
                                                                    {{ $shopCatalog['name'] ?? 'Meta catalog' }}
                                                                    · ID {{ $shopCatalog['external_catalog_id'] ?? '-' }}
                                                                    @if (!empty($shopCatalog['vertical']))
                                                                        · {{ $shopCatalog['vertical'] }}
                                                                    @endif
                                                                    @if (isset($shopCatalog['product_count']))
                                                                        · {{ $shopCatalog['product_count'] }} product(s)
                                                                    @endif
                                                                    · {{ match ($shopCatalog['live_status'] ?? null) {
                                                                        'ok' => 'live ok',
                                                                        'not_applicable' => 'separate Facebook commerce authorization required',
                                                                        default => 'live check failed',
                                                                    } }}
                                                                    @if (!empty($shopCatalog['live_error']))
                                                                        · {{ $shopCatalog['live_error'] }}
                                                                    @endif
                                                                </span>
                                                            @endforeach
                                                        </div>
                                                    @endif

                                                    @if ($diagnosticCheckout !== [])
                                                        <div class="ws-commerce-meta">
                                                            Checkout URL audit:
                                                            <span class="ws-commerce-code">
                                                                Checked {{ $diagnosticCheckout['checked_count'] ?? 0 }},
                                                                valid HTTPS {{ $diagnosticCheckout['valid_count'] ?? 0 }},
                                                                invalid {{ $diagnosticCheckout['invalid_count'] ?? 0 }}
                                                            </span>
                                                            @if (!empty($diagnosticCheckout['invalid_examples']))
                                                                @foreach ((array) $diagnosticCheckout['invalid_examples'] as $invalidUrl)
                                                                    <span class="ws-commerce-code">
                                                                        {{ $invalidUrl['type'] ?? 'url' }} · {{ $invalidUrl['label'] ?? '-' }} · {{ $invalidUrl['url'] ?? '-' }}
                                                                    </span>
                                                                @endforeach
                                                            @endif
                                                        </div>
                                                    @endif

                                                    @foreach ($checks as $key => $check)
                                                        <div class="ws-commerce-check">
                                                            <span class="ws-commerce-pill {{ !empty($check['ok']) ? 'ok' : 'fail' }}">
                                                                {{ !empty($check['ok']) ? 'OK' : 'Needs access' }}
                                                            </span>
                                                            {{ $key }}
                                                            @if (empty($check['ok']) && !empty($check['error']))
                                                                <span class="ws-commerce-code">{{ $check['error'] }}</span>
                                                            @endif
                                                        </div>
                                                    @endforeach
                                                </div>
                                            </details>
                                        </div>
                                    </div>
                                @empty
                                    <div class="ws-empty">
                                        Connect an Instagram account first, then return here to discover Meta Commerce assets.
                                    </div>
                                @endforelse
                            </div>
                        </div>

                        <div class="ws-commerce-card">
                            <h3 class="ws-section-title">Discovered Meta catalogs</h3>

                            <div class="ws-commerce-list">
                                @forelse (($metaCommerceCatalogs ?? collect()) as $catalog)
                                    <div class="ws-commerce-catalog">
                                        <div class="ws-commerce-title">{{ $catalog->name }}</div>
                                        <div class="ws-commerce-meta">
                                            Catalog ID: {{ $catalog->external_catalog_id ?: '-' }}
                                            @if ($catalog->external_business_id)
                                                · Business ID: {{ $catalog->external_business_id }}
                                            @endif
                                            @if ($catalog->providerConnection)
                                                · Instagram: {{ $catalog->providerConnection->provider_account_name ?: $catalog->providerConnection->provider_account_id }}
                                            @endif
                                        </div>
                                        <div class="ws-commerce-meta">
                                            Sync status: {{ str_replace('_', ' ', $catalog->meta_sync_status ?: 'not synced') }}
                                            @if ($catalog->meta_synced_at)
                                                · {{ $catalog->meta_synced_at->format('M d, Y H:i') }}
                                            @endif
                                        </div>
                                        @php
                                            $lastProductSync = is_array($catalog->meta ?? null) ? ($catalog->meta['last_product_sync'] ?? null) : null;
                                        @endphp
                                        @if (is_array($lastProductSync))
                                            <div class="ws-commerce-meta">
                                                Last product sync:
                                                {{ $lastProductSync['product_count'] ?? 0 }} product(s)
                                                · {{ $lastProductSync['status'] ?? 'unknown' }}
                                                @if (!empty($lastProductSync['batch_handle']))
                                                    <span class="ws-commerce-code">Batch handle: {{ $lastProductSync['batch_handle'] }}</span>
                                                @endif
                                            </div>
                                        @endif

                                        <form method="POST" action="{{ route('settings.commerce.catalogs.products.sync', $catalog) }}" class="ws-commerce-sync-form">
                                            @csrf
                                            <select name="source_catalog_id" class="ws-commerce-select" required>
                                                <option value="">Choose Leadochat source catalog</option>
                                                @foreach (($commerceSourceCatalogs ?? collect()) as $sourceCatalog)
                                                    <option value="{{ $sourceCatalog->id }}">
                                                        {{ $sourceCatalog->name }} · {{ $sourceCatalog->products_count }} active products
                                                    </option>
                                                @endforeach
                                            </select>
                                            <button type="submit" class="ws-commerce-button">Sync products to Meta</button>
                                        </form>
                                    </div>
                                @empty
                                    <div class="ws-empty">
                                        No Meta catalogs discovered yet.
                                    </div>
                                @endforelse
                            </div>
                        </div>

                        <div class="ws-commerce-grid">
                            <div class="ws-commerce-card">
                                <h3 class="ws-section-title">Create product set</h3>
                                <div class="ws-section-subtitle">
                                    Group synced products into reusable sets for each Meta catalog. This is the foundation for collections and shop merchandising.
                                </div>

                                <form method="POST" action="{{ route('settings.commerce.product-sets.store') }}" class="ws-commerce-list" style="margin-top:14px;">
                                    @csrf

                                    <select name="meta_catalog_id" class="ws-commerce-select" required>
                                        <option value="">Choose Meta catalog</option>
                                        @foreach (($metaCommerceCatalogs ?? collect()) as $catalog)
                                            <option value="{{ $catalog->id }}">
                                                {{ $catalog->name }}
                                                @if ($catalog->providerConnection)
                                                    · {{ $catalog->providerConnection->provider_account_name ?: $catalog->providerConnection->provider_account_id }}
                                                @endif
                                            </option>
                                        @endforeach
                                    </select>

                                    <input
                                        type="text"
                                        name="name"
                                        class="ws-commerce-input"
                                        maxlength="160"
                                        placeholder="Featured summer drop"
                                        value="{{ old('name') }}"
                                        required
                                    >

                                    <textarea
                                        name="description"
                                        class="ws-commerce-textarea"
                                        maxlength="1000"
                                        placeholder="Optional internal notes for this product set"
                                    >{{ old('description') }}</textarea>

                                    <div>
                                        <button type="submit" class="ws-commerce-button">Create product set</button>
                                    </div>
                                </form>
                            </div>

                            <div class="ws-commerce-card">
                                <h3 class="ws-section-title">Product set readiness</h3>
                                <div class="ws-section-subtitle">
                                    Only active Leadochat products that already have Meta sync status queued or synced can be added to a product set.
                                </div>
                                <div class="ws-commerce-list">
                                    <span class="ws-commerce-pill ok">{{ ($commerceTaggableProducts ?? collect())->count() }} taggable products</span>
                                    <span class="ws-commerce-pill">{{ ($commerceProductSets ?? collect())->count() }} product sets</span>
                                    <span class="ws-commerce-pill">{{ ($metaCommerceCatalogs ?? collect())->count() }} Meta catalogs</span>
                                </div>
                            </div>
                        </div>

                        <div class="ws-commerce-grid">
                            <div class="ws-commerce-card">
                                <h3 class="ws-section-title">Create collection</h3>
                                <div class="ws-section-subtitle">
                                    Collections sit above product sets and act as the storefront grouping layer for a Meta catalog.
                                </div>

                                <form method="POST" action="{{ route('settings.commerce.collections.store') }}" class="ws-commerce-list" style="margin-top:14px;">
                                    @csrf

                                    <select name="meta_catalog_id" class="ws-commerce-select" required>
                                        <option value="">Choose Meta catalog</option>
                                        @foreach (($metaCommerceCatalogs ?? collect()) as $catalog)
                                            <option value="{{ $catalog->id }}">
                                                {{ $catalog->name }}
                                                @if ($catalog->providerConnection)
                                                    · {{ $catalog->providerConnection->provider_account_name ?: $catalog->providerConnection->provider_account_id }}
                                                @endif
                                            </option>
                                        @endforeach
                                    </select>

                                    <input
                                        type="text"
                                        name="name"
                                        class="ws-commerce-input"
                                        maxlength="160"
                                        placeholder="Holiday featured collection"
                                        value="{{ old('name') }}"
                                        required
                                    >

                                    <textarea
                                        name="description"
                                        class="ws-commerce-textarea"
                                        maxlength="1000"
                                        placeholder="Optional internal notes for this collection"
                                    >{{ old('description') }}</textarea>

                                    <div>
                                        <button type="submit" class="ws-commerce-button">Create collection</button>
                                    </div>
                                </form>
                            </div>

                            <div class="ws-commerce-card">
                                <h3 class="ws-section-title">Collection readiness</h3>
                                <div class="ws-section-subtitle">
                                    Collections can only use product sets that belong to the same Meta catalog, keeping the shop structure clean.
                                </div>
                                <div class="ws-commerce-list">
                                    <span class="ws-commerce-pill ok">{{ ($commerceCollections ?? collect())->count() }} collections</span>
                                    <span class="ws-commerce-pill">{{ ($commerceProductSets ?? collect())->count() }} product sets</span>
                                    <span class="ws-commerce-pill">{{ ($metaCommerceCatalogs ?? collect())->count() }} Meta catalogs</span>
                                </div>
                            </div>
                        </div>

                        <div class="ws-commerce-grid">
                            <div class="ws-commerce-card">
                                <h3 class="ws-section-title">Create test order</h3>
                                <div class="ws-section-subtitle">
                                    Build review-safe order proof with real catalog products, status changes, and snapshot history.
                                </div>

                                <form method="POST" action="{{ route('settings.commerce.orders.store') }}" class="ws-commerce-list" style="margin-top:14px;">
                                    @csrf

                                    <select name="provider_connection_id" class="ws-commerce-select" required>
                                        <option value="">Choose Instagram account</option>
                                        @foreach (($commerceConnections ?? collect()) as $connection)
                                            <option value="{{ $connection->id }}" @selected((string) old('provider_connection_id') === (string) $connection->id)>
                                                {{ $connection->provider_account_name ?: $connection->provider_account_id }}
                                            </option>
                                        @endforeach
                                    </select>

                                    <select name="catalog_id" class="ws-commerce-select" required>
                                        <option value="">Choose source catalog</option>
                                        @foreach (($commerceSourceCatalogs ?? collect()) as $sourceCatalog)
                                            <option value="{{ $sourceCatalog->id }}" @selected((string) old('catalog_id') === (string) $sourceCatalog->id)>
                                                {{ $sourceCatalog->name }} · {{ $sourceCatalog->products_count }} active products
                                            </option>
                                        @endforeach
                                    </select>

                                    <select name="catalog_collection_id" class="ws-commerce-select">
                                        <option value="">Optional collection context</option>
                                        @foreach (($commerceCollections ?? collect()) as $collection)
                                            <option value="{{ $collection->id }}" @selected((string) old('catalog_collection_id') === (string) $collection->id)>
                                                {{ $collection->name }}
                                                @if ($collection->providerConnection)
                                                    · {{ $collection->providerConnection->provider_account_name ?: $collection->providerConnection->provider_account_id }}
                                                @endif
                                            </option>
                                        @endforeach
                                    </select>

                                    <select name="product_ids[]" class="ws-commerce-select is-multi" multiple required>
                                        @foreach (($commerceTaggableProducts ?? collect()) as $product)
                                            <option value="{{ $product->id }}" @selected(in_array((string) $product->id, array_map('strval', (array) old('product_ids', [])), true))>
                                                {{ $product->title }}
                                                @if ($product->catalog)
                                                    · {{ $product->catalog->name }}
                                                @endif
                                            </option>
                                        @endforeach
                                    </select>

                                    <div class="ws-commerce-grid" style="grid-template-columns:repeat(2, minmax(0, 1fr));">
                                        <input type="text" name="external_order_id" class="ws-commerce-input" maxlength="120" placeholder="External order ID" value="{{ old('external_order_id') }}">
                                        <input type="text" name="external_checkout_id" class="ws-commerce-input" maxlength="120" placeholder="External checkout ID" value="{{ old('external_checkout_id') }}">
                                        <input type="text" name="customer_reference" class="ws-commerce-input" maxlength="120" placeholder="Customer reference" value="{{ old('customer_reference') }}">
                                        <input type="text" name="customer_name" class="ws-commerce-input" maxlength="160" placeholder="Customer name" value="{{ old('customer_name') }}">
                                        <input type="email" name="customer_email" class="ws-commerce-input" maxlength="160" placeholder="Customer email" value="{{ old('customer_email') }}">
                                        <input type="datetime-local" name="placed_at" class="ws-commerce-input" value="{{ old('placed_at') }}">
                                    </div>

                                    <div class="ws-commerce-grid" style="grid-template-columns:repeat(4, minmax(0, 1fr));">
                                        <select name="source" class="ws-commerce-select" required>
                                            @foreach (['manual_test' => 'Manual test', 'instagram_shop' => 'Instagram shop', 'website_checkout' => 'Website checkout', 'catalog_share' => 'Catalog share'] as $value => $label)
                                                <option value="{{ $value }}" @selected(old('source', 'manual_test') === $value)>{{ $label }}</option>
                                            @endforeach
                                        </select>
                                        <select name="status" class="ws-commerce-select" required>
                                            @foreach (['placed', 'confirmed', 'processing', 'fulfilled', 'cancelled', 'refunded'] as $value)
                                                <option value="{{ $value }}" @selected(old('status', 'placed') === $value)>{{ ucfirst($value) }}</option>
                                            @endforeach
                                        </select>
                                        <select name="payment_status" class="ws-commerce-select" required>
                                            @foreach (['pending', 'authorized', 'paid', 'failed', 'refunded'] as $value)
                                                <option value="{{ $value }}" @selected(old('payment_status', 'pending') === $value)>{{ ucfirst($value) }}</option>
                                            @endforeach
                                        </select>
                                        <select name="fulfillment_status" class="ws-commerce-select" required>
                                            @foreach (['unfulfilled', 'processing', 'fulfilled', 'returned'] as $value)
                                                <option value="{{ $value }}" @selected(old('fulfillment_status', 'unfulfilled') === $value)>{{ ucfirst(str_replace('_', ' ', $value)) }}</option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div class="ws-commerce-grid" style="grid-template-columns:repeat(4, minmax(0, 1fr));">
                                        <input type="text" name="currency" class="ws-commerce-input" maxlength="3" placeholder="USD" value="{{ old('currency', 'USD') }}" required>
                                        <input type="number" step="0.01" min="0" name="discount_amount" class="ws-commerce-input" placeholder="Discount" value="{{ old('discount_amount', '0') }}">
                                        <input type="number" step="0.01" min="0" name="tax_amount" class="ws-commerce-input" placeholder="Tax" value="{{ old('tax_amount', '0') }}">
                                        <input type="number" step="0.01" min="0" name="shipping_amount" class="ws-commerce-input" placeholder="Shipping" value="{{ old('shipping_amount', '0') }}">
                                    </div>

                                    <textarea name="notes" class="ws-commerce-textarea" maxlength="2000" placeholder="Optional internal notes for the test order">{{ old('notes') }}</textarea>

                                    <div>
                                        <button type="submit" class="ws-commerce-button">Create test order</button>
                                    </div>
                                </form>
                            </div>

                            <div class="ws-commerce-card">
                                <h3 class="ws-section-title">Order readiness</h3>
                                <div class="ws-section-subtitle">
                                    Orders give App Review a concrete way to inspect checkout handoff, state changes, and immutable proof snapshots.
                                </div>
                                <div class="ws-commerce-list">
                                    <span class="ws-commerce-pill ok">{{ (int) (($commerceOrderStats ?? [])['order_count'] ?? 0) }} orders</span>
                                    <span class="ws-commerce-pill">{{ (int) (($commerceOrderStats ?? [])['test_order_count'] ?? 0) }} test orders</span>
                                    <span class="ws-commerce-pill">{{ (int) (($commerceOrderStats ?? [])['snapshot_count'] ?? 0) }} snapshots</span>
                                    <span class="ws-commerce-pill">{{ ($commerceCollections ?? collect())->count() }} collections</span>
                                </div>
                            </div>
                        </div>

                        <div class="ws-commerce-card">
                            <h3 class="ws-section-title">Order ledger</h3>

                            <div class="ws-commerce-order-list">
                                @forelse (($commerceOrders ?? collect()) as $order)
                                    <div class="ws-commerce-order-item">
                                        <div class="ws-commerce-head">
                                            <div>
                                                <div class="ws-commerce-title">
                                                    Order #{{ $order->id }}
                                                    @if ($order->external_order_id)
                                                        · {{ $order->external_order_id }}
                                                    @endif
                                                </div>
                                                <div class="ws-commerce-meta">
                                                    {{ $order->providerConnection?->provider_account_name ?: $order->providerConnection?->provider_account_id ?: 'Instagram account' }}
                                                    @if ($order->catalog)
                                                        · {{ $order->catalog->name }}
                                                    @endif
                                                    @if ($order->collection)
                                                        · collection {{ $order->collection->name }}
                                                    @endif
                                                    @if ($order->placed_at)
                                                        · {{ $order->placed_at->format('M d, Y H:i') }}
                                                    @endif
                                                </div>
                                            </div>

                                            <div class="ws-commerce-order-meta-row">
                                                <span class="ws-commerce-pill {{ $order->is_test ? 'ok' : '' }}">{{ $order->is_test ? 'Test order' : 'Live order' }}</span>
                                                <span class="ws-commerce-pill">{{ $order->currency }} {{ number_format((float) $order->total_amount, 2) }}</span>
                                            </div>
                                        </div>

                                        <div class="ws-commerce-order-meta-row">
                                            <span class="ws-commerce-pill">{{ ucfirst(str_replace('_', ' ', $order->status)) }}</span>
                                            <span class="ws-commerce-pill">{{ ucfirst(str_replace('_', ' ', $order->payment_status)) }}</span>
                                            <span class="ws-commerce-pill">{{ ucfirst(str_replace('_', ' ', $order->fulfillment_status)) }}</span>
                                            <span class="ws-commerce-pill">{{ ucfirst(str_replace('_', ' ', $order->source)) }}</span>
                                            <span class="ws-commerce-pill">{{ $order->items_count }} item(s)</span>
                                            <span class="ws-commerce-pill">{{ $order->snapshots_count }} snapshot(s)</span>
                                        </div>

                                        @if ($order->customer_name || $order->customer_email || $order->customer_reference)
                                            <div class="ws-commerce-meta">
                                                Customer:
                                                {{ $order->customer_name ?: 'Unknown customer' }}
                                                @if ($order->customer_email)
                                                    · {{ $order->customer_email }}
                                                @endif
                                                @if ($order->customer_reference)
                                                    · ref {{ $order->customer_reference }}
                                                @endif
                                            </div>
                                        @endif

                                        @if ($order->items->isNotEmpty())
                                            <div class="ws-commerce-order-meta-row">
                                                @foreach ($order->items as $item)
                                                    <span class="ws-commerce-pill">
                                                        {{ $item->title }} · {{ $item->currency }} {{ number_format((float) $item->total_price, 2) }}
                                                    </span>
                                                @endforeach
                                            </div>
                                        @endif

                                        <div class="ws-commerce-grid">
                                            <form method="POST" action="{{ route('settings.commerce.orders.status.update', $order) }}" class="ws-commerce-list" style="margin-top:0;">
                                                @csrf
                                                @method('PATCH')

                                                <div class="ws-commerce-grid" style="grid-template-columns:repeat(3, minmax(0, 1fr));">
                                                    <select name="status" class="ws-commerce-select" required>
                                                        @foreach (['placed', 'confirmed', 'processing', 'fulfilled', 'cancelled', 'refunded'] as $value)
                                                            <option value="{{ $value }}" @selected($order->status === $value)>{{ ucfirst($value) }}</option>
                                                        @endforeach
                                                    </select>
                                                    <select name="payment_status" class="ws-commerce-select" required>
                                                        @foreach (['pending', 'authorized', 'paid', 'failed', 'refunded'] as $value)
                                                            <option value="{{ $value }}" @selected($order->payment_status === $value)>{{ ucfirst($value) }}</option>
                                                        @endforeach
                                                    </select>
                                                    <select name="fulfillment_status" class="ws-commerce-select" required>
                                                        @foreach (['unfulfilled', 'processing', 'fulfilled', 'returned'] as $value)
                                                            <option value="{{ $value }}" @selected($order->fulfillment_status === $value)>{{ ucfirst(str_replace('_', ' ', $value)) }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>

                                                <input type="text" name="notes" class="ws-commerce-input" maxlength="2000" placeholder="Optional status note">

                                                <div>
                                                    <button type="submit" class="ws-commerce-button">Update status</button>
                                                </div>
                                            </form>

                                            <form method="POST" action="{{ route('settings.commerce.orders.snapshots.store', $order) }}" class="ws-commerce-list" style="margin-top:0;">
                                                @csrf

                                                <select name="snapshot_type" class="ws-commerce-select">
                                                    <option value="manual">Manual snapshot</option>
                                                    <option value="review_demo">Review demo snapshot</option>
                                                    <option value="sync_check">Sync check snapshot</option>
                                                </select>

                                                <input type="text" name="notes" class="ws-commerce-input" maxlength="2000" placeholder="Optional snapshot note">

                                                <div>
                                                    <button type="submit" class="ws-commerce-button" style="background:#1d4ed8;">Capture snapshot</button>
                                                </div>
                                            </form>
                                        </div>

                                        @if ($order->snapshots->isNotEmpty())
                                            <details class="ws-commerce-details">
                                                <summary>Snapshot history</summary>
                                                <div class="ws-commerce-history-list">
                                                    @foreach ($order->snapshots->take(5) as $snapshot)
                                                        <div class="ws-commerce-history-item">
                                                            <div class="ws-commerce-history-title">
                                                                {{ $snapshot->captured_at?->format('M d, Y H:i') ?: 'Unknown time' }}
                                                                · {{ ucfirst(str_replace('_', ' ', $snapshot->snapshot_type)) }}
                                                            </div>
                                                            <div class="ws-commerce-summary-text">
                                                                Status {{ $snapshot->status ?? 'unknown' }}
                                                                @if ($snapshot->createdBy)
                                                                    · by {{ $snapshot->createdBy->name }}
                                                                @endif
                                                            </div>
                                                            @if (!empty($snapshot->payload['note']))
                                                                <span class="ws-commerce-code">{{ $snapshot->payload['note'] }}</span>
                                                            @endif
                                                        </div>
                                                    @endforeach
                                                </div>
                                            </details>
                                        @endif
                                    </div>
                                @empty
                                    <div class="ws-empty">
                                        No orders recorded yet. Create a test order to start building review proof for checkout and order-state flows.
                                    </div>
                                @endforelse
                            </div>
                        </div>

                        <div class="ws-commerce-grid">
                            <div class="ws-commerce-card">
                                <h3 class="ws-section-title">Create promotion campaign</h3>
                                <div class="ws-section-subtitle">
                                    Prepare collection ads, shops ads, and promoted posts from the same synced commerce assets already used across Leadochat.
                                </div>

                                <form method="POST" action="{{ route('settings.commerce.promotions.store') }}" class="ws-commerce-list" style="margin-top:14px;">
                                    @csrf

                                    <div class="ws-commerce-grid" style="grid-template-columns:repeat(2, minmax(0, 1fr));">
                                        <select name="provider_connection_id" class="ws-commerce-select" required>
                                            <option value="">Choose Instagram account</option>
                                            @foreach (($commerceConnections ?? collect()) as $connection)
                                                <option value="{{ $connection->id }}" @selected((string) old('provider_connection_id') === (string) $connection->id)>
                                                    {{ $connection->provider_account_name ?: $connection->provider_account_id }}
                                                </option>
                                            @endforeach
                                        </select>

                                        <select name="campaign_type" class="ws-commerce-select" required>
                                            @foreach (['collection_ad' => 'Collection ad', 'shops_ad' => 'Shops ad', 'promoted_post' => 'Promoted post'] as $value => $label)
                                                <option value="{{ $value }}" @selected(old('campaign_type', 'collection_ad') === $value)>{{ $label }}</option>
                                            @endforeach
                                        </select>

                                        <select name="objective" class="ws-commerce-select" required>
                                            @foreach (['sales' => 'Sales', 'traffic' => 'Traffic', 'engagement' => 'Engagement', 'catalog_sales' => 'Catalog sales'] as $value => $label)
                                                <option value="{{ $value }}" @selected(old('objective', 'sales') === $value)>{{ $label }}</option>
                                            @endforeach
                                        </select>

                                        <select name="status" class="ws-commerce-select" required>
                                            @foreach (['draft' => 'Draft', 'active' => 'Active', 'paused' => 'Paused', 'archived' => 'Archived'] as $value => $label)
                                                <option value="{{ $value }}" @selected(old('status', 'draft') === $value)>{{ $label }}</option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <input type="text" name="name" class="ws-commerce-input" maxlength="160" placeholder="Summer collection retargeting" value="{{ old('name') }}" required>

                                    <textarea name="description" class="ws-commerce-textarea" maxlength="2000" placeholder="Optional internal campaign notes">{{ old('description') }}</textarea>

                                    <div class="ws-commerce-grid" style="grid-template-columns:repeat(2, minmax(0, 1fr));">
                                        <select name="catalog_collection_id" class="ws-commerce-select">
                                            <option value="">Optional collection asset</option>
                                            @foreach (($commerceCollections ?? collect()) as $collection)
                                                <option value="{{ $collection->id }}" @selected((string) old('catalog_collection_id') === (string) $collection->id)>
                                                    {{ $collection->name }}
                                                    @if ($collection->providerConnection)
                                                        · {{ $collection->providerConnection->provider_account_name ?: $collection->providerConnection->provider_account_id }}
                                                    @endif
                                                </option>
                                            @endforeach
                                        </select>

                                        <select name="social_post_id" class="ws-commerce-select">
                                            <option value="">Optional Instagram post asset</option>
                                            @foreach (($commercePromotablePosts ?? collect()) as $post)
                                                @php
                                                    $postProductTagCount = is_array($post->raw ?? null) ? count((array) ($post->raw['product_tags'] ?? [])) : 0;
                                                @endphp
                                                <option value="{{ $post->id }}" @selected((string) old('social_post_id') === (string) $post->id)>
                                                    {{ \Illuminate\Support\Str::limit($post->caption ?: ('Post #' . $post->id), 48) }}
                                                    · tags {{ $postProductTagCount }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div class="ws-commerce-grid" style="grid-template-columns:repeat(4, minmax(0, 1fr));">
                                        <input type="number" step="0.01" min="0" name="budget_amount" class="ws-commerce-input" placeholder="Budget" value="{{ old('budget_amount', '0') }}" required>
                                        <input type="text" name="currency" class="ws-commerce-input" maxlength="3" placeholder="USD" value="{{ old('currency', 'USD') }}" required>
                                        <input type="text" name="call_to_action" class="ws-commerce-input" maxlength="60" placeholder="Shop now" value="{{ old('call_to_action') }}">
                                        <input type="url" name="destination_url" class="ws-commerce-input" maxlength="2048" placeholder="https://example.com/shop" value="{{ old('destination_url') }}">
                                    </div>

                                    <div class="ws-commerce-grid" style="grid-template-columns:repeat(2, minmax(0, 1fr));">
                                        <input type="datetime-local" name="starts_at" class="ws-commerce-input" value="{{ old('starts_at') }}">
                                        <input type="datetime-local" name="ends_at" class="ws-commerce-input" value="{{ old('ends_at') }}">
                                    </div>

                                    <div>
                                        <button type="submit" class="ws-commerce-button">Create promotion campaign</button>
                                    </div>
                                </form>
                            </div>

                            <div class="ws-commerce-card">
                                <h3 class="ws-section-title">Promotion readiness</h3>
                                <div class="ws-section-subtitle">
                                    Prepared promotion previews make the ad foundation reviewable even before full ad delivery flows are added.
                                </div>
                                <div class="ws-commerce-list">
                                    <span class="ws-commerce-pill ok">{{ (int) (($commercePromotionStats ?? [])['campaign_count'] ?? 0) }} campaigns</span>
                                    <span class="ws-commerce-pill">{{ (int) (($commercePromotionStats ?? [])['prepared_count'] ?? 0) }} prepared</span>
                                    <span class="ws-commerce-pill">{{ (int) (($commercePromotionStats ?? [])['collection_ad_count'] ?? 0) }} collection ads</span>
                                    <span class="ws-commerce-pill">{{ (int) (($commercePromotionStats ?? [])['promoted_post_count'] ?? 0) }} promoted posts</span>
                                    <span class="ws-commerce-pill">{{ (int) (($commercePromotionStats ?? [])['shops_ad_count'] ?? 0) }} shops ads</span>
                                </div>
                            </div>
                        </div>

                        <div class="ws-commerce-card">
                            <h3 class="ws-section-title">Promotion campaigns</h3>

                            <div class="ws-commerce-order-list">
                                @forelse (($commercePromotionCampaigns ?? collect()) as $campaign)
                                    @php
                                        $campaignPreview = is_array($campaign->meta ?? null) ? ($campaign->meta['last_prepared_preview'] ?? null) : null;
                                        $campaignChecks = is_array($campaignPreview['checks'] ?? null) ? $campaignPreview['checks'] : [];
                                        $campaignAssetSummary = is_array($campaignPreview['asset_summary'] ?? null) ? $campaignPreview['asset_summary'] : [];
                                        $campaignPayloadPreview = is_array($campaignPreview['payload_preview'] ?? null) ? $campaignPreview['payload_preview'] : [];
                                    @endphp

                                    <div class="ws-commerce-order-item">
                                        <div class="ws-commerce-head">
                                            <div>
                                                <div class="ws-commerce-title">{{ $campaign->name }}</div>
                                                <div class="ws-commerce-meta">
                                                    {{ ucfirst(str_replace('_', ' ', $campaign->campaign_type)) }}
                                                    · {{ ucfirst(str_replace('_', ' ', $campaign->objective)) }}
                                                    @if ($campaign->providerConnection)
                                                        · {{ $campaign->providerConnection->provider_account_name ?: $campaign->providerConnection->provider_account_id }}
                                                    @endif
                                                </div>
                                            </div>

                                            <form method="POST" action="{{ route('settings.commerce.promotions.delete', $campaign) }}">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="ws-commerce-button" style="background:#b91c1c;">Delete</button>
                                            </form>
                                        </div>

                                        <div class="ws-commerce-order-meta-row">
                                            <span class="ws-commerce-pill">{{ ucfirst(str_replace('_', ' ', $campaign->status)) }}</span>
                                            <span class="ws-commerce-pill">{{ str_replace('_', ' ', $campaign->meta_sync_status ?: 'not prepared') }}</span>
                                            <span class="ws-commerce-pill">{{ $campaign->currency }} {{ number_format((float) $campaign->budget_amount, 2) }}</span>
                                            @if ($campaign->collection)
                                                <span class="ws-commerce-pill">Collection {{ $campaign->collection->name }}</span>
                                            @endif
                                            @if ($campaign->socialPost)
                                                @php
                                                    $promotionPostTagCount = is_array($campaign->socialPost->raw ?? null) ? count((array) ($campaign->socialPost->raw['product_tags'] ?? [])) : 0;
                                                @endphp
                                                <span class="ws-commerce-pill">Post tags {{ $promotionPostTagCount }}</span>
                                            @endif
                                        </div>

                                        @if ($campaign->description)
                                            <div class="ws-commerce-meta">{{ $campaign->description }}</div>
                                        @endif

                                        @if ($campaign->destination_url)
                                            <span class="ws-commerce-code">{{ $campaign->destination_url }}</span>
                                        @endif

                                        @if ($campaign->meta_sync_error)
                                            <span class="ws-commerce-code">{{ $campaign->meta_sync_error }}</span>
                                        @endif

                                        <div class="ws-commerce-grid">
                                            <form method="POST" action="{{ route('settings.commerce.promotions.prepare', $campaign) }}" class="ws-commerce-list" style="margin-top:0;">
                                                @csrf
                                                <div>
                                                    <button type="submit" class="ws-commerce-button" style="background:#1d4ed8;">Prepare preview</button>
                                                </div>
                                            </form>

                                            <form method="POST" action="{{ route('settings.commerce.promotions.status.update', $campaign) }}" class="ws-commerce-list" style="margin-top:0;">
                                                @csrf
                                                @method('PATCH')
                                                <select name="status" class="ws-commerce-select" required>
                                                    @foreach (['draft' => 'Draft', 'active' => 'Active', 'paused' => 'Paused', 'archived' => 'Archived'] as $value => $label)
                                                        <option value="{{ $value }}" @selected($campaign->status === $value)>{{ $label }}</option>
                                                    @endforeach
                                                </select>
                                                <div>
                                                    <button type="submit" class="ws-commerce-button">Update status</button>
                                                </div>
                                            </form>
                                        </div>

                                        @if ($campaignChecks !== [])
                                            <details class="ws-commerce-details">
                                                <summary>Preparation checks</summary>
                                                <div class="ws-commerce-list">
                                                    @foreach ($campaignChecks as $check)
                                                        @php
                                                            $campaignCheckClass = match ($check['status'] ?? 'warn') {
                                                                'ok' => 'ok',
                                                                'fail' => 'fail',
                                                                default => '',
                                                            };
                                                        @endphp
                                                        <div class="ws-commerce-check">
                                                            <span class="ws-commerce-pill {{ $campaignCheckClass }}">
                                                                {{ strtoupper($check['status'] ?? 'warn') }}
                                                            </span>
                                                            {{ str_replace('_', ' ', $check['key'] ?? 'check') }}
                                                            <span class="ws-commerce-code">{{ $check['summary'] ?? '' }}</span>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            </details>
                                        @endif

                                        @if ($campaignAssetSummary !== [] || $campaignPayloadPreview !== [])
                                            <details class="ws-commerce-details">
                                                <summary>Payload preview</summary>
                                                <div class="ws-commerce-list">
                                                    @if ($campaignAssetSummary !== [])
                                                        <span class="ws-commerce-code">
                                                            collection sets {{ $campaignAssetSummary['collection_product_set_count'] ?? 0 }}
                                                            · collection products {{ $campaignAssetSummary['collection_product_count'] ?? 0 }}
                                                            · post product tags {{ $campaignAssetSummary['social_post_product_tag_count'] ?? 0 }}
                                                        </span>
                                                    @endif
                                                    @if ($campaignPayloadPreview !== [])
                                                        <span class="ws-commerce-code">{{ json_encode($campaignPayloadPreview, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</span>
                                                    @endif
                                                </div>
                                            </details>
                                        @endif
                                    </div>
                                @empty
                                    <div class="ws-empty">
                                        No promotion campaigns yet. Create one to prepare collection ads, shops ads, or promoted post flows for review.
                                    </div>
                                @endforelse
                            </div>
                        </div>

                        <div class="ws-commerce-card">
                            <h3 class="ws-section-title">Product sets</h3>

                            <div class="ws-commerce-list">
                                @forelse (($commerceProductSets ?? collect()) as $productSet)
                                    @php
                                        $setProducts = ($commerceTaggableProducts ?? collect())->filter(function ($product) use ($productSet) {
                                            $catalogConnectionId = $product->catalog?->provider_connection_id;

                                            return !$catalogConnectionId || (int) $catalogConnectionId === (int) $productSet->provider_connection_id;
                                        })->values();
                                    @endphp

                                    <div class="ws-commerce-catalog">
                                        <div class="ws-commerce-head">
                                            <div>
                                                <div class="ws-commerce-title">{{ $productSet->name }}</div>
                                                <div class="ws-commerce-meta">
                                                    Meta catalog: {{ $productSet->metaCatalog?->name ?: '-' }}
                                                    @if ($productSet->providerConnection)
                                                        · Instagram: {{ $productSet->providerConnection->provider_account_name ?: $productSet->providerConnection->provider_account_id }}
                                                    @endif
                                                </div>
                                            </div>

                                            <form method="POST" action="{{ route('settings.commerce.product-sets.delete', $productSet) }}">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="ws-commerce-button" style="background:#b91c1c;">Delete</button>
                                            </form>
                                        </div>

                                        <div class="ws-commerce-meta">
                                            {{ $productSet->products_count }} product(s)
                                            · {{ str_replace('_', ' ', $productSet->meta_sync_status ?: 'not_synced') }}
                                        </div>

                                        @if ($productSet->external_product_set_id)
                                            <span class="ws-commerce-code">External product set ID: {{ $productSet->external_product_set_id }}</span>
                                        @endif

                                        @php
                                            $lastProductSetMetaSync = is_array($productSet->meta ?? null) ? ($productSet->meta['last_meta_sync'] ?? null) : null;
                                        @endphp
                                        @if (is_array($lastProductSetMetaSync))
                                            <div class="ws-commerce-meta">
                                                Last Meta sync:
                                                {{ $lastProductSetMetaSync['status'] ?? 'unknown' }}
                                                @if (!empty($lastProductSetMetaSync['synced_at']))
                                                    · {{ $lastProductSetMetaSync['synced_at'] }}
                                                @endif
                                            </div>
                                        @endif

                                        @if ($productSet->meta_sync_error)
                                            <span class="ws-commerce-code">{{ $productSet->meta_sync_error }}</span>
                                        @endif

                                        @if ($productSet->description)
                                            <div class="ws-commerce-meta">{{ $productSet->description }}</div>
                                        @endif

                                        @if ($productSet->products->isNotEmpty())
                                            <div class="ws-commerce-product-set-products">
                                                @foreach ($productSet->products as $product)
                                                    <span class="ws-commerce-pill">
                                                        {{ $product->title }}
                                                        @if ($product->sku)
                                                            · {{ $product->sku }}
                                                        @endif
                                                    </span>
                                                @endforeach
                                            </div>
                                        @endif

                                        <form method="POST" action="{{ route('settings.commerce.product-sets.sync', $productSet) }}" class="ws-commerce-sync-form">
                                            @csrf
                                            <button type="submit" class="ws-commerce-button">Sync product set to Meta</button>
                                        </form>

                                        <form method="POST" action="{{ route('settings.commerce.product-sets.products.sync', $productSet) }}" class="ws-commerce-list" style="margin-top:12px;">
                                            @csrf
                                            @method('PATCH')

                                            <select name="product_ids[]" class="ws-commerce-select is-multi" multiple size="6">
                                                @foreach ($setProducts as $product)
                                                    <option
                                                        value="{{ $product->id }}"
                                                        @selected($productSet->products->contains('id', $product->id))
                                                    >
                                                        {{ $product->title }}
                                                        @if ($product->sku)
                                                            · {{ $product->sku }}
                                                        @endif
                                                        · {{ $product->catalog?->name ?: 'Catalog' }}
                                                    </option>
                                                @endforeach
                                            </select>

                                            <div>
                                                <button type="submit" class="ws-commerce-button">Save product set items</button>
                                            </div>
                                        </form>
                                    </div>
                                @empty
                                    <div class="ws-empty">
                                        No product sets created yet.
                                    </div>
                                @endforelse
                            </div>
                        </div>

                        <div class="ws-commerce-card">
                            <h3 class="ws-section-title">Collections</h3>

                            <div class="ws-commerce-list">
                                @forelse (($commerceCollections ?? collect()) as $collection)
                                    @php
                                        $collectionProductSets = ($commerceProductSets ?? collect())
                                            ->where('catalog_id', $collection->catalog_id)
                                            ->values();
                                    @endphp

                                    <div class="ws-commerce-catalog">
                                        <div class="ws-commerce-head">
                                            <div>
                                                <div class="ws-commerce-title">{{ $collection->name }}</div>
                                                <div class="ws-commerce-meta">
                                                    Meta catalog: {{ $collection->metaCatalog?->name ?: '-' }}
                                                    @if ($collection->providerConnection)
                                                        · Instagram: {{ $collection->providerConnection->provider_account_name ?: $collection->providerConnection->provider_account_id }}
                                                    @endif
                                                </div>
                                            </div>

                                            <form method="POST" action="{{ route('settings.commerce.collections.delete', $collection) }}">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="ws-commerce-button" style="background:#b91c1c;">Delete</button>
                                            </form>
                                        </div>

                                        <div class="ws-commerce-meta">
                                            {{ $collection->product_sets_count }} product set(s)
                                            · {{ str_replace('_', ' ', $collection->meta_sync_status ?: 'not_synced') }}
                                        </div>

                                        @if ($collection->external_collection_id)
                                            <span class="ws-commerce-code">External collection ID: {{ $collection->external_collection_id }}</span>
                                        @endif

                                        @php
                                            $lastCollectionMetaSync = is_array($collection->meta ?? null) ? ($collection->meta['last_meta_sync'] ?? null) : null;
                                        @endphp
                                        @if (is_array($lastCollectionMetaSync))
                                            <div class="ws-commerce-meta">
                                                Last Meta sync:
                                                {{ $lastCollectionMetaSync['status'] ?? 'unknown' }}
                                                @if (!empty($lastCollectionMetaSync['synced_at']))
                                                    · {{ $lastCollectionMetaSync['synced_at'] }}
                                                @endif
                                            </div>
                                        @endif

                                        @if ($collection->meta_sync_error)
                                            <span class="ws-commerce-code">{{ $collection->meta_sync_error }}</span>
                                        @endif

                                        @if ($collection->description)
                                            <div class="ws-commerce-meta">{{ $collection->description }}</div>
                                        @endif

                                        @if ($collection->productSets->isNotEmpty())
                                            <div class="ws-commerce-product-set-products">
                                                @foreach ($collection->productSets as $productSet)
                                                    <span class="ws-commerce-pill">
                                                        {{ $productSet->name }}
                                                        · {{ $productSet->products->count() }} products
                                                    </span>
                                                @endforeach
                                            </div>
                                        @endif

                                        <form method="POST" action="{{ route('settings.commerce.collections.sync', $collection) }}" class="ws-commerce-sync-form">
                                            @csrf
                                            <button type="submit" class="ws-commerce-button">Sync collection to Meta</button>
                                        </form>

                                        <form method="POST" action="{{ route('settings.commerce.collections.product-sets.sync', $collection) }}" class="ws-commerce-list" style="margin-top:12px;">
                                            @csrf
                                            @method('PATCH')

                                            <select name="product_set_ids[]" class="ws-commerce-select is-multi" multiple size="6">
                                                @foreach ($collectionProductSets as $productSet)
                                                    <option
                                                        value="{{ $productSet->id }}"
                                                        @selected($collection->productSets->contains('id', $productSet->id))
                                                    >
                                                        {{ $productSet->name }} · {{ $productSet->products_count ?? $productSet->products->count() }} products
                                                    </option>
                                                @endforeach
                                            </select>

                                            <div>
                                                <button type="submit" class="ws-commerce-button">Save collection product sets</button>
                                            </div>
                                        </form>
                                    </div>
                                @empty
                                    <div class="ws-empty">
                                        No collections created yet.
                                    </div>
                                @endforelse
                            </div>
                        </div>
                    </div>
                @elseif ($section === 'automation')
                    <style>
                        .ws-automation-list {
                            display: grid;
                            gap: 16px;
                            margin-top: 18px;
                        }

                        .ws-automation-card {
                            border: 1px solid #e2e8f0;
                            border-radius: 14px;
                            background: #fff;
                            padding: 18px;
                        }

                        .ws-automation-account {
                            margin: 0;
                            color: #0f172a;
                            font-size: 16px;
                            font-weight: 800;
                        }

                        .ws-automation-account-meta,
                        .ws-automation-help {
                            margin-top: 5px;
                            color: #64748b;
                            font-size: 12px;
                            line-height: 1.6;
                        }

                        .ws-automation-grid {
                            display: grid;
                            grid-template-columns: repeat(2, minmax(0, 1fr));
                            gap: 14px;
                            margin-top: 16px;
                        }

                        .ws-automation-rule {
                            border: 1px solid #e2e8f0;
                            border-radius: 12px;
                            background: #f8fafc;
                            padding: 14px;
                        }

                        .ws-automation-toggle {
                            display: flex;
                            align-items: center;
                            gap: 9px;
                            color: #1e293b;
                            font-size: 13px;
                            font-weight: 800;
                        }

                        .ws-automation-textarea {
                            box-sizing: border-box;
                            width: 100%;
                            min-height: 112px;
                            margin-top: 12px;
                            resize: vertical;
                            border: 1px solid #cbd5e1;
                            border-radius: 10px;
                            background: #fff;
                            padding: 10px 12px;
                            color: #0f172a;
                            font: inherit;
                            font-size: 13px;
                            line-height: 1.55;
                        }

                        .ws-automation-textarea:focus {
                            border-color: #818cf8;
                            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.12);
                            outline: none;
                        }

                        .ws-automation-actions {
                            display: flex;
                            justify-content: flex-end;
                            margin-top: 14px;
                        }

                        .ws-automation-button {
                            border: 0;
                            border-radius: 10px;
                            background: #4f46e5;
                            padding: 10px 16px;
                            color: #fff;
                            font-size: 13px;
                            font-weight: 800;
                            cursor: pointer;
                        }

                        .ws-automation-notice {
                            margin-top: 16px;
                            border-radius: 10px;
                            padding: 10px 12px;
                            font-size: 13px;
                            font-weight: 700;
                        }

                        .ws-automation-notice.is-success {
                            border: 1px solid #bbf7d0;
                            background: #f0fdf4;
                            color: #166534;
                        }

                        .ws-automation-notice.is-error {
                            border: 1px solid #fecaca;
                            background: #fef2f2;
                            color: #b91c1c;
                        }

                        @media (max-width: 820px) {
                            .ws-automation-grid {
                                grid-template-columns: 1fr;
                            }
                        }
                    </style>

                    @if (session('status'))
                        <div class="ws-automation-notice is-success">
                            {{ session('status') }}
                        </div>
                    @endif

                    @if ($errors->any())
                        <div class="ws-automation-notice is-error">
                            {{ $errors->first() }}
                        </div>
                    @endif

                    <div class="ws-automation-list">
                        @forelse ($automationConnections as $connection)
                            @php
                                $dmAutomation = is_array($connection->meta['dm_automation'] ?? null)
                                    ? $connection->meta['dm_automation']
                                    : [];
                                $storyReply = is_array($dmAutomation['story_reply'] ?? null)
                                    ? $dmAutomation['story_reply']
                                    : [];
                                $commentDm = is_array($dmAutomation['comment_dm'] ?? null)
                                    ? $dmAutomation['comment_dm']
                                    : [];
                                $usesOldAutomationValues = (string) old('automation_connection_id') === (string) $connection->id;
                            @endphp

                            <form method="POST" action="{{ route('settings.automation.instagram.update', $connection) }}" class="ws-automation-card">
                                @csrf
                                @method('PATCH')
                                <input type="hidden" name="automation_connection_id" value="{{ $connection->id }}">

                                <h3 class="ws-automation-account">
                                    {{ $connection->provider_account_name ?: 'Instagram account' }}
                                </h3>
                                <div class="ws-automation-account-meta">
                                    Instagram account ID: {{ $connection->provider_account_id }}
                                </div>

                                <div class="ws-automation-grid">
                                    <div class="ws-automation-rule">
                                        <label class="ws-automation-toggle" for="storyReplyEnabled{{ $connection->id }}">
                                            <input type="hidden" name="story_reply_enabled" value="0">
                                            <input
                                                id="storyReplyEnabled{{ $connection->id }}"
                                                type="checkbox"
                                                name="story_reply_enabled"
                                                value="1"
                                                @checked($usesOldAutomationValues ? old('story_reply_enabled') : ($storyReply['enabled'] ?? false))
                                            >
                                            Send a DM after every story reply
                                        </label>
                                        <div class="ws-automation-help">
                                            Runs only for inbound Instagram story replies. Regular direct messages are ignored.
                                        </div>
                                        <textarea
                                            name="story_reply_message"
                                            class="ws-automation-textarea"
                                            maxlength="1000"
                                            placeholder="Write the automatic story reply message"
                                        >{{ $usesOldAutomationValues ? old('story_reply_message') : ($storyReply['message'] ?? '') }}</textarea>
                                    </div>

                                    <div class="ws-automation-rule">
                                        <label class="ws-automation-toggle" for="commentDmEnabled{{ $connection->id }}">
                                            <input type="hidden" name="comment_dm_enabled" value="0">
                                            <input
                                                id="commentDmEnabled{{ $connection->id }}"
                                                type="checkbox"
                                                name="comment_dm_enabled"
                                                value="1"
                                                @checked($usesOldAutomationValues ? old('comment_dm_enabled') : ($commentDm['enabled'] ?? false))
                                            >
                                            Send a private DM after every new comment
                                        </label>
                                        <div class="ws-automation-help">
                                            Sends a private reply to the comment author and records the outbound message in Inbox.
                                        </div>
                                        <textarea
                                            name="comment_dm_message"
                                            class="ws-automation-textarea"
                                            maxlength="1000"
                                            placeholder="Write the automatic comment DM"
                                        >{{ $usesOldAutomationValues ? old('comment_dm_message') : ($commentDm['message'] ?? '') }}</textarea>
                                    </div>
                                </div>

                                <div class="ws-automation-actions">
                                    <button type="submit" class="ws-automation-button">
                                        Save automation
                                    </button>
                                </div>
                            </form>
                        @empty
                            <div class="ws-placeholder">
                                Connect an Instagram account before configuring direct-message automation.
                            </div>
                        @endforelse
                    </div>
                @elseif (in_array($section, ['ai', 'billing', 'security', 'advanced'], true))
                    <div class="ws-placeholder">
                        {{ $sections[$section] }} will be added soon.
                    </div>
                @else
                    <div class="ws-placeholder">
                        This area is intentionally kept as a placeholder so the settings architecture is ready from now on.
                    </div>
                @endif
            </section>
        </div>
    </div>
</x-app-layout>
