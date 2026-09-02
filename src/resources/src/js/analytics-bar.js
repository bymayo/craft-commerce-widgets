/* globals Craft */

/**
 * Draws a row of stats above a Commerce element index and keeps it in step with whatever the index is
 * currently showing.
 *
 * The numbers come from Craft's own element index params, so every filter the index understands — source,
 * status, search, custom filters and, on the order index, Commerce's date range picker — is reflected
 * without this file having to know anything about them. Which index it's serving comes from the config
 * the page registers, so the same code drives both the order and product bars.
 */

const DEFAULT_DATE_ATTR = 'dateUpdated';
const PLACEHOLDER = '—';

// Craft.elementIndex is created by the page's own inline JS, which may run either side of this bundle.
const READY_POLL_INTERVAL = 50;
const READY_TIMEOUT = 10000;

// Narrowest a stat cell is allowed to get before the bar drops to fewer columns.
const MIN_CELL_WIDTH = 165;

document.addEventListener('DOMContentLoaded', () => {
    const config = window.CommerceWidgetsAnalyticsBar;

    if (!config || !config.action || !Array.isArray(config.stats) || !config.stats.length) {
        return;
    }

    whenIndexReady((index) => attach(index, config));
});

function whenIndexReady(callback) {
    const started = Date.now();

    const poll = () => {
        const index = window.Craft && Craft.elementIndex;

        if (index && index.$elements && index.$elements.length) {
            callback(index);
            return;
        }

        if (Date.now() - started < READY_TIMEOUT) {
            setTimeout(poll, READY_POLL_INTERVAL);
        }
    };

    poll();
}

function attach(index, {action, stats}) {
    const bar = buildBar(stats);
    const cells = {};

    stats.forEach((stat) => {
        cells[stat.handle] = bar.querySelector(
            '[data-handle="' + stat.handle + '"]'
        );
    });

    // Sit above the content pane rather than inside it, so the bar reads as its own card on the page
    // background instead of looking like part of the order table.
    const anchor =
        index.$elements[0].closest('#content, .content-pane') || index.$elements[0];
    anchor.parentNode.insertBefore(bar, anchor);

    layoutColumns(bar, stats.length);

    let lastWidth = Math.round(bar.clientWidth);

    const relayout = () => {
        // Changing the column count changes the bar's height, not its width, so this can't loop.
        const width = Math.round(bar.clientWidth);

        if (width !== lastWidth) {
            lastWidth = width;
            layoutColumns(bar, stats.length);
        }
    };

    // The window resize covers the usual case; the observer also catches the bar changing width on its
    // own, such as when the sources sidebar is collapsed.
    window.addEventListener('resize', relayout);

    if (typeof ResizeObserver !== 'undefined') {
        new ResizeObserver(relayout).observe(bar);
    }

    let controller = null;
    let lastFingerprint = null;

    const refresh = () => {
        // The index hasn't settled on a source yet, so there's nothing meaningful to count.
        if (!index.$source && !index.sourceKey) {
            return;
        }

        const params = buildParams(index);
        const fingerprint = fingerprintOf(params);

        // The index refreshes on paging and sorting too, neither of which changes what's counted.
        if (fingerprint === lastFingerprint) {
            return;
        }

        lastFingerprint = fingerprint;

        if (controller) {
            controller.abort();
        }

        controller = new AbortController();
        bar.classList.add('is-loading');

        Craft.sendActionRequest('POST', action, {
            data: params,
            headers: {Accept: 'application/json'},
            signal: controller.signal,
        })
            .then((response) => {
                controller = null;
                bar.classList.remove('is-loading');
                render(cells, response.data.stats || []);
            })
            .catch((error) => {
                // A superseded request isn't a failure — the newer one will paint over this one.
                if (error && error.code === 'ERR_CANCELED') {
                    return;
                }

                controller = null;
                lastFingerprint = null; // Let the next refresh retry rather than trust a failed fetch
                bar.classList.remove('is-loading');
                clear(cells);
            });
    };

    index.on('updateElements', refresh);
    refresh();
}

