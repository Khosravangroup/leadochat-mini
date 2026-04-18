# Social Module — Instagram Architecture

## Product split

### 1) Inbox
Only for:
- DMs
- inbound/outbound messages
- assignment
- tags / departments / agents

### 2) Social
Only for:
- posts
- comments
- stories
- moderation
- reply / delete / hide
- reply by DM

This keeps Inbox focused on conversations and makes Social the dedicated content management area.

---

## Final Social structure

Inside `Social`, create these tabs:

### Posts
- post / reel list
- single post details
- comment and like counts
- open comments

### Comments
- inbox-like comment list
- filter by post
- filter by unreplied
- public reply
- hide / unhide
- delete when allowed
- reply by DM

### Stories
- published story list
- publish story
- insights later

---

## Reply comment via DM

This action must be separate from normal public reply.

For each comment, actions should be:
- Reply publicly
- Reply via DM
- Hide
- Unhide
- Delete if endpoint is allowed

### Backend flow for Reply via DM
1. Comment is selected
2. Resolve the comment author provider/user id
3. If a DM thread already exists:
   - use the existing conversation
4. If no DM thread exists:
   - create a new Instagram conversation
5. Send the message through the messaging service
6. The conversation must also be visible in Inbox

Result:
- `Social` and `Inbox` stay separate
- `Reply via DM` becomes the bridge between them

---

## Backend modules

Use these services:

- `InstagramService`
- `InstagramMessagingService`
- `InstagramContentService`
- `InstagramCommentService`
- `InstagramStoryService`
- `InstagramSocialSyncService`

### Responsibilities

#### `InstagramContentService`
- sync posts / reels
- get feed
- get single media
- publish post

#### `InstagramCommentService`
- sync comments
- public reply
- hide / unhide
- delete if allowed
- resolve comment author ids

#### `InstagramStoryService`
- publish story
- list stories if needed

#### `InstagramSocialSyncService`
- transform webhook events into social read models
- update post/comment counters
- push realtime events

---

## Database tables

### `social_posts`
Fields:
- `workspace_id`
- `provider_connection_id`
- `provider`
- `provider_media_id`
- `media_type`
- `caption`
- `permalink`
- `media_url`
- `thumbnail_url`
- `posted_at`
- `comments_count`
- `like_count`
- `status`
- `raw`

### `social_comments`
Fields:
- `workspace_id`
- `provider_connection_id`
- `provider`
- `provider_media_id`
- `provider_comment_id`
- `parent_provider_comment_id`
- `provider_user_id`
- `username`
- `text`
- `status`
- `is_hidden`
- `commented_at`
- `replied_publicly_at`
- `replied_via_dm_at`
- `raw`

### `social_stories`
Fields:
- `workspace_id`
- `provider_connection_id`
- `provider_story_id`
- `media_url`
- `thumbnail_url`
- `posted_at`
- `expires_at`
- `status`
- `raw`

---

## Route structure

### Page routes
- `/social`
- `/social/instagram`
- `/social/instagram/posts`
- `/social/instagram/comments`
- `/social/instagram/stories`

### API / action routes
- `GET /social/instagram/posts/feed`
- `GET /social/instagram/posts/{post}`
- `GET /social/instagram/posts/{post}/comments`

- `POST /social/instagram/comments/{comment}/reply`
- `POST /social/instagram/comments/{comment}/reply-dm`
- `POST /social/instagram/comments/{comment}/hide`
- `POST /social/instagram/comments/{comment}/unhide`
- `DELETE /social/instagram/comments/{comment}`

- `POST /social/instagram/stories/publish`
- `POST /social/instagram/posts/publish`

---

## Realtime design

To make Social realtime:

1. Meta webhook
2. `WebhookEvent`
3. job processing
4. update `social_posts` / `social_comments`
5. websocket event
6. frontend Social tabs update live

### Suggested event names
- `social.instagram.comment.created`
- `social.instagram.comment.updated`
- `social.instagram.comment.hidden`
- `social.instagram.post.updated`
- `social.instagram.story.updated`

---

## UI proposal

### Social page
Use three tabs:

#### Posts
- grid / card list
- thumbnail
- caption
- counts
- open details

#### Comments
- comment stream
- small post preview
- action bar:
  - Reply
  - Reply via DM
  - Hide
  - Unhide
  - Delete

#### Stories
- story list
- publish form
- status list

---

## Implementation order

### Phase 1
`Social shell + DB schema`
- Social menu
- `Posts / Comments / Stories` tabs
- migrations for social tables

### Phase 2
`Posts feed sync`
- fetch posts
- store in `social_posts`
- render posts UI

### Phase 3
`Comments sync + moderation`
- fetch comments
- store in `social_comments`
- public reply
- hide / unhide
- delete if allowed

### Phase 4
`Reply via DM bridge`
- from comment to DM
- send through messaging service
- link conversation to inbox

### Phase 5
`Stories publish/manage`
- publish story
- story list / status

### Phase 6
`Realtime hardening`
- webhook projections
- websocket push
- retry / idempotency

---

## Final summary

- `Inbox` = only DMs
- `Social` = posts + comments + stories
- `Reply via DM` = the bridge between Social and Inbox

## Best next step
Start with:
1. building the `Social` page shell
2. adding migrations for `social_posts / social_comments / social_stories`
3. then implementing fetch/sync services

## First file to open for implementation
- `routes/web.php`
