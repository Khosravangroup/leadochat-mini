<?php

namespace App\Http\Controllers;

use App\Models\Catalog;
use App\Models\CatalogCollection;
use App\Models\CatalogProduct;
use App\Models\CatalogProductSet;
use App\Models\CommerceOrder;
use App\Models\CommercePromotionCampaign;
use App\Models\WorkspaceTag;
use App\Models\ProviderConnection;
use App\Models\SocialPost;
use App\Models\User;
use App\Models\WorkspaceDepartment;
use Illuminate\Auth\Events\Registered;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

class WorkspaceSettingsController extends Controller
{
    private const LABEL_NAME_RULES = ['required', 'string', 'max:80', 'not_regex:/\A\s*\z/u'];

    private const LABEL_COLOR_RULES = ['required', 'string', 'regex:/\A#[0-9A-Fa-f]{6}\z/'];

    public function index(Request $request): View
    {
        $user = $request->user();
        $workspace = $user?->currentWorkspace();
        $section = $request->query('section', 'general');

        $sections = [
            'general' => 'General',
            'tags' => 'Tags',
            'departments' => 'Departments',
            'team' => 'Team',
            'inbox' => 'Inbox',
            'channels' => 'Channels',
            'catalogs' => 'Catalogs',
            'commerce' => 'Commerce',
            'automation' => 'Automation',
            'ai' => 'AI',
            'billing' => 'Billing',
            'security' => 'Security',
            'advanced' => 'Advanced',
        ];

        if (!array_key_exists($section, $sections)) {
            $section = 'general';
        }

        $workspaceTags = collect();
        $workspaceDepartments = collect();
        $workspaceMembers = collect();
        $providerConnections = collect();
        $providerCards = collect();
        $catalogs = collect();
        $catalogProviderConnections = collect();
        $commerceConnections = collect();
        $metaCommerceCatalogs = collect();
        $commerceSourceCatalogs = collect();
        $commerceProductSets = collect();
        $commerceTaggableProducts = collect();
        $commerceCollections = collect();
        $commerceOrders = collect();
        $commerceOrderStats = [];
        $commercePromotionCampaigns = collect();
        $commercePromotablePosts = collect();
        $commercePromotionStats = [];

        if ($workspace && $section === 'tags') {
            $workspaceTags = WorkspaceTag::query()
                ->where('workspace_id', $workspace->id)
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get();
        }

        if ($workspace && $section === 'departments') {
            $workspaceDepartments = WorkspaceDepartment::query()
                ->where('workspace_id', $workspace->id)
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get();
        }

        if ($workspace && $section === 'team') {
            $workspaceMembers = $workspace->members()
                ->orderBy('users.name')
                ->orderBy('users.email')
                ->get();
        }

        if ($workspace && $section === 'channels') {
            $providerConnections = $workspace->providerConnections()
                ->latest('id')
                ->get();

            $providerCards = collect([
                [
                    'key' => 'instagram',
                    'title' => 'Instagram',
                    'subtitle' => 'Connect Instagram messaging and account channels for this workspace.',
                    'account_type' => 'Business / Professional',
                    'connect_url' => route('connections.instagram.redirect'),
                    'is_ready' => true,
                ],
                [
                    'key' => 'facebook',
                    'title' => 'Facebook',
                    'subtitle' => 'Connect Facebook pages and messaging channels for this workspace.',
                    'account_type' => 'Page',
                    'connect_url' => null,
                    'is_ready' => false,
                ],
                [
                    'key' => 'whatsapp',
                    'title' => 'WhatsApp',
                    'subtitle' => 'Connect WhatsApp channels and phone-based messaging for this workspace.',
                    'account_type' => 'Phone / Business',
                    'connect_url' => null,
                    'is_ready' => false,
                ],
            ]);
        }

        if ($workspace && $section === 'catalogs') {
            $catalogs = Catalog::query()
                ->where('workspace_id', $workspace->id)
                ->with([
                    'providerConnection',
                    'products' => fn ($query) => $query
                        ->with([
                            'offers' => fn ($offerQuery) => $offerQuery
                                ->with('marketOverride')
                                ->orderBy('priority')
                                ->orderByDesc('id'),
                            'marketOverrides' => fn ($marketQuery) => $marketQuery
                                ->with(['offers' => fn ($offerQuery) => $offerQuery
                                    ->orderBy('priority')
                                    ->orderByDesc('id')])
                                ->orderBy('target_country')
                                ->orderBy('content_language'),
                        ])
                        ->orderByDesc('is_active')
                        ->orderBy('title'),
                ])
                ->orderBy('name')
                ->get();

            $catalogProviderConnections = ProviderConnection::query()
                ->where('workspace_id', $workspace->id)
                ->where('provider', 'instagram')
                ->where('status', 'connected')
                ->orderBy('provider_account_name')
                ->get();
        }

        if ($workspace && $section === 'commerce') {
            $commerceConnections = ProviderConnection::query()
                ->where('workspace_id', $workspace->id)
                ->where('provider', 'instagram')
                ->where('status', 'connected')
                ->with(['catalogs' => fn ($query) => $query
                    ->where('source', 'meta')
                    ->orderBy('name')])
                ->orderBy('provider_account_name')
                ->get();

            $metaCommerceCatalogs = Catalog::query()
                ->where('workspace_id', $workspace->id)
                ->where('source', 'meta')
                ->with('providerConnection')
                ->orderBy('name')
                ->get();

            $commerceSourceCatalogs = Catalog::query()
                ->where('workspace_id', $workspace->id)
                ->where('status', 'active')
                ->where('source', '!=', 'meta')
                ->withCount(['products' => fn ($query) => $query->where('is_active', true)])
                ->orderBy('name')
                ->get();

            $commerceProductSets = CatalogProductSet::query()
                ->where('workspace_id', $workspace->id)
                ->with([
                    'metaCatalog.providerConnection',
                    'products' => fn ($query) => $query
                        ->with('catalog.providerConnection')
                        ->orderBy('title'),
                ])
                ->withCount('products')
                ->orderBy('name')
                ->get();

            $commerceTaggableProducts = CatalogProduct::query()
                ->where('is_active', true)
                ->whereIn('meta_sync_status', ['queued', 'synced'])
                ->whereHas('catalog', function ($query) use ($workspace) {
                    $query->where('workspace_id', $workspace->id)
                        ->where('status', 'active')
                        ->where('source', '!=', 'meta');
                })
                ->with('catalog.providerConnection')
                ->orderBy('title')
                ->get();

            $commerceCollections = CatalogCollection::query()
                ->where('workspace_id', $workspace->id)
                ->with([
                    'metaCatalog.providerConnection',
                    'productSets' => fn ($query) => $query
                        ->with('products')
                        ->orderBy('name'),
                ])
                ->withCount('productSets')
                ->orderBy('name')
                ->get();

            $commerceOrders = CommerceOrder::query()
                ->where('workspace_id', $workspace->id)
                ->with([
                    'providerConnection',
                    'catalog',
                    'collection',
                    'items.product',
                    'snapshots.createdBy',
                ])
                ->withCount(['items', 'snapshots'])
                ->orderByDesc('placed_at')
                ->orderByDesc('id')
                ->limit(12)
                ->get();

            $commerceOrderStats = [
                'order_count' => CommerceOrder::query()
                    ->where('workspace_id', $workspace->id)
                    ->count(),
                'test_order_count' => CommerceOrder::query()
                    ->where('workspace_id', $workspace->id)
                    ->where('is_test', true)
                    ->count(),
                'snapshot_count' => CommerceOrder::query()
                    ->where('workspace_id', $workspace->id)
                    ->withCount('snapshots')
                    ->get()
                    ->sum('snapshots_count'),
            ];

            $commercePromotablePosts = SocialPost::query()
                ->where('workspace_id', $workspace->id)
                ->where('provider', 'instagram')
                ->where('status', '!=', 'deleted')
                ->with('providerConnection')
                ->latest('posted_at')
                ->latest('id')
                ->limit(60)
                ->get();

            $commercePromotionCampaigns = CommercePromotionCampaign::query()
                ->where('workspace_id', $workspace->id)
                ->with([
                    'providerConnection',
                    'collection.productSets.products',
                    'socialPost.providerConnection',
                ])
                ->orderByDesc('id')
                ->limit(20)
                ->get();

            $commercePromotionStats = [
                'campaign_count' => CommercePromotionCampaign::query()
                    ->where('workspace_id', $workspace->id)
                    ->count(),
                'prepared_count' => CommercePromotionCampaign::query()
                    ->where('workspace_id', $workspace->id)
                    ->whereIn('meta_sync_status', ['prepared', 'prepared_with_warnings'])
                    ->count(),
                'collection_ad_count' => CommercePromotionCampaign::query()
                    ->where('workspace_id', $workspace->id)
                    ->where('campaign_type', 'collection_ad')
                    ->count(),
                'promoted_post_count' => CommercePromotionCampaign::query()
                    ->where('workspace_id', $workspace->id)
                    ->where('campaign_type', 'promoted_post')
                    ->count(),
                'shops_ad_count' => CommercePromotionCampaign::query()
                    ->where('workspace_id', $workspace->id)
                    ->where('campaign_type', 'shops_ad')
                    ->count(),
            ];
        }

        return view('settings.index', [
            'workspace' => $workspace,
            'section' => $section,
            'sections' => $sections,
            'workspaceTags' => $workspaceTags,
            'workspaceDepartments' => $workspaceDepartments,
            'workspaceMembers' => $workspaceMembers,
            'providerConnections' => $providerConnections,
            'providerCards' => $providerCards,
            'catalogs' => $catalogs,
            'catalogProviderConnections' => $catalogProviderConnections,
            'commerceConnections' => $commerceConnections,
            'metaCommerceCatalogs' => $metaCommerceCatalogs,
            'commerceSourceCatalogs' => $commerceSourceCatalogs,
            'commerceProductSets' => $commerceProductSets,
            'commerceTaggableProducts' => $commerceTaggableProducts,
            'commerceCollections' => $commerceCollections,
            'commerceOrders' => $commerceOrders,
            'commerceOrderStats' => $commerceOrderStats,
            'commercePromotionCampaigns' => $commercePromotionCampaigns,
            'commercePromotablePosts' => $commercePromotablePosts,
            'commercePromotionStats' => $commercePromotionStats,
            'canManageTeam' => (bool) ($workspace && $user),
        ]);
    }