function buildParams(index) {
    const params = index.getViewParams();

    // Mirrors how Craft.Commerce.OrderIndex decides which date attribute its range picker filters on.
    params.statsDateAttr =
        (index.$source && index.$source.data('date-attr')) || DEFAULT_DATE_ATTR;
    params.statsStartDate = index.startDate ? index.startDate.getTime() : null;
    params.statsEndDate = index.endDate ? index.endDate.getTime() : null;

    return params;
}

/**
 * Picks a column count that keeps every row equally full.
 *
 * Letting CSS choose with auto-fit leaves an orphan — six stats in five columns strands the sixth
 * across a whole row — so the count is chosen here instead: fewest rows first, then the fewest empty
 * cells left over, then the widest layout. Six stats in a five-column space becomes two rows of three.
 */
function layoutColumns(bar, count) {
    const width = bar.clientWidth;

    if (!width || !count) {
        return;
    }

    const maxColumns = Math.max(
        1,
        Math.min(count, Math.floor(width / MIN_CELL_WIDTH))
    );

    let columns = 1;
    let fewestRows = Infinity;
    let fewestEmpty = Infinity;

    for (let candidate = 1; candidate <= maxColumns; candidate++) {
        const rows = Math.ceil(count / candidate);
        const empty = candidate * rows - count;

        if (rows < fewestRows || (rows === fewestRows && empty < fewestEmpty)) {
            columns = candidate;
            fewestRows = rows;
            fewestEmpty = empty;
        }
    }

    bar.style.setProperty('--cw-columns', columns);
}

/**
 * Identifies a set of params by everything that can move the numbers, ignoring the paging, sorting and
 * table layout that come along for the ride.
 */
function fingerprintOf(params) {
    const {criteria, viewState, collapsedElementIds, returnUrl, ...rest} = params;
    const {offset, limit, ...countedCriteria} = criteria || {};

    return JSON.stringify([rest, countedCriteria]);
}

function buildBar(stats) {
    const bar = document.createElement('div');
    bar.className = 'cw-analytics-bar is-loading';

    stats.forEach((stat) => {
        const cell = document.createElement('div');
        cell.className = 'cw-analytics-bar__stat';
        cell.setAttribute('data-handle', stat.handle);

        const label = document.createElement('div');
        label.className = 'cw-analytics-bar__label';
        label.textContent = stat.label;

        const value = document.createElement('div');
        value.className = 'cw-analytics-bar__value';

        const number = document.createElement('span');
        number.className = 'cw-analytics-bar__number';
        number.textContent = PLACEHOLDER;

        const change = document.createElement('span');
        change.className = 'cw-analytics-bar__change';
        change.hidden = true;

        value.appendChild(number);
        value.appendChild(change);
        cell.appendChild(label);
        cell.appendChild(value);
        bar.appendChild(cell);
    });

    return bar;
}

function render(cells, stats) {
    stats.forEach((stat) => {
        const cell = cells[stat.handle];

        if (!cell) {
            return;
        }

        cell.querySelector('.cw-analytics-bar__number').textContent =
            stat.formattedValue;

        const change = cell.querySelector('.cw-analytics-bar__change');
        change.className = 'cw-analytics-bar__change';

        if (!stat.changeIndicator) {
            change.hidden = true;
            change.textContent = '';
            change.removeAttribute('title');
            return;
        }

        change.hidden = false;
        change.classList.add('cw-analytics-bar__change--' + stat.changeDirection);
        change.textContent =
            arrowFor(stat.changeDirection) + ' ' + stat.changeIndicator;

        if (stat.changeTooltip) {
            change.setAttribute('title', stat.changeTooltip);
        } else {
            change.removeAttribute('title');
        }
    });
}

function clear(cells) {
    Object.values(cells).forEach((cell) => {
        cell.querySelector('.cw-analytics-bar__number').textContent = PLACEHOLDER;

        const change = cell.querySelector('.cw-analytics-bar__change');
        change.hidden = true;
        change.textContent = '';
        change.removeAttribute('title');
    });
}

function arrowFor(direction) {
    if (direction === 'up') {
        return '↗';
    }

    if (direction === 'down') {
        return '↘';
    }

    return '→';
}
