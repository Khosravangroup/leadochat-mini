# Leadochat Mini — Cursor Handoff

## Goal of this handoff
This document summarizes what was implemented, what is currently stable, what is broken, which files Cursor should inspect first, and the exact next steps to continue safely.

---

## What has been completed so far

### 1) Inbox UI work already completed
The following Inbox features were implemented or improved during this session history:

- Removed flash-message style confirmations from the Inbox flow
- Message status UI moved toward tick-style status indicators instead of plain text labels
- Archive conversation flow added
- Trash / soft-delete conversation flow added
- Restore conversation flow added
- Archived and Trash views added to Inbox
- Note-saving UI was implemented and later stabilized
- Attachment send flow was added
- Multi-file preview work was partially implemented
- Voice message mock / recording UI work was explored
- Emoji picker UI was improved
- Scroll behavior for the chat panel was improved
- Right sidebar height / scroll behavior was improved

### 2) Workspace Settings foundation created
A new Settings area was added to the dashboard so shared workspace configuration can live there instead of being managed inside Inbox.

Implemented:

- `WorkspaceSettingsController`
- `/settings` route
- Settings page view
- Settings link in dashboard navigation
- Sections scaffold in Settings

### 3) Workspace Tags management created in Settings
A full Settings > Tags management flow was created and stabilized.

Implemented:

- Create workspace tags
- Delete workspace tags
- Simple UI for tag management
- Separate Settings-first architecture so Inbox only selects tags, while Settings manages them

### 4) Backend for Inbox tag assignment created
The backend route and controller logic for assigning workspace tags to a conversation already exists.

Implemented:

- Workspace tags list endpoint / view data loading
- Conversation tag save endpoint
- Sync-based save logic
- Controller fix so save no longer crashes with `Call to a member function map() on null`

---

## What is stable right now

### Stable
- Settings page opens
- Settings navigation works
- Settings > Tags page opens
- Creating workspace tags works
- Deleting workspace tags works
- Inbox page opens again
- Conversation messages render again
- Saving internal notes works
- Backend tag save endpoint no longer crashes

### Not stable / still needs work
The current unstable area is **Inbox tag selection persistence**.

Symptoms seen:
- Tags can appear in Inbox
- Selection behavior was inconsistent
- Save behavior was partially fixed, but after refresh tags did not remain correctly in the rendered Inbox state
- `resources/views/inbox/index.blade.php` became noisy and risky because multiple JS patches were layered onto a file that already had a damaged / truncated composer script

Important note:
- The Inbox Blade file currently appears to contain a **broken or truncated JavaScript block** around composer preview logic (`formatBytes(...)` area). That means UI behavior in the rest of the page may be unreliable until that script is cleaned up.

---

## Most important architecture decision already made

### Settings owns tag management
This was an intentional design decision and should be preserved:

- **Settings > Tags** = create / delete workspace tags
- **Inbox** = only assign existing workspace tags to a conversation

Do **not** move tag creation back into Inbox.

---

## Files Cursor should inspect first
Upload these files first into Cursor.

### Highest priority
1. `resources/views/inbox/index.blade.php`
2. `app/Http/Controllers/InboxController.php`
3. `resources/views/settings/index.blade.php`
4. `app/Http/Controllers/WorkspaceSettingsController.php`
5. `routes/web.php`

### Also useful for context
6. `resources/views/layouts/navigation.blade.php`
7. `app/Models/WorkspaceTag.php`
8. `app/Models/Workspace.php`
9. `app/Models/Conversation.php`
10. Any migration related to:
   - `workspace_tags`
   - pivot table between conversations and workspace tags
   - conversations note field

If Cursor needs the full domain context for tags, also upload:

11. Any migration that created `workspace_tags`
12. Any migration that created the pivot relation table
13. Any policy / middleware if conversation-workspace access rules exist outside controller methods

---

## Exact files to upload to Cursor
If you want the smallest useful upload pack, upload exactly these:

```text
app/Http/Controllers/InboxController.php
app/Http/Controllers/WorkspaceSettingsController.php
app/Models/Conversation.php
app/Models/Workspace.php
app/Models/WorkspaceTag.php
resources/views/inbox/index.blade.php
resources/views/settings/index.blade.php
resources/views/layouts/navigation.blade.php
routes/web.php
```

If you want Cursor to be safer and understand the DB side too, also upload:

```text
database/migrations/*workspace_tags*.php
database/migrations/*conversation*tag*.php
database/migrations/*conversations*.php
```

---

## Current technical status by file

### `app/Http/Controllers/WorkspaceSettingsController.php`
Status: mostly good

