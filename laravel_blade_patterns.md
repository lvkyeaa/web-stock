# Laravel Blade Coding Patterns

Extracted from this codebase (`directory-app`) as a reusable reference for future Laravel + Blade projects. This is not a rulebook this project always follows perfectly — it's the conventions that show up consistently enough to be worth reusing, plus notes on trade-offs so you can pick deliberately next time.

## Stack this pattern set assumes

- Laravel 11, server-rendered Blade views (no SPA/Inertia)
- Bootstrap 5 admin theme (Argon Dashboard) + jQuery for DOM/AJAX glue
- Select2 for enhanced `<select>`, DataTables / Tabulator for server-side tables
- Livewire 3 for isolated interactive widgets (file upload/import dialogs), not the whole page
- Spatie `laravel-permission` for roles/permissions
- Maatwebsite/Excel + League/CSV for import/export
- Queued jobs for anything slow (import, export, notifications)

---

## 1. Layout & Blade structure

**One root layout, sections for the rest.**

```blade
{{-- resources/views/layouts/app.blade.php --}}
<body class="{{ $class ?? '' }}">
    @guest
        @yield('content')
    @endguest

    @auth
        @include('layouts.navbars.auth.sidenav')
        <main class="main-content border-radius-lg">
            @yield('content')
        </main>
    @endauth

    <script src="/assets/js/core/bootstrap.min.js"></script>
    @stack('js')
</body>
```

Every page view:

```blade
@extends('layouts.app', ['class' => 'g-sidenav-show bg-gray-100'])

@section('css')
    <link href="/vendor/select2/select2.min.css" rel="stylesheet">
@endsection

@section('content')
    @include('layouts.navbars.auth.topnav', ['title' => 'Page Title'])
    <div class="container-fluid py-4">
        {{-- page body --}}
    </div>
@endsection

@push('js')
    <script src="/vendor/select2/select2.min.js"></script>
    <script>
        // page-specific JS lives here, at the bottom of the same blade file
    </script>
@endpush
```

**Rules of thumb:**
- Page-specific `<link>` tags go in `@section('css')`, page-specific `<script>` tags go in `@push('js')` at the *bottom* of the same file (not a separate `.js` asset), so the view and its behavior stay together.
- Reusable page furniture (topnav, sidenav, footer) is `@include`d, not duplicated.
- Small, self-contained UI elements (alert banner, confirmation modal, buttons) are Blade components under `resources/views/components/`.

**Session flash messages, always the same shape:**

```blade
@if (session('success-upload'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <span class="alert-text"><strong>Success!</strong> {{ session('success-upload') }}</span>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif
```

Controllers set these with `redirect(...)->with('success-upload', '...')` / `->with('failed-upload', '...')`. Pick two consistent flash keys (e.g. `success` / `failed`) per module and reuse them everywhere instead of inventing a new key per action.

**Role-gated markup** uses Spatie's Blade directives directly in the view instead of pre-computing booleans in every branch:

```blade
@hasrole('adminprov')
    <div class="col-md-3">...satker filter...</div>
@endhasrole
```

For flags that need to combine role + permission (`hasPermissionTo('edit_business') || hasRole('adminprov')`), compute them once in the controller and pass a plain boolean (`canEdit`, `canDelete`) into the view — don't reimplement permission logic in Blade.

---

## 2. Controllers

**Fat, procedural controllers; no Form Request classes.** Validation is inline via `$request->validate([...])`. This is a deliberate simplicity trade-off in this codebase — for a new project you may prefer `FormRequest` classes once validation rules get reused or complex, but the inline style is what's consistently used here:

```php
public function updateMarket(Request $request, $id)
{
    try {
        $validated = $request->validate([
            'name'        => 'required|string|max:255',
            'description' => 'required|string|max:1000',
            'status'      => 'required|in:Tetap,Tidak Tetap',
        ]);

        $market = MarketBusiness::find($id);
        if (!$market) {
            return response()->json(['success' => false, 'message' => 'Not found'], 404);
        }

        $market->update($validated);

        return response()->json(['success' => true, 'business' => $market]);
    } catch (ValidationException $e) {
        return response()->json(['success' => false, 'errors' => $e->errors()], 422);
    } catch (Exception $e) {
        return response()->json(['success' => false, 'message' => 'Server error'], 500);
    }
}
```

