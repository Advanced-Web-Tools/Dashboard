(() => {
    "use strict";
    const app = document.getElementById("widget-app");
    if (!app) return;
    const get = (id) => document.getElementById(id);
    const dialog = get("widget-dialog");
    const grid = get("dashboard-widgets");
    const edit = get("edit-widgets");
    const activeList = get("widget-active-list");
    const availableList = get("widget-available-list");
    const search = get("widget-search");
    const status = get("widget-dialog-status");
    const token = get("widget-csrf").querySelector(
        'input[name="_token"]',
    ).value;
    let state = JSON.parse(get("widget-state").textContent);
    let draft = null;
    let busy = false;
    let opener = edit;
    let undoKey = null;
    let dragKey = null;

    const clone = (value) => JSON.parse(JSON.stringify(value));
    const widgets = () =>
        new Map(state.widgets.map((widget) => [widget.key, widget]));
    const visibleKeys = (layout) =>
        layout.order.filter((key) => layout.visibility[key]);

    function setBusy(value) {
        busy = value;
        app.setAttribute("aria-busy", String(value));
        app.querySelectorAll("button").forEach((button) => {
            button.disabled = value;
        });
        if (!value && draft) {
            drawLists();
            if (draft.reset) get("widget-reset").disabled = true;
        }
    }

    function feedback(message, key = null) {
        undoKey = key;
        get("widget-feedback-text").textContent = message;
        get("widget-undo").hidden = !key;
        get("widget-feedback").hidden = false;
    }

    function updateDashboard(next) {
        state = next;
        if (typeof next.html === "string") grid.innerHTML = next.html;
        get("widgets-empty").hidden = visibleKeys(state).length > 0;
    }

    async function request(path, fields = null) {
        const options = {
            credentials: "same-origin",
            headers: { Accept: "application/json" },
            cache: "no-store",
        };
        if (fields) {
            options.method = "POST";
            options.body = new URLSearchParams({ ...fields, _token: token });
        }
        const response = await fetch("/dashboard/widgets" + path, options);
        let result;
        try {
            result = await response.json();
        } catch {
            throw new Error(
                "Could not read the server response. Please reload and try again.",
            );
        }
        if (!response.ok) {
            const message =
                response.status === 401
                    ? "Your session expired. Please sign in again."
                    : response.status === 409
                      ? "Your dashboard changed in another tab. Close and reopen this editor to load the latest layout."
                      : response.status === 403
                        ? "Your session token expired. Reload this page and try again."
                        : response.status >= 500
                          ? "Could not save your dashboard. Please try again."
                          : result.message || "Could not save your dashboard.";
            throw new Error(message);
        }
        if (!result.data || !Array.isArray(result.data.widgets))
            throw new Error(
                "Invalid dashboard response. Reload and try again.",
            );
        return result.data;
    }

    async function openEditor(button = edit) {
        if (busy) return;
        opener = button;
        draft = null;
        activeList.replaceChildren();
        availableList.replaceChildren();
        dialog.showModal();
        status.textContent = "Loading your widgets…";
        search.value = "";
        setBusy(true);
        try {
            state = await request("");
            draft = clone({ order: state.order, visibility: state.visibility });
            status.textContent = "";
            drawLists();
        } catch (error) {
            draft = null;
            activeList.replaceChildren();
            availableList.replaceChildren();
            status.textContent = error.message;
        } finally {
            setBusy(false);
            get("widget-save").disabled = !draft;
            get("widget-reset").disabled = !draft;
            search.focus();
        }
    }

    function closeEditor() {
        if (busy) return;
        dialog.close();
        draft = null;
        if (opener?.isConnected) opener.focus();
        else edit.focus();
    }

    function icon(name) {
        const element = document.createElement("i");
        element.className = "fa-solid fa-" + name;
        element.setAttribute("aria-hidden", "true");
        return element;
    }

    function button(text, label, action, key) {
        const element = document.createElement("button");
        element.type = "button";
        element.className = "btn btn-outline btn-sm";
        const icons = { up: "arrow-up", down: "arrow-down", hide: "xmark", add: "plus" };
        element.append(icon(icons[action]));
        if (text) element.append(document.createTextNode(" " + text));
        element.setAttribute("aria-label", label);
        element.title = label;
        element.dataset.editorAction = action;
        element.dataset.key = key;
        return element;
    }

    function row(widget, active, index, total) {
        const element = document.createElement("li");
        element.className = "widget-list-row";
        element.dataset.key = widget.key;
        element.draggable = active && !search.value.trim();
        if (active) {
            const grip = icon("grip-vertical");
            grip.classList.add("widget-drag-grip");
            element.append(grip);
        }
        const copy = document.createElement("div");
        copy.className = "min-w-0 flex-grow-1";
        const title = document.createElement("strong");
        title.className = "d-block";
        title.textContent = widget.label;
        const description = document.createElement("p");
        description.className = "text-small text-muted m-0";
        description.textContent = widget.description;
        const source = document.createElement("span");
        source.className = "widget-source text-small text-muted";
        source.textContent = widget.package;
        copy.append(title, description, source);
        const controls = document.createElement("div");
        controls.className = "widget-row-controls d-flex gap-1";
        if (active) {
            const up = button(
                "",
                "Move " + widget.label + " up",
                "up",
                widget.key,
            );
            const down = button(
                "",
                "Move " + widget.label + " down",
                "down",
                widget.key,
            );
            up.disabled = index === 0;
            down.disabled = index === total - 1;
            controls.append(
                up,
                down,
                button("Hide", "Hide " + widget.label, "hide", widget.key),
            );
        } else
            controls.append(
                button("Add", "Add " + widget.label, "add", widget.key),
            );
        if (draft.reset || busy)
            controls.querySelectorAll("button").forEach((button) => {
                button.disabled = true;
            });
        element.append(copy, controls);
        return element;
    }

    function drawLists(focusKey = null, focusAction = null) {
        if (!draft) return;
        const positions = rowPositions();
        const scrollTop = dialog.scrollTop;
        const catalog = widgets();
        const keys = visibleKeys(draft);
        const query = search.value.trim().toLowerCase();
        const matches = (widget) =>
            (widget.label + " " + widget.description + " " + widget.package)
                .toLowerCase()
                .includes(query);
        activeList.replaceChildren();
        availableList.replaceChildren();
        let activeCount = 0;
        let availableCount = 0;
        draft.order.forEach((key) => {
            const widget = catalog.get(key);
            if (!widget || !matches(widget)) return;
            if (draft.visibility[key]) {
                activeList.append(
                    row(widget, true, keys.indexOf(key), keys.length),
                );
                activeCount++;
            } else {
                availableList.append(row(widget, false, 0, 0));
                availableCount++;
            }
        });
        get("widget-active-empty").hidden = activeCount > 0 || !!query;
        get("widget-available-empty").hidden = availableCount > 0 || !!query;
        get("widget-no-results").hidden =
            !query || activeCount + availableCount > 0;
        animateRows(positions);
        if (focusKey) {
            const buttons = [
                ...dialog.querySelectorAll("[data-editor-action]"),
            ];
            const target =
                buttons.find(
                    (b) =>
                        b.dataset.key === focusKey &&
                        b.dataset.editorAction === focusAction &&
                        !b.disabled,
                ) ||
                buttons.find((b) => b.dataset.key === focusKey && !b.disabled);
            (target || get("widget-save")).focus({ preventScroll: true });
        }
        dialog.scrollTop = scrollTop;
    }

    function rowPositions() {
        return new Map([...activeList.children].map(row => [row.dataset.key, row.getBoundingClientRect().top]));
    }

    function animateRows(positions) {
        if (window.matchMedia("(prefers-reduced-motion: reduce)").matches) return;
        for (const row of activeList.children) {
            const before = positions.get(row.dataset.key);
            const delta = before - row.getBoundingClientRect().top;
            if (before !== undefined && delta && row.animate && !row.classList.contains("is-dragging")) {
                row.animate([{ transform: `translateY(${delta}px)` }, { transform: "translateY(0)" }], {
                    duration: 180, easing: "ease-out",
                });
            }
        }
    }

    function move(key, direction) {
        const keys = visibleKeys(draft);
        const current = keys.indexOf(key);
        const target = current + direction;
        if (current < 0 || target < 0 || target >= keys.length) return;
        const a = draft.order.indexOf(key);
        const b = draft.order.indexOf(keys[target]);
        [draft.order[a], draft.order[b]] = [draft.order[b], draft.order[a]];
        status.textContent =
            widgets().get(key).label +
            " moved " +
            (direction < 0 ? "up." : "down.");
    }

    async function saveDraft(reset = false) {
        if (busy || !draft) return;
        setBusy(true);
        status.textContent = "Saving…";
        let saved = false;
        try {
            const next = await request(
                reset ? "/reset" : "/layout",
                reset
                    ? { revision: String(state.revision) }
                    : {
                          revision: String(state.revision),
                          layout: JSON.stringify(draft),
                      },
            );
            updateDashboard(next);
            dialog.close();
            draft = null;
            feedback(reset ? "Default layout restored." : "Dashboard updated.");
            saved = true;
        } catch (error) {
            status.textContent = error.message;
        } finally {
            setBusy(false);
            if (saved) edit.focus();
        }
    }

    async function setVisibility(key, visible) {
        if (busy) return;
        setBusy(true);
        let saved = false;
        try {
            const next = await request("/visibility", {
                key,
                visible: visible ? "1" : "0",
                revision: String(state.revision),
            });
            updateDashboard(next);
            feedback(
                visible ? "Widget restored." : "Widget hidden.",
                visible ? null : key,
            );
            saved = true;
        } catch (error) {
            feedback(error.message);
        } finally {
            setBusy(false);
            if (saved) (visible ? edit : get("widget-undo")).focus();
        }
    }

    edit.addEventListener("click", () => openEditor(edit));
    app.addEventListener("click", (event) => {
        const target = event.target.closest("[data-widget-action]");
        if (!target || busy) return;
        if (target.dataset.widgetAction === "edit") openEditor(target);
        else if (target.dataset.widgetAction === "hide") {
            const frame = target.closest("[data-widget-key]");
            if (frame) setVisibility(frame.dataset.widgetKey, false);
        }
    });
    get("widget-undo").addEventListener("click", () => {
        if (undoKey) setVisibility(undoKey, true);
    });
    get("widget-save").addEventListener("click", () =>
        saveDraft(!!draft?.reset),
    );
    get("widget-reset").addEventListener("click", () => {
        if (!draft || busy) return;
        // Reset is a staged action; Cancel still discards it.
        const defaults = [...state.widgets].sort(
            (a, b) =>
                a.defaultPosition - b.defaultPosition ||
                a.key.localeCompare(b.key),
        );
        draft = {
            order: defaults.map((widget) => widget.key),
            visibility: Object.fromEntries(
                defaults.map((widget) => [widget.key, widget.defaultVisible]),
            ),
            reset: true,
        };
        status.textContent =
            "Save to restore the default layout, including preferences for unavailable widgets.";
        get("widget-save").textContent = "Restore defaults";
        drawLists();
        get("widget-reset").disabled = true;
        search.disabled = true;
    });
    dialog.addEventListener("close", () => {
        search.disabled = false;
        get("widget-save").textContent = "Save changes";
    });
    get("widget-cancel").addEventListener("click", closeEditor);
    get("widget-dialog-close").addEventListener("click", closeEditor);
    dialog.addEventListener("cancel", (event) => {
        event.preventDefault();
        closeEditor();
    });
    search.addEventListener("input", () => {
        if (!busy && !draft?.reset) drawLists();
    });
    dialog.addEventListener("click", (event) => {
        const control = event.target.closest("[data-editor-action]");
        if (!control || !draft || draft.reset || busy) return;
        const { key, editorAction: action } = control.dataset;
        if (action === "up" || action === "down")
            move(key, action === "up" ? -1 : 1);
        else draft.visibility[key] = action === "add";
        drawLists(key, action);
    });
    activeList.addEventListener("dragstart", (event) => {
        const item = event.target.closest(".widget-list-row");
        if (!item || busy || !draft || draft.reset || search.value.trim()) {
            event.preventDefault();
            return;
        }
        dragKey = item.dataset.key;
        event.dataTransfer.setData("text/plain", dragKey);
        event.dataTransfer.effectAllowed = "move";
        item.classList.add("is-dragging");
        activeList.classList.add("is-reordering");
    });
    activeList.addEventListener("dragover", (event) => {
        if (dragKey && !busy) {
            event.preventDefault();
            event.dataTransfer.dropEffect = "move";
            const dragging = [...activeList.children].find(row => row.dataset.key === dragKey);
            if (!dragging) return;
            const positions = rowPositions();
            const next = [...activeList.children].find(row => {
                if (row === dragging) return false;
                const bounds = row.getBoundingClientRect();
                return event.clientY < bounds.top + bounds.height / 2;
            });
            if (dragging.nextElementSibling !== (next || null)) {
                activeList.insertBefore(dragging, next || null);
                animateRows(positions);
            }
            const bounds = dialog.getBoundingClientRect();
            if (event.clientY < bounds.top + 60) dialog.scrollTop -= 12;
            else if (event.clientY > bounds.bottom - 60) dialog.scrollTop += 12;
        }
    });
    activeList.addEventListener("drop", (event) => {
        if (!dragKey || busy || !draft || draft.reset)
            return;
        event.preventDefault();
        const reordered = [...activeList.children].map(row => row.dataset.key);
        let index = 0;
        draft.order = draft.order.map(key => draft.visibility[key] ? reordered[index++] : key);
        const key = dragKey;
        dragKey = null;
        activeList.classList.remove("is-reordering");
        status.textContent = "Widget order updated. Save changes to keep it.";
        drawLists(key, "up");
    });
    activeList.addEventListener("dragend", () => {
        const cancelled = dragKey !== null;
        dragKey = null;
        activeList.classList.remove("is-reordering");
        activeList
            .querySelectorAll(".is-dragging")
            .forEach((row) => row.classList.remove("is-dragging"));
        if (cancelled) drawLists();
    });
})();