What it does now:
- Renders settings page
- Loads workspace tags for the tags section
- Creates a workspace tag
- Updates a workspace tag (older logic may still be present but tag UI was simplified later)
- Deletes a workspace tag

What Cursor should verify:
- Remove dead code if update flow is no longer used by UI
- Ensure route names match current Blade usage
- Confirm no unnecessary legacy logic remains

### `resources/views/settings/index.blade.php`
Status: mostly good after simplification

What it does now:
- Renders Settings shell
- Renders Tags section UI
- Supports Add tag
- Supports Remove tag

What Cursor should verify:
- Confirm no leftover legacy classes remain from old tag editing UI
- Confirm Settings > Tags JS is minimal and clean
- Keep English-only UI strings

### `app/Http/Controllers/InboxController.php`
Status: mostly good, but should be reviewed

What it does now:
- Loads inbox conversations
- Loads selected conversation
- Loads workspace tags
- Saves internal notes
- Saves assigned conversation tags using sync
- Handles archive / trash / restore / messages / attachments / mock incoming

What Cursor should verify:
- `index()` should always load the selected conversation from the already eager-loaded collection when possible
- `selectedConversation` should have correct `tags` relation available for rendering after refresh
- ensure workspace filtering is correct
- ensure conversation tags are actually attached in DB and reloaded correctly for the selected conversation

### `resources/views/inbox/index.blade.php`
Status: risky / needs cleanup first

This is the main unstable file.

Known problems:
- It has accumulated many UI patches
- It appears to include a broken or truncated JS block in the composer area
- Multiple responsibilities are packed into one huge Blade file
- Tag UI logic was added on top of already unstable script structure

What Cursor should do first in this file:
1. Clean the broken script blocks
2. Remove duplicated or legacy tags JS
3. Isolate Inbox tag selector logic into a clean standalone block
4. Make sure selected tags render from actual persisted DB state after refresh
5. Keep message rendering untouched as much as possible

---

## What Cursor should do next

### Phase 1 — stabilize `resources/views/inbox/index.blade.php`
Priority: highest

Cursor should:
- Inspect the bottom scripts carefully
- Find broken/truncated JS near composer preview / `formatBytes`
- Remove corrupted duplicate JS blocks
- Keep only one clean script per behavior area
- Ensure the file closes correctly with:
  - balanced Blade directives
  - closed script tags
  - `</x-app-layout>`

### Phase 2 — make Inbox tag assignment reliable
Priority: highest after script cleanup

Expected behavior:
- Workspace tags appear in right sidebar
- Clicking a tag toggles selected state
- Search filters tags
- Save sends selected tag ids to backend
- Refresh shows the exact saved tag set from DB

Cursor should verify:
- selected conversation being rendered after refresh is the same conversation that was saved
- `$selectedConversation->tags` is loaded when page renders
- no stale route-model-bound instance is used instead of the reloaded one

### Phase 3 — optional cleanup refactor
Priority: medium

Recommended refactor:
- move large inline JS blocks out of Blade into dedicated assets later
- split Inbox sidebar / center / right panel into partials
- split settings sections into partials

---

## Suggested debugging checklist for Cursor

### For Inbox tags not persisting visually after refresh
1. Confirm DB row exists in pivot table after save
2. Confirm selected conversation id on refresh is the same one saved
3. Confirm `tags` relation is eager-loaded for selected conversation
4. Confirm Blade uses `$selectedConversation->tags`, not stale request state
5. Confirm no second JS block overrides visual state on page load
6. Confirm tag color rendering uses the workspace tag color from DB

### For broken Inbox JS
1. Search for duplicate `DOMContentLoaded` blocks
2. Search for duplicate tags logic
3. Search for truncated string literals / template strings
4. Search for partial function definitions like broken `formatBytes`
5. Confirm all `<script>` tags are properly closed

---

## Safe next milestone
The safest next milestone is:

### "Finish Inbox tag assignment cleanly"
Definition of done:
- tags are managed only in Settings
- Inbox shows workspace tags with correct colors
- selecting tags works
- saving works
- refresh preserves assigned tags
- no JS errors in console from Inbox tags logic

---

## Notes for Cursor / next engineer
- Do not rewrite the entire Inbox page unless strictly necessary
- First stabilize the current Blade and scripts
- Prefer minimal fixes over sweeping UI rewrites
- Preserve Settings-first ownership of shared tag configuration
- English-only UI strings inside the panel

---

## Short summary of what was done in plain language
Up to this point, the project gained a real Settings area, a working Tags management page inside Settings, backend support for assigning tags to conversations, and a partially connected Inbox tag selector. The remaining blocker is not the data model anymore; it is mostly the stability and cleanup of the large `inbox/index.blade.php` file so the saved tags render consistently after refresh.
