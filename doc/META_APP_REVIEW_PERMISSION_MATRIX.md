# Meta App Review software permission matrix

Code inventory date: 23 September 2026. The Instagram diagnostics correction is
deployed from `main` revision `df7a0d266d0b97510071587adf8c69d31915a51a`
after the owner reconnected an authorized test account. This is an
application-code and live-boundary inventory, not proof of a Meta grant or App
Review approval. Meta dashboard inspection remains outside this task.

The public marketing site, free-start links, and public registration remain
unchanged. The owner's personal use of the application is context, not a request
to remove those public product paths.

## How scopes enter the application

- `INSTAGRAM_SCOPES` is sent as the `scope` value by
  `InstagramLoginService::buildAuthorizationUrl()` during Instagram OAuth. The default has
  five Instagram Business scopes.
- `META_COMMERCE_REVIEW_SCOPES` is reserved for a future, separately authenticated
  Facebook Login/Marketing API review. It defaults to an empty value and is not
  added to the Instagram OAuth scope string. A configured or locally recorded
  scope is not evidence that the current token has that grant.
- A mock HTTP test proves request/response handling, not live provider access.

## Default configured scope inventory

| Scope | Code-backed user action and outbound boundary | Automated evidence | Current gap |
| --- | --- | --- | --- |
| `instagram_business_basic` | Connect account; identity read in `InstagramTokenExchangeService` and `MetaCommerceDiagnosticsService`; media read in `InstagramContentService` | `InstagramOAuthCallbackTest` covers callback boundaries with mocked exchange; `MetaCommerceDiagnosticsTest` covers direct Instagram diagnostics routing | Live read-only identity and media checks succeeded on the owner-authorized test account; the provider grant still requires Meta review |
| `instagram_business_manage_messages` | Inbox send through `InstagramMessagingService`; signed inbound webhooks through `ProcessInstagramWebhookEvent` | `InstagramMessagingServiceTest`, `InboxCatalogProductTest`, webhook unit tests | No live DM send/receive proof; narrow send/receive edge cases lack direct tests |
| `instagram_business_manage_comments` | Social comment read, reply, hide/unhide, and delete through `InstagramCommentService` and `SocialController` | `InstagramReviewApiJourneyTest` covers reply/hide requests and provider-error redaction | Add read/delete and route-authorization edge tests; live proof deferred |
| `instagram_business_content_publish` | Post and story publish through `InstagramContentService` and `InstagramStoryService` | `InstagramContentServiceTest` covers post product tags; `InstagramReviewApiJourneyTest` covers story create/status/publish | Add video/failure and route-authorization edge tests; live proof deferred |
| `instagram_business_manage_insights` | Owner-only `GET /social/instagram/insights` reads account reach, views, and interactions through `graph.instagram.com` using the connected Instagram token | `InstagramInsightsTest` covers request, workspace isolation, unavailable metrics, and safe errors | The live account-level request succeeded; Meta review approval is still pending and media-level insights are not implemented |
| `business_management` | Deferred from this submission; commerce discovery contains a mocked `/me/businesses` path | `MetaCommerceDiscoveryTest` with mocked provider responses | Requires a separate compatible login/token contract before a future review |
| `catalog_management` | Deferred from this submission; catalog services remain local groundwork | `MetaCommerceDiscoveryTest`, `MetaCatalogProductSyncTest`, `MetaCommerceStructureSyncTest` | Requires a separate compatible login/token contract before a future review |
| `ads_read` | Deferred from this submission; no ads/ad-account read API call exists | `MetaCommercePromotionsTest` covers local preparation, not ads read | Not requested in the current Instagram-only review |
| `ads_management` | Deferred from this submission; no ads/ad-account write API call exists | `MetaCommercePromotionsTest` covers local preparation, not ads write | Not requested in the current Instagram-only review |