    public function createTeamMember(Request $request): RedirectResponse
    {
        $user = $request->user();
        $workspace = $user?->currentWorkspace();

        if (! $workspace) {
            abort(404);
        }

        abort_unless($workspace->members()->whereKey($user->id)->exists(), 403);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $member = DB::transaction(function () use ($workspace, $validated): User {
            $member = User::create([
                'name' => trim($validated['name']),
                'email' => Str::lower(trim($validated['email'])),
                'password' => Hash::make($validated['password']),
            ]);

            $workspace->members()->attach($member->id, [
                'role' => 'agent',
            ]);

            return $member;
        });

        event(new Registered($member));

        return redirect()->route('settings.index', [
            'section' => 'team',
        ])->with('status', 'Team member created. They must verify their email before accessing the workspace.');
    }

    public function updateGeneral(Request $request): RedirectResponse
    {
        $user = $request->user();
        $workspace = $user?->currentWorkspace();

        if (!$workspace) {
            abort(404);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        $workspace->update([
            'name' => trim($validated['name']),
        ]);

        return redirect()->route('settings.index', [
            'section' => 'general',
        ])->with('status', 'Workspace settings saved.');
    }

    public function createDepartment(Request $request): JsonResponse
    {
        $user = $request->user();
        $workspace = $user?->currentWorkspace();

        if (!$workspace) {
            abort(404);
        }

        $validated = $request->validate([
            'name' => self::LABEL_NAME_RULES,
            'color' => self::LABEL_COLOR_RULES,
        ]);

        $name = trim($validated['name']);

        $existing = WorkspaceDepartment::query()
            ->where('workspace_id', $workspace->id)
            ->whereRaw('LOWER(name) = ?', [mb_strtolower($name)])
            ->first();

        if ($existing) {
            return response()->json([
                'message' => 'Department already exists.',
            ], 422);
        }

        $maxSort = (int) WorkspaceDepartment::query()
            ->where('workspace_id', $workspace->id)
            ->max('sort_order');

        $department = WorkspaceDepartment::create([
            'workspace_id' => $workspace->id,
            'name' => $name,
            'color' => strtolower($validated['color']),
            'sort_order' => $maxSort + 1,
        ]);

        return response()->json([
            'ok' => true,
            'department' => $department,
        ]);
    }

    public function deleteDepartment(Request $request, WorkspaceDepartment $department): JsonResponse
    {
        $user = $request->user();
        $workspace = $user?->currentWorkspace();

        if (!$workspace || $department->workspace_id !== $workspace->id) {
            abort(404);
        }

        $department->delete();

        return response()->json([
            'ok' => true,
        ]);
    }

    public function createTag(Request $request): JsonResponse
    {
        $user = $request->user();
        $workspace = $user?->currentWorkspace();

        if (!$workspace) {
            abort(404);
        }

        $validated = $request->validate([
            'name' => self::LABEL_NAME_RULES,
            'color' => self::LABEL_COLOR_RULES,
        ]);

        $name = trim($validated['name']);

        $existing = WorkspaceTag::query()
            ->where('workspace_id', $workspace->id)
            ->whereRaw('LOWER(name) = ?', [mb_strtolower($name)])
            ->first();

        if ($existing) {
            return response()->json([
                'message' => 'Tag already exists.',
            ], 422);
        }

        $maxSort = (int) WorkspaceTag::query()
            ->where('workspace_id', $workspace->id)
            ->max('sort_order');

        $tag = WorkspaceTag::create([
            'workspace_id' => $workspace->id,
            'name' => $name,
            'color' => strtolower($validated['color']),
            'is_active' => true,
            'sort_order' => $maxSort + 1,
        ]);

        return response()->json([
            'ok' => true,
            'tag' => $tag,
        ]);
    }

    public function updateTag(Request $request, WorkspaceTag $tag): JsonResponse
    {
        $user = $request->user();
        $workspace = $user?->currentWorkspace();

        if (!$workspace || $tag->workspace_id !== $workspace->id) {
            abort(404);
        }

        $validated = $request->validate([
            'name' => self::LABEL_NAME_RULES,
            'color' => self::LABEL_COLOR_RULES,
            'is_active' => ['required', 'boolean'],
        ]);

        $name = trim($validated['name']);

        $duplicate = WorkspaceTag::query()
            ->where('workspace_id', $workspace->id)
            ->where('id', '!=', $tag->id)
            ->whereRaw('LOWER(name) = ?', [mb_strtolower($name)])
            ->exists();

        if ($duplicate) {
            return response()->json([
                'message' => 'Another tag with this name already exists.',
            ], 422);
        }

        $tag->update([
            'name' => $name,
            'color' => strtolower($validated['color']),
            'is_active' => (bool) $validated['is_active'],
        ]);

        return response()->json([
            'ok' => true,
            'tag' => $tag->fresh(),
        ]);
    }

    public function deleteTag(Request $request, WorkspaceTag $tag): JsonResponse
    {
        $user = $request->user();
        $workspace = $user?->currentWorkspace();

        if (!$workspace || $tag->workspace_id !== $workspace->id) {
            abort(404);
        }

        $tag->delete();

        return response()->json([
            'ok' => true,
        ]);
    }
}
