@extends("Dashboard.views.templates.main")
@section("content")
<div id="widget-app" class="dashboard-page">
    <header class="navbar border-bottom">
        <div class="container">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 py-2">
                <div class="navbar-brand">Your dashboard</div>
                <button id="edit-widgets" type="button" class="btn btn-outline-secondary" aria-haspopup="dialog" aria-controls="widget-dialog">Edit widgets</button>
            </div>
        </div>
    </header>
    <div class="py-5">
        <div class="container dashboard-widget-area">
            <div id="widget-feedback" class="widget-feedback alert alert-info d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3" role="status" aria-live="polite" hidden>
                <span id="widget-feedback-text"></span>
                <button id="widget-undo" type="button" class="btn btn-outline-secondary btn-sm" hidden>Undo</button>
            </div>
            <div id="dashboard-widgets" class="widgets card-grid dashboard-widgets">{!! $widgets !!}</div>
            <section id="widgets-empty" class="card empty-state" @if(count(array_filter($widgetState['visibility'])) > 0) hidden @endif>
                <h2 class="card-title mb-2">Make room for what matters.</h2>
                <p class="text-muted mb-3">Choose the widgets you want to see on your dashboard.</p>
                <button type="button" class="btn btn-primary" data-widget-action="edit">Add widgets</button>
            </section>

            <dialog id="widget-dialog" class="widget-dialog modal scrollbar-subtle" aria-labelledby="widget-dialog-title" aria-describedby="widget-dialog-description">
                <div class="dialog-header modal-header">
                    <div>
                        <h2 id="widget-dialog-title" class="h3 m-0">Customize your dashboard</h2>
                        <p id="widget-dialog-description" class="card-subtitle text-small">Choose what’s useful. Put it where you want it.</p>
                    </div>
                    <button id="widget-dialog-close" type="button" class="btn btn-ghost btn-icon" aria-label="Close widget editor"><i class="fa-solid fa-xmark" aria-hidden="true"></i></button>
                </div>
                <div class="dialog-content modal-body">
                    <label for="widget-search" class="form-label mb-1">Find a widget</label>
                    <input id="widget-search" class="form-control mb-3" type="search" placeholder="Search widgets or packages" autocomplete="off">
                    <h3 class="h5 mb-1">On your dashboard</h3>
                    <p class="text-small text-muted m-0 mb-2">Drag to reorder, or use the arrow buttons.</p>
                    <ul id="widget-active-list" class="widget-list list-group" aria-label="Active widgets"></ul>
                    <p id="widget-active-empty" class="text-small text-muted py-2" hidden>No active widgets.</p>
                    <h3 class="h5 mt-4 mb-2">Available widgets</h3>
                    <ul id="widget-available-list" class="widget-list list-group" aria-label="Available widgets"></ul>
                    <p id="widget-available-empty" class="text-small text-muted py-2" hidden>All available widgets are on your dashboard.</p>
                    <p id="widget-no-results" class="text-small text-muted py-2" hidden>No widgets match your search.</p>
                    <p id="widget-dialog-status" class="text-small text-muted mt-3 mb-0" role="status" aria-live="polite"></p>
                </div>
                <footer class="dialog-footer modal-footer">
                    <button id="widget-reset" class="btn btn-sm" type="button">Reset layout</button>
                    <div class="d-flex gap-1">
                        <button id="widget-cancel" class="btn btn-outline-secondary btn-sm" type="button">Cancel</button>
                        <button id="widget-save" class="btn btn-primary btn-sm" type="button">Save changes</button>
                    </div>
                </footer>
            </dialog>
            <form id="widget-csrf" hidden>@csrf</form>
            <script id="widget-state" type="application/json">{!! json_encode($widgetState, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_THROW_ON_ERROR) !!}</script>
        </div>
    </div>
</div>
<script src="@asset('js/widgets.js')" defer></script>
<script src="@asset('js/dashboard-layout.js')" defer></script>
@endsection