The account Insights request follows Meta's [Instagram User Insights reference](https://developers.facebook.com/docs/instagram-platform/api-reference/instagram-user/insights/): Instagram Login uses an Instagram User token, `graph.instagram.com`, and `instagram_business_basic` plus `instagram_business_manage_insights`. The Marketing API uses a separate ad-account token and `graph.facebook.com` according to Meta's [official Marketing API collection](https://www.postman.com/meta/facebook-marketing-api/documentation/0zr4mes/facebook-marketing-api-mapi). The current Instagram Login token and commerce diagnostic scope list do not establish ads access. A live grant is still unverified.

## Ordered software work

1. Keep the existing OAuth callback candidate and its single-use state regression.
2. Extend the new mocked comment/story API tests to read/delete, video/failure,
   and route-authorization edges. Prepare owner-visible proof for those already
   implemented paths. Preserve workspace/role boundaries and secret redaction.
3. Capture the already verified account Insights journey in the reviewer
   screencast without exposing tokens or unrelated account data.
4. Decide the intended ads product workflow and obtain the exact API contract
   before implementing ads read/write calls. Local campaign previews must not be
   presented as completed ads API operations.
5. Resolve how commerce grants are obtained and validated for the token actually
   used by commerce services. Do not silently merge distinct OAuth products or
   add scopes without checking the provider contract.
6. Verify each needed permission with the authorized test account and capture
   minimal, redacted reviewer evidence after deployment.

The commerce diagnostic and exported review packet include
`permissions.without_demonstrated_api_journey` for the commerce review list and
`permissions.instagram_without_demonstrated_api_journey` for Instagram OAuth
scopes. A configured scope with no code-backed API journey makes the local
readiness summary `blocked`, even if the permission response says `granted`.
This is a software-evidence guard, not a Meta approval decision. The current
Instagram-only default leaves the commerce review list empty. The Insights journey
is code-backed but still unverified against Meta. In this default state, building
the Instagram App Review evidence packet does not call deferred commerce APIs and
exports only aggregate workspace counts.

## Direct Instagram diagnostics contract

- Identity uses `GET https://graph.instagram.com/me`.
- Webhook subscription state uses
  `GET https://graph.instagram.com/{version}/{instagram-user-id}/subscribed_apps`.
- Access tokens are sent in the authorization header and are redacted from stored
  diagnostic errors.
- `ProviderPermission` rows remain local audit evidence. Their saved status is
  not converted into a live provider grant.
- Facebook permission and catalog calls are not attempted with a direct Instagram
  Login token. Catalog checks use `not_applicable` until a separate Facebook
  commerce authorization exists.
- `MetaCommerceDiagnosticsTest` covers both saved-mode and token-scope routing,
  requested and stale local permission states, header token transport, separate
  catalog authorization, and the versioned review-packet contract. These mocked
  responses prove application behavior only.

## Production verification on 23 September 2026

The owner completed an interactive reconnect with a test page. Direct read-only
identity, media, subscribed-app, comments, and account-Insights journeys returned
successful provider responses. Pull requests `#52` and `#53` corrected the
API-family mismatch, and pull requests `#54` and `#55` corrected canonical
identity selection when Meta returns both `id` and `user_id`.

The exact production revision shown above was then verified against provider
connection `28`. Identity and subscribed-app checks both returned HTTP `200` with
`outcome=ok`, the diagnostic account identifier matched the connected provider
identifier, and the live subscribed fields included `messages` and `comments`.
Channel health and webhook-subscription readiness were both `ok`. Saved webhook
fields matched the live response. Provider-granted permissions remained empty,
while local permission rows remained explicitly `requested`; the application did
not convert local evidence into provider grants.

The overall commerce review summary remains `blocked` because no Meta catalog is
discovered for this direct Instagram connection. Catalog access is a warning that
requires separate Facebook commerce authorization, not a failed Instagram API
probe. No outbound message, moderation action, publication, catalog mutation, or
other provider mutation was attempted.
