# Dashboard 1.0.1

CMS dashboard for AWT >=27.0.0 and URPC >=1.0.0. Uses AWT's built-in admin authentication, event dispatcher, runtime linker, ObjectFactory, schema registry, transactional database provider, router CSRF and Blade views (.awt.php).

## Install / update

Existing installation:
```sh
php awt pm update /absolute/path/Dashboard-1.0.1.zip
```
New installation:
```sh
php awt pm install /absolute/path/Dashboard-1.0.1.zip
php awt pm list
php awt pm enable DASHBOARD_PACKAGE_ID
```
Use the package manager for updates. Copying files manually does not execute update.php and will leave the widget preference table missing. install.php and update.php create the same idempotent schema via TableWizard with the Dashboard package ID as owner. Back up the existing site before updating.

## User experience

`views/assets/css/main.css` is the reusable UI stylesheet: additions belong there only when they are generic components or utilities. Dashboard widget-specific styling lives in `views/assets/css/widgets.css`; page layout lives in `views/assets/css/dashboard.css`. The main layout loads both after the shared stylesheet.

Each widget has a Hide button in its top-right corner. Hiding saves a per-user database preference; an Undo button restores it. Hidden widgets are not created, processed or rendered on the next request.

Edit widgets opens a native dialog containing active and available widgets, descriptions and package names. Add/Hide edits a draft; active widgets support live drag placement, animated reorder and accessible Move up/Move down controls. Reordering respects reduced-motion preferences and preserves the editor scroll position. Search covers names, descriptions and packages. Save changes commits the draft; Cancel or Escape discards it. Reset layout is staged and only committed through Restore defaults. Empty dashboards offer an Add widgets action. Light/dark styling uses the existing card/button/spacing tokens. Icons use Font Awesome 6.5.2 loaded from cdnjs.

The package also provides Personal notes and Recently visited widgets. Notes save automatically (up to 4,000 characters), and history keeps the six latest navigation pages visited through the shared Dashboard layout, excluding the dashboard home, login and logout. Both use browser localStorage keyed by authenticated admin ID; they do not sync across devices. History shows only links still present in the current navigation, with navigation icons, visit times and the latest page highlighted. Clear history offers Undo until navigation or a history update from another tab. Older history entries remain available without invented visit times. Hiding a widget retains its data. If browser storage is unavailable, data remains in memory for the current page and the widgets display a storage message.

## Navigation permissions

Navigation items accept an optional `permissionLevel` constructor argument or `setPermissionLevel(?int $level)`. Like `admin\Admin::checkPermission()`, a user's level must be less than or equal to the threshold: Admin is 0, Moderator is 1, Author is 2. A null threshold leaves an item available to all dashboard users.

```php
$item = new NavigationItem('Administration', '/dashboard/admin', permissionLevel: 0);
$item->setPermissionLevel(1); // Admins and moderators
```

NavigationContainer filters items and nested children server-side before returning items or rendering HTML. A restricted parent hides its entire subtree. Groups without a URL disappear when all their children are inaccessible; parents with a URL remain usable. Page controllers must still enforce access with `setPermissionLevel()`. Hiding a navigation link does not protect its route.

WidgetDefinition's `permissionLevel` uses the same threshold. Restricted widgets are omitted from the dashboard and editor metadata, never instantiated, and rejected by widget mutations. Saved preferences remain stored if access is later restored.

## Registry contract

Register metadata during dashboard requests through dashboard.widgets.register. Register your event listener in your package runtime main(), before HTTP route execution. The event is dispatched for each dashboard/catalog/mutation request after authentication. It contains a WidgetRegistry and a WidgetUser with authenticated ID and numeric permission level, not an Admin model or credentials.