**Role-based branching for what data a page loads**, right at the top of `index()`-style actions:

```php
public function index()
{
    $user = User::find(Auth::id());

    if ($user->hasRole('adminprov')) {
        $organizations = Organization::all();
    } elseif ($user->hasRole('adminkab')) {
        $markets = Market::where('organization_id', $user->organization_id)->get();
    } elseif ($user->hasRole('pml') || $user->hasRole('operator')) {
        $markets = $user->markets;
    }

    return view('module.index', compact('organizations', 'markets', /* ... */));
}
```

For 3+ roles with meaningfully different data access, this if/elseif chain is fine at controller scope but gets unwieldy once repeated across many actions — consider a policy or a scope-per-role query builder method on the model if you find yourself copy-pasting the same branch in five methods.

**Every list/table page ships with a paired JSON data endpoint** (`GET /module/data`) consumed by DataTables or Tabulator via AJAX, rather than passing the full dataset into Blade. Two response shapes appear depending on which JS table library is used:

DataTables-style (matches jQuery DataTables' expected payload):

```php
return response()->json([
    'draw'            => $request->draw,
    'recordsTotal'    => $recordsTotal,
    'recordsFiltered' => $recordsFiltered,
    'data'            => $data,
]);
```

Tabulator/custom-style (page-based, with a hard cap to protect the DB from runaway result sets):

```php
$totalRecords = (clone $records)->count();
$total = min($totalRecords, 1000); // cap total rows ever returned

return response()->json([
    'total_records' => $totalRecords,
    'last_page'      => (int) ceil($total / $perPage),
    'data'            => $data->toArray(),
]);
```

Build the filtered query with a chain of `if ($request->filterX && $request->filterX !== 'all') { $records->where(...); }` — each filter is independent and skipped when absent or `'all'`. Clone the query builder (`clone $records`) before adding pagination when you need an unpaginated count.

**Async work (import/export) never runs inline in the request.** The controller:
1. Validates the upload.
2. Creates a `*Status` row (`uuid`, `status: 'start'`, owning user/context).
3. Dispatches a queued Job (or `Maatwebsite\Excel` import `->queue()`), `->chain()`d with a notification job.
4. Redirects immediately with a flash message telling the user to check status.
5. A separate polling endpoint (`GET /module/data` on the status model, or a Livewire component) reports progress by re-reading the status row.

```php
$uuid = Str::uuid();
$status = MarketUploadStatus::create(['id' => $uuid, 'user_id' => $user->id, 'status' => 'start', /* ... */]);

try {
    (new MarketBusinessImport($uuid))->queue($absolutePath)->chain([
        new MarketUploadNotificationJob($uuid),
    ]);
} catch (Exception $e) {
    $status->update(['status' => 'failed', 'message' => $e->getMessage()]);
}

return redirect('/module/upload')->with('success-upload', 'Uploaded, check status below.');
```

Status values are a small fixed vocabulary: `start` → `loading` → `success` | `success with error` | `failed`.

---

## 3. Models

- Plain Eloquent models, relationships as simple methods (`belongsTo`, `hasMany`, `belongsToMany` with `withPivot`/`withTimestamps`).
- UUID primary keys where a model is created client-side or needs to be referenced before being persisted (job status, uploads): `use HasUuids; public $incrementing = false;` and `protected $guarded = [];` instead of `$fillable`.
- Static "value list" helpers for anything that backs a `<select>` or an enum-like column, instead of a separate enum class, keeping label/value pairs next to the model they describe:

```php
public static function getCompletionStatusValues(): array
{
    return [
        ['name' => 'Not Started', 'value' => 'not start'],
        ['name' => 'In Progress', 'value' => 'on going'],
        ['name' => 'Done',        'value' => 'done'],
    ];
}
```

- Accessors for human-readable transforms of a raw column (`getTransformedCompletionStatusAttribute`), appended via `protected $appends`.
- A shared `BaseModel` (extend it instead of `Model` on audited tables) hooks `updating` / `deleting` / `restoring` in `booted()` to write a row per changed column into a generic `audits` table (`model_type`, `table_name`, `model_id`, `column_name`, `old_value`, `new_value`, `edited_by`, `medium`, `edited_at`). This gives free field-level audit history without touching individual controllers — worth doing early in a new project if you'll need "who changed what" later, since retrofitting it is painful.

```php
class BaseModel extends Model
{
    protected static function booted()
    {
        static::updating(function ($model) {
            foreach ($model->getDirty() as $column => $newValue) {
                if (in_array($column, ['updated_at'])) continue;
                $old = $model->getOriginal($column);
                if ($old == $newValue) continue;
                DB::table('audits')->insert([/* ... */]);
            }
        });
    }
}
```

---

## 4. Routes

- Grouped by `middleware('auth')` at the top, then nested `Route::group(['middleware' => ['role:x|y|z']])` blocks per permission tier (Spatie's `role:` middleware, pipe-separated for "any of these roles").
- Plain `Route::get/post/patch/delete` with explicit paths for anything custom; `Route::resource(...)->only([...])`/`->except([...])` split across role groups when create/store needs a stricter role than index/show.
- Every page route has a sibling `/data` route for its AJAX table, and often a `/download` route that triggers an export job.

```php
Route::group(['middleware' => 'auth'], function () {
    Route::group(['middleware' => ['role:adminkab|adminprov']], function () {
        Route::get('/module', [ModuleController::class, 'index'])->name('module');
        Route::get('/module/data', [ModuleController::class, 'getData']);
        Route::post('/module/download', [ModuleController::class, 'download']);
    });
});
```

---

## 5. Front-end JS (jQuery + Select2 + AJAX), inline in Blade

No SPA framework — interactivity is jQuery calling the controller's JSON endpoints directly, with a declarative config array for repeated Select2 setup:

```js
const selectConfigs = [
    { selector: '#regency', placeholder: 'Choose Regency' },
    { selector: '#subdistrict', placeholder: 'Choose Subdistrict' },
];
selectConfigs.forEach(({ selector, placeholder }) => {
    $(selector).select2({ placeholder, allowClear: true });
});
```

Cascading dropdowns (region → subdistrict → village) each own a `loadX(parentId, preselectedId)` function that clears + shows a "Processing..." placeholder, `$.ajax`s to a `GET` endpoint, then repopulates `<option>`s — the same shape every time:

```js
function loadSubdistrict(regencyId = null, preselected = null) {
    $('#subdistrict').empty().append(`<option value="0" disabled selected>Processing...</option>`);
    $.ajax({
        url: '/kec/' + (regencyId ?? $('#regency').val()),
        success: (response) => {
            $('#subdistrict').empty().append(`<option value="0" disabled selected>-- Choose --</option>`);
            response.forEach(item => {
                const selected = preselected == String(item.id) ? 'selected' : '';
                $('#subdistrict').append(`<option value="${item.id}" ${selected}>${item.name}</option>`);
            });
        }
    });
}
```

Filter changes trigger a `renderTable()` re-fetch of the DataTables/Tabulator data endpoint rather than filtering client-side — keeps large datasets server-side.

---

## 6. Async jobs & import/export

- **Import**: a `Maatwebsite\Excel` `Importer` class (implements `ShouldQueue` via the package), dispatched with `->queue($path)->chain([NotificationJob])`. The notification job (usually a simple `ShouldQueue` job) flips the status row to its terminal state and optionally emails/broadcasts.
- **Export**: a plain queued `Job` (`implements ShouldQueue`) that streams a `League\Csv\Writer` to a `Storage::path(...)` file in `chunk(1000, ...)` batches (never `->get()` the whole table into memory), wrapped in try/catch that writes failures back to the same status-row pattern.
- Every async job constructor immediately flips its status row to `loading` and the `handle()` method wraps everything in try/catch, writing `failed` + `$e->getMessage()` on error rather than letting the queue worker's failed-job table be the only record.

```php
class MarketBusinessExportJob implements ShouldQueue
{
    use Queueable;
    public $timeout = 0;

    public function handle(): void
    {
        try {
            $status = AssignmentStatus::find($this->uuid);
            $status->update(['status' => 'loading']);

            $stream = fopen(Storage::path("/exports/{$this->uuid}.csv"), 'w+');
            $csv = Writer::createFromStream($stream);
            $csv->insertOne([/* header row */]);

            MarketBusiness::query()
                ->with(['market', 'user'])
                ->chunk(1000, function ($rows) use ($csv) {
                    foreach ($rows as $row) {
                        $csv->insertOne([/* ... */]);
                    }
                });

            fclose($stream);
            $status->update(['status' => 'success']);
        } catch (Exception $e) {
            $status->update(['status' => 'failed', 'message' => $e->getMessage()]);
        }
    }
}
```

---

## 7. Livewire — for widgets, not whole pages

Livewire is reserved for small, self-contained interactive pieces (a file-upload dialog, a status modal) embedded inside an otherwise static Blade page, not for driving the whole page. A component mirrors the controller's async-job pattern exactly (status row + queued job + chained notification), just triggered from a Livewire action instead of a controller method:

```php
class Import extends Component
{
    use WithFileUploads;
    public $importFile;
    public $importing = false;

    public function import()
    {
        $this->validate(['importFile' => 'required|mimes:xlsx,csv|max:2048']);
        $uuid = (string) Str::uuid();
        AssignmentStatus::create(['id' => $uuid, 'user_id' => Auth::id(), 'status' => 'start']);
        (new SlsAssignmentImport(Auth::user()->regency_id, $uuid))
            ->queue($this->importFile->store('imports'))
            ->chain([new AssignmentNotificationJob($uuid)]);
    }

    public function render() { return view('livewire.import'); }
}
```

The page polls progress (`updateImportProgress`) either via Livewire polling or a JS interval hitting a status endpoint, and flips a `$importFinished` flag once the status row reaches a terminal state.

---

## 8. API layer conventions

Separate `App\Http\Controllers\Api\*` namespace for token/mobile-facing endpoints, using a shared trait for a single consistent JSON envelope instead of ad-hoc `response()->json()` calls:

```php
trait ApiResponser
{
    protected function successResponse($data = null, $message = 'Success', $status = 200)
    {
        return response()->json(['success' => true, 'message' => $message, 'data' => $data, 'status_code' => $status], $status);
    }

    protected function errorResponse($message = 'Error', $status = 500, $errors = null)
    {
        return response()->json(['success' => false, 'message' => $message, 'errors' => $errors, 'status_code' => $status], $status);
    }
}
```

Web-facing (Blade-consumed) JSON endpoints skip this envelope and just return the shape the JS table library expects (see §2) — the envelope is an API-only convention, don't force it onto DataTables/Tabulator responses.

---

## Things to reconsider, not just copy, next time

- **Inline `$request->validate()` in every controller method** duplicates rules once the same entity is validated in create + update. Worth graduating to `FormRequest` classes as soon as a validation rule set is reused more than twice.
- **No repository/service layer** — controllers talk to Eloquent directly. Fine at this size; if a new project's business logic grows past simple CRUD + filters, extract query logic (the role-based `if/elseif` blocks especially) into query scopes or a small service class per module rather than letting controllers keep growing.
- **Status polling via a generic `*Status` model per feature** (`MarketUploadStatus`, `AssignmentStatus`, ...) duplicates the same `id/user_id/status/message` shape across tables. A single polymorphic `job_statuses` table (`trackable_type`, `trackable_id`) would remove that duplication in a fresh project.