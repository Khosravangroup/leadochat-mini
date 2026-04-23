<?php

use App\Http\Controllers\ConnectionController;
use App\Http\Controllers\InboxController;
use App\Http\Controllers\InstagramConnectController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SocialController;
use App\Http\Controllers\PublicPageController;
use App\Http\Controllers\WorkspaceCatalogController;
use App\Http\Controllers\WorkspaceMetaCollectionController;
use App\Http\Controllers\WorkspaceMetaCommerceController;
use App\Http\Controllers\WorkspaceMetaProductSetController;
use App\Http\Controllers\WorkspaceSettingsController;
use Illuminate\Support\Facades\Route;

Route::get('/', [PublicPageController::class, 'home'])->name('home');
Route::get('/features', [PublicPageController::class, 'features'])->name('features');
Route::get('/about', [PublicPageController::class, 'about'])->name('about');
Route::get('/pricing', [PublicPageController::class, 'pricing'])->name('pricing');
Route::get('/omnichannel-inbox', [PublicPageController::class, 'landingPage'])
    ->defaults('slug', 'omnichannel-inbox')
    ->name('public.omnichannel-inbox');
Route::get('/instagram-dm-automation', [PublicPageController::class, 'landingPage'])
    ->defaults('slug', 'instagram-dm-automation')
    ->name('public.instagram-dm-automation');
Route::get('/whatsapp-business-automation', [PublicPageController::class, 'landingPage'])
    ->defaults('slug', 'whatsapp-business-automation')
    ->name('public.whatsapp-business-automation');
Route::get('/facebook-messenger-automation', [PublicPageController::class, 'landingPage'])
    ->defaults('slug', 'facebook-messenger-automation')
    ->name('public.facebook-messenger-automation');
Route::get('/customer-analytics', [PublicPageController::class, 'landingPage'])
    ->defaults('slug', 'customer-analytics')
    ->name('public.customer-analytics');
Route::get('/whatsapp-sales-funnel', [PublicPageController::class, 'landingPage'])
    ->defaults('slug', 'whatsapp-sales-funnel')
    ->name('public.whatsapp-sales-funnel');
Route::get('/shared-inbox-customer-support', [PublicPageController::class, 'landingPage'])
    ->defaults('slug', 'shared-inbox-customer-support')
    ->name('public.shared-inbox-customer-support');