Each WidgetDefinition requires:
- key: stable PackageName/widget-key; never a URPC UID or translated label
- packageName, label and description
- factory: AWT ObjectFactory producing DashboardWidget
- defaultVisible, defaultPosition, size (small, medium, large)
- optional permissionLevel: AWT numeric threshold (a user's level must be <= this value)

Duplicate keys fail explicitly. Metadata collection never instantiates widget factories. The Dashboard creates only available, visible widgets in the user's saved order. The registry is rebuilt per request, so disabled packages disappear from both dashboard and picker. Their stored preferences are retained until reactivation. New widgets follow saved ones and use their declared defaults.

### Register from another package

Declare Dashboard >=1.0.1 in your manifest dependencies. Example runtime:
```php
use Dashboard\api\events\{WidgetRegistryEvent, WidgetRegistrationListener};
use Dashboard\api\widget\{WidgetDefinition, DashboardWidget};
use object\ObjectFactory;
use runtime\enums\ERuntimeFlags;

public function environmentSetup(): void
{
    parent::environmentSetup();
    $this->setRuntimeFlag(ERuntimeFlags::EventDispatcher);
    $this->waitForPackage('Dashboard');
}

public function main(): void
{
    $this->eventDispatcher->addListener(
        WidgetRegistryEvent::NAME,
        new WidgetRegistrationListener(new WidgetDefinition(
            key: 'Analytics/traffic',
            packageName: 'Analytics',
            label: 'Website traffic',
            description: 'Visits and activity on your website.',
            factory: (new ObjectFactory())
                ->setClassName(\Analytics\widgets\TrafficWidget::class)
                ->setType(DashboardWidget::class),
            defaultVisible: false,
            defaultPosition: 100,
            size: DashboardWidget::SIZE_MEDIUM,
            permissionLevel: 1,
        ))
    );
}
```
For contextual registration implement IEventListener yourself and use $event->getRegistry()->register(...). ObjectFactory also supports constructor args, properties and method calls. A factory must produce a fresh instance; do not query content during metadata registration.

## Widget structure and processors

DashboardWidget still extends URPCBlock and implements widget(): self. Existing widget implementations can keep their content structure and URPC processors. The outer Dashboard frame owns the card style, close control, data-widget-key and responsive width. The widget content should not also use card or w-100: otherwise nested card styling/width can appear. The bundled Welcome widget was adapted accordingly.

Set a stable source key on your content root. Put render-time logic in an IURPCBlockRenderer and register it with the descriptor's setBlockRenderer(). The Dashboard wrapper preserves that inner renderer. Wrapper/content source IDs are distinct from widget preference keys. Dynamic content is processed after URPC assigns source IDs.

setSize() validates the size and merges attributes instead of overwriting classes. Both DashboardWidget and WidgetDefinition default to large. The registry definition determines the effective size through data-widget-size. At a content width of at least 900px, small takes one third, medium one half, and large the full width; at 600–899px small/medium take one half; below 600px every widget takes full width. Personal notes and Recently visited explicitly use medium.

dashboard-layout.js places cards into the shortest available contiguous columns, with the shared spacing token between them. Each card keeps its natural height. ResizeObserver handles changes to content, textarea height and container width; MutationObserver handles replacement of widgets after Save, Hide or Undo. DOM order remains the saved widget order. Without JavaScript or ResizeObserver, a responsive grid still applies sizes and keeps cards from stretching to a sibling's height.

## Migration from 1.0.0 widget registration

The old dashboard.widget.request event and WidgetRequestEvent/WidgetResponseEvent classes are retained for callers that explicitly use them, but the new managed dashboard rendering path does not dispatch that document-building event. Migrate providers to WidgetRegistryEvent and WidgetRegistrationListener as above. This is an explicit API change: old hardcoded document listeners are not silently adapted because their widget() calls would execute even for hidden widgets. WidgetRenderer is now an internal renderer invoked by WidgetService; its constructor includes definitions and layout state.

## Database and API

One row in dashboard_widget_layout per admin:
- admin_id: primary key, foreign key to awt_admin.id, ON DELETE CASCADE
- payload: JSON-encoded order and visibility overrides in LONGTEXT
- revision: optimistic concurrency counter
- updated_at: UTC timestamp

Authentication selects the owner server-side using Admin::getKey(). Clients cannot supply admin_id or factory classes. Responses/catalogs are private per-user data and use Cache-Control: no-store. Every mutation uses a provider transaction and revision-checked write; stale requests receive 409 and preserve the saved layout. Rendering failures after a commit do not undo the DB preference; reload the page to retrieve current state.

Routes:
- GET /dashboard/widgets: metadata and effective current layout, without rendering content
- POST /dashboard/widgets/visibility: key, visible=0|1, revision
- POST /dashboard/widgets/layout: layout (JSON object with complete available order/visibility), revision
- POST /dashboard/widgets/reset: revision

POST bodies are URL-encoded with _token from @csrf. AWT also accepts X-CSRF-Token. Built-in router CSRF stays enabled. JSON responses use AWT's {status,message,data} envelope. Successful mutations include server-rendered HTML for visible widgets. 401 means session expired, 403 invalid CSRF, 409 concurrent edit, 422 invalid input. JavaScript retains current cards on failure and keeps unsaved editor changes open for correction.

## Verification

52 PHP integration checks and 38 DOM UI checks passed.

PHP integration checks run against unmodified AWT commit cfb94a01cd49dfda6172e93ab7374a3aedf86c4b and URPC 1.0.0. Tests use the real runtime/linker, ObjectFactory, admin model/authentication, router/CSRF, response sender, URPC processors, TableWizard/TableRegistry and Blade view. SQLite is used for the test database; only MySQL DDL syntax is translated by the test schema provider. Actual MySQL deployment and browser visual rendering were not available here.

```sh
AWT_ROOT=/path/to/AWT URPC_ROOT=/path/to/URPC php tests/run.php
```
Requires PDO SQLite, DOM, tokenizer and ctype. The script prints an actual rendered dashboard HTML path. DOM UI tests use jsdom (a test dependency only), mocking HTTP responses and native dialog open/close:
```sh
npm install --prefix /tmp/dashboard-js-test --ignore-scripts jsdom@26.1.0
JSDOM_MODULE=/tmp/dashboard-js-test/node_modules/jsdom node tests/ui.cjs /path/printed/by/php/dashboard.html
```

No framework core files are modified. Existing login, navigation and logout behavior remains as supplied; widget API authentication is handled separately and returns JSON instead of a login redirect.
