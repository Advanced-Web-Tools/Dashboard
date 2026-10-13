(() => {
    'use strict';
    const grid = document.getElementById('dashboard-widgets');
    if (!grid || !('ResizeObserver' in window)) return;

    let frame = null;
    const observed = new Set();
    const resize = new ResizeObserver(schedule);

    function schedule() {
        if (frame !== null) return;
        frame = requestAnimationFrame(layout);
    }

    function layout() {
        frame = null;
        const items = [...grid.children].filter(item => !item.hidden);
        observed.forEach(item => {
            if (!items.includes(item)) {
                resize.unobserve(item);
                observed.delete(item);
            }
        });
        items.forEach(item => {
            if (!observed.has(item)) {
                resize.observe(item);
                observed.add(item);
            }
        });

        const width = grid.getBoundingClientRect().width;
        if (!width) return;
        const style = getComputedStyle(grid);
        const columns = Math.max(1, parseInt(style.getPropertyValue('--widget-columns'), 10) || 1);
        const gap = parseFloat(style.columnGap) || 0;
        const unit = (width - gap * (columns - 1)) / columns;
        grid.classList.add('is-packed');

        // Each column has its own bottom edge, so tall cards never stretch siblings.
        const bottoms = Array(columns).fill(0);
        items.forEach(item => {
            const span = Math.min(columns, Math.max(1,
                parseInt(getComputedStyle(item).getPropertyValue('--widget-span'), 10) || columns));
            let column = 0;
            let top = Infinity;
            for (let candidate = 0; candidate <= columns - span; candidate++) {
                const bottom = Math.max(...bottoms.slice(candidate, candidate + span));
                if (bottom < top) {
                    top = bottom;
                    column = candidate;
                }
            }
            item.style.insetInlineStart = `${column * (unit + gap)}px`;
            item.style.insetBlockStart = `${top}px`;
            const bottom = top + item.getBoundingClientRect().height + gap;
            bottoms.fill(bottom, column, column + span);
        });
        const height = items.length ? Math.max(...bottoms) - gap : 0;
        grid.style.height = `${height}px`;
    }

    resize.observe(grid);
    new MutationObserver(schedule).observe(grid, {
        childList: true, subtree: true, attributes: true,
        attributeFilter: ['data-widget-size', 'hidden'],
    });
    schedule();
})();