Route::get('/privacy-policy', [PublicPageController::class, 'privacyPolicy'])->name('privacy-policy');
Route::get('/data-deletion', [PublicPageController::class, 'dataDeletion'])->name('data-deletion');
Route::get('/contact', [PublicPageController::class, 'contact'])->name('contact');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', function () {
        return view('dashboard');
    })->name('dashboard');


    Route::get('/connections', [ConnectionController::class, 'index'])->name('connections.index');

    Route::get('/social', function () {
        return redirect()->route('social.instagram.posts');
    })->name('social.index');

    Route::get('/social/instagram', function () {
        return redirect()->route('social.instagram.posts');
    })->name('social.instagram.index');

    Route::get('/social/instagram/posts', [SocialController::class, 'instagramPosts'])
        ->name('social.instagram.posts');

    Route::get('/social/instagram/realtime/posts', [SocialController::class, 'instagramRealtimePosts'])
        ->name('social.instagram.realtime.posts');

    Route::post('/social/instagram/posts/publish', [SocialController::class, 'publishInstagramPost'])
        ->name('social.instagram.posts.publish');

    Route::delete('/social/instagram/posts/{post}', [SocialController::class, 'deleteInstagramPost'])
        ->name('social.instagram.posts.delete');

    Route::get('/social/instagram/comments', [SocialController::class, 'instagramComments'])
        ->name('social.instagram.comments');


    Route::get('/social/instagram/stories', [SocialController::class, 'instagramStories'])
        ->name('social.instagram.stories');


    Route::post('/social/instagram/stories/publish', [SocialController::class, 'publishInstagramStory'])
        ->name('social.instagram.stories.publish');

    Route::delete('/social/instagram/stories/{story}', [SocialController::class, 'deleteInstagramStory'])
        ->name('social.instagram.stories.delete');

    Route::post('/social/instagram/comments/{comment}/reply', [SocialController::class, 'replyInstagramComment'])
        ->name('social.instagram.comments.reply');

    Route::post('/social/instagram/comments/{comment}/reply-dm', [SocialController::class, 'replyInstagramCommentViaDm'])
        ->name('social.instagram.comments.reply_dm');

    Route::post('/social/instagram/comments/{comment}/hide', [SocialController::class, 'hideInstagramComment'])
        ->name('social.instagram.comments.hide');

    Route::post('/social/instagram/comments/{comment}/unhide', [SocialController::class, 'unhideInstagramComment'])
        ->name('social.instagram.comments.unhide');

    Route::delete('/social/instagram/comments/{comment}', [SocialController::class, 'deleteInstagramComment'])
        ->name('social.instagram.comments.delete');

    Route::get('/settings', [WorkspaceSettingsController::class, 'index'])->name('settings.index');
    Route::patch('/settings/general', [WorkspaceSettingsController::class, 'updateGeneral'])->name('settings.general.update');
    Route::post('/settings/team', [WorkspaceSettingsController::class, 'createTeamMember'])->name('settings.team.create');
    Route::post('/settings/tags', [WorkspaceSettingsController::class, 'createTag'])->name('settings.tags.create');
    Route::post('/settings/tags/{tag}', [WorkspaceSettingsController::class, 'updateTag'])->name('settings.tags.update');
    Route::delete('/settings/tags/{tag}', [WorkspaceSettingsController::class, 'deleteTag'])->name('settings.tags.delete');
    Route::post('/settings/departments', [WorkspaceSettingsController::class, 'createDepartment'])->name('settings.departments.create');
    Route::delete('/settings/departments/{department}', [WorkspaceSettingsController::class, 'deleteDepartment'])->name('settings.departments.delete');
    Route::post('/settings/catalogs', [WorkspaceCatalogController::class, 'storeCatalog'])->name('settings.catalogs.store');
    Route::post('/settings/catalogs/{catalog}/products', [WorkspaceCatalogController::class, 'storeProduct'])->name('settings.catalogs.products.store');
    Route::post('/settings/catalogs/{catalog}/products/import', [WorkspaceCatalogController::class, 'importProducts'])->name('settings.catalogs.products.import');
    Route::patch('/settings/catalog-products/{product}', [WorkspaceCatalogController::class, 'updateProduct'])->name('settings.catalogs.products.update');
    Route::patch('/settings/catalog-products/{product}/status', [WorkspaceCatalogController::class, 'toggleProductStatus'])->name('settings.catalogs.products.status');
    Route::delete('/settings/catalog-products/{product}', [WorkspaceCatalogController::class, 'deleteProduct'])->name('settings.catalogs.products.delete');
    Route::post('/settings/commerce/connections/{connection}/sync', [WorkspaceMetaCommerceController::class, 'sync'])->name('settings.commerce.sync');
    Route::post('/settings/commerce/catalogs/{catalog}/products/sync', [WorkspaceMetaCommerceController::class, 'syncProducts'])->name('settings.commerce.catalogs.products.sync');
    Route::post('/settings/commerce/product-sets', [WorkspaceMetaProductSetController::class, 'store'])->name('settings.commerce.product-sets.store');
    Route::patch('/settings/commerce/product-sets/{productSet}/products', [WorkspaceMetaProductSetController::class, 'syncProducts'])->name('settings.commerce.product-sets.products.sync');
    Route::post('/settings/commerce/product-sets/{productSet}/sync', [WorkspaceMetaProductSetController::class, 'sync'])->name('settings.commerce.product-sets.sync');
    Route::delete('/settings/commerce/product-sets/{productSet}', [WorkspaceMetaProductSetController::class, 'destroy'])->name('settings.commerce.product-sets.delete');
    Route::post('/settings/commerce/collections', [WorkspaceMetaCollectionController::class, 'store'])->name('settings.commerce.collections.store');
    Route::patch('/settings/commerce/collections/{collection}/product-sets', [WorkspaceMetaCollectionController::class, 'syncProductSets'])->name('settings.commerce.collections.product-sets.sync');
    Route::post('/settings/commerce/collections/{collection}/sync', [WorkspaceMetaCollectionController::class, 'sync'])->name('settings.commerce.collections.sync');
    Route::delete('/settings/commerce/collections/{collection}', [WorkspaceMetaCollectionController::class, 'destroy'])->name('settings.commerce.collections.delete');
    Route::get('/inbox', [InboxController::class, 'index'])->name('inbox.index');
    Route::get('/inbox/realtime/snapshot', [InboxController::class, 'realtimeSnapshot'])->name('inbox.realtime.snapshot');
    Route::get('/inbox/{conversation}', [InboxController::class, 'index'])->name('inbox.show');

    Route::post('/inbox/{conversation}/messages', [InboxController::class, 'storeMessage'])->name('inbox.messages.store');
    Route::post('/inbox/{conversation}/catalog-products', [InboxController::class, 'sendCatalogProduct'])->name('inbox.catalog-products.send');
    Route::post('/inbox/{conversation}/messages/{message}/reaction', [InboxController::class, 'storeReaction'])->name('inbox.messages.reaction');
    Route::post('/inbox/{conversation}/voice', [InboxController::class, 'storeVoice'])->name('inbox.messages.voice');

    Route::post('/inbox/{conversation}/note', [InboxController::class, 'saveNote'])->name('inbox.note.save');

    Route::get('/inbox/tags/workspace', [InboxController::class, 'listWorkspaceTags'])->name('inbox.tags.workspace.list');
    Route::post('/inbox/tags/workspace', [InboxController::class, 'createWorkspaceTag'])->name('inbox.tags.workspace.create');
    Route::post('/inbox/{conversation}/tags', [InboxController::class, 'saveConversationTags'])->name('inbox.tags.conversation.save');
    Route::post('/inbox/{conversation}/department', [InboxController::class, 'saveConversationDepartment'])->name('inbox.department.save');
    Route::post('/inbox/{conversation}/agent', [InboxController::class, 'saveConversationAgent'])->name('inbox.agent.save');

    Route::post('/inbox/{conversation}/archive', [InboxController::class, 'archiveConversation'])->name('inbox.archive');
    Route::post('/inbox/{conversation}/unarchive', [InboxController::class, 'unarchiveConversation'])->name('inbox.unarchive');
    Route::post('/inbox/{conversation}/trash', [InboxController::class, 'trashConversation'])->name('inbox.trash');
    Route::post('/inbox/{conversation}/restore', [InboxController::class, 'restoreConversation'])->name('inbox.restore');

    Route::get('/connect/instagram', [InstagramConnectController::class, 'redirect'])
        ->name('connections.instagram.redirect');

    Route::get('/connect/instagram/callback', [InstagramConnectController::class, 'callback'])
        ->name('connections.instagram.callback');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
