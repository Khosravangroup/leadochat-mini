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
                            @if (in_array($key, ['automation', 'ai', 'billing', 'security', 'advanced'], true))
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
                    @elseif (in_array($section, ['automation', 'ai', 'billing', 'security', 'advanced'], true))
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

                            const buildTagRow = (tag) => {
                                const wrapper = document.createElement('div');
                                wrapper.className = 'ws-tag-row';
                                wrapper.setAttribute('data-tag-id', String(tag.id));
                                wrapper.innerHTML = `
                                    <div class="ws-tag-chip-wrap">
                                        <span class="ws-tag-chip" style="background: ${tag.color}20; border-color: ${tag.color}55;">
                                            <span class="ws-tag-label">${tag.name}</span>
                                        </span>
                                    </div>

                                    <div class="ws-tag-actions">
                                        <button
                                            type="button"
                                            class="ws-tag-delete"
                                            data-delete-url="/settings/tags/${tag.id}"
                                            title="Remove tag"
                                        >
                                            Remove
                                        </button>
                                    </div>
                                `;
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

                            const buildDepartmentRow = (department) => {
                                const wrapper = document.createElement('div');
                                wrapper.className = 'ws-department-row';
                                wrapper.setAttribute('data-department-id', String(department.id));
                                wrapper.innerHTML = `
                                    <div class="ws-department-chip-wrap">
                                        <span class="ws-department-chip" style="background: ${department.color}20; border-color: ${department.color}55;">
                                            <span class="ws-department-label">${department.name}</span>
                                        </span>
                                    </div>

                                    <div class="ws-department-actions">
                                        <button
                                            type="button"
                                            class="ws-department-delete"
                                            data-delete-url="/settings/departments/${department.id}"
                                            title="Remove department"
                                        >
                                            Remove
                                        </button>
                                    </div>
                                `;
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
                                        The new teammate will be added directly to this workspace and can log in immediately.
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
                @elseif (in_array($section, ['automation', 'ai', 'billing', 'security', 'advanced'], true))
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
