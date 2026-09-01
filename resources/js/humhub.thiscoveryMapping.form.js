humhub.module('thiscoveryMapping.form', function (module, require, $) {
    var client = require('client');
    var searchTimer = null;

    function nextIndex(dest) {
        var max = -1;
        dest.querySelectorAll('[name]').forEach(function (el) {
            var match = String(el.getAttribute('name') || '').match(/\[(\d+)\]/);
            if (match) {
                max = Math.max(max, parseInt(match[1], 10));
            }
        });
        return max + 1;
    }

    function addFromTemplate(kind) {
        var tpl = document.getElementById('tm-tpl-' + kind);
        var dest = document.getElementById('tm-' + kind);
        if (!tpl || !dest) {
            return;
        }
        dest.insertAdjacentHTML('beforeend', tpl.innerHTML.replace(/__I__/g, String(nextIndex(dest))));
        if (kind === 'questions') {
            var rows = dest.querySelectorAll('.tm-edit__question');
            if (rows.length) {
                syncQuestionOptions(rows[rows.length - 1]);
            }
        }
    }

    function syncQuestionOptions(row) {
        var typeEl = row.querySelector('[data-tm-question-type]');
        var opts = row.querySelector('[data-tm-question-options]');
        if (!opts) {
            return;
        }
        var type = typeEl ? typeEl.value : '';
        var show = type === 'dropdown' || type === 'radio';
        opts.hidden = !show;
        if (show) {
            var list = opts.querySelector('[data-tm-choice-list]');
            if (list && !list.querySelector('.tm-edit__choice')) {
                addChoiceRow(list, false);
                addChoiceRow(list, false);
            }
        }
    }

    function addChoiceRow(list, focus) {
        if (!list) {
            return;
        }
        var row = document.createElement('div');
        row.className = 'tm-edit__choice';
        var input = document.createElement('input');
        input.className = 'form-control';
        input.name = list.getAttribute('data-name') || '';
        input.placeholder = 'Choice';
        var btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'tm-edit__choice-remove';
        btn.setAttribute('data-tm-choice-remove', '1');
        btn.setAttribute('aria-label', 'Remove');
        btn.innerHTML = '&times;';
        row.appendChild(input);
        row.appendChild(btn);
        list.appendChild(row);
        if (focus) {
            input.focus();
        }
    }

    function onAddClick(e) {
        e.preventDefault();
        addFromTemplate(this.getAttribute('data-tm-add'));
    }

    function zoomForLayer(layer) {
        return {
            country: 5,
            macroregion: 5,
            region: 7,
            county: 9,
            localadmin: 11,
            locality: 12,
            borough: 13,
            neighbourhood: 14,
            postalcode: 14,
            address: 16,
            street: 16,
            venue: 17
        }[layer] || 13;
    }

    function coordRoot(el) {
        return el.closest('[data-tm-place-wrap]')
            || el.closest('.tm-edit__section, [data-cf-map-panel], form');
    }

    function geocodeUrl(input) {
        var node = input.closest('[data-tm-geocode-url]');
        return node ? node.getAttribute('data-tm-geocode-url') : '';
    }

    function resultList(input) {
        var box = input.closest('.tm-place-search') || input.parentElement;
        return box ? box.querySelector('[data-tm-place-results]') : null;
    }

    function hideResults(list) {
        if (list) {
            list.hidden = true;
            list.innerHTML = '';
        }
    }

    function applyPlace(input, row) {
        var root = coordRoot(input);
        if (!root) {
            return;
        }
        var lat = root.querySelector('[data-tm-lat]');
        var lng = root.querySelector('[data-tm-lng]');
        var zoom = root.querySelector('[data-tm-zoom]');
        if (lat) {
            lat.value = Number(row.lat).toFixed(7);
        }
        if (lng) {
            lng.value = Number(row.lng).toFixed(7);
        }
        if (zoom) {
            zoom.value = String(zoomForLayer(row.layer));
        }
        input.value = row.label || input.value;
        hideResults(resultList(input));
        syncPreviewFromFields(root, true);
    }

    function renderResults(input, rows) {
        var list = resultList(input);
        if (!list) {
            return;
        }
        list.innerHTML = '';
        if (!rows.length) {
            var empty = document.createElement('li');
            empty.className = 'tm-place-search__empty';
            empty.textContent = (input.closest('.tm-place-search') && input.closest('.tm-place-search').getAttribute('data-tm-empty')) || 'No places found';
            list.appendChild(empty);
            list.hidden = false;
            return;
        }
        rows.forEach(function (row) {
            var li = document.createElement('li');
            var btn = document.createElement('button');
            btn.type = 'button';
            btn.textContent = row.label;
            btn.addEventListener('click', function () {
                applyPlace(input, row);
            });
            li.appendChild(btn);
            list.appendChild(li);
        });
        list.hidden = false;
    }

    function onSearchInput() {
        var input = this;
        var url = geocodeUrl(input);
        var list = resultList(input);
        var q = String(input.value || '').trim();
        clearTimeout(searchTimer);
        if (!url || q.length < 3) {
            hideResults(list);
            return;
        }
        searchTimer = setTimeout(function () {
            var root = coordRoot(input);
            var latEl = root ? root.querySelector('[data-tm-lat]') : null;
            var lngEl = root ? root.querySelector('[data-tm-lng]') : null;
            var data = { q: q };
            if (latEl && lngEl && latEl.value && lngEl.value) {
                data.lat = latEl.value;
                data.lng = lngEl.value;
            }
            client.get(url, { data: data }).then(function (resp) {
                var rows = resp.results || (resp.data && resp.data.results) || [];
                renderResults(input, rows);
            }).catch(function () {
                hideResults(list);
            });
        }, 280);
    }

    function leafletGlobal() {
        var lib = window.L || window.leaflet || null;
        return (lib && typeof lib.map === 'function') ? lib : null;
    }

    function ensureLeaflet(done) {
        if (leafletGlobal()) {
            done();
            return;
        }
        var src = (module.config && module.config.leafletSrc) || '';
        if (!src || document.querySelector('script[src*="leaflet.js"]')) {
            done();
            return;
        }
        var script = document.createElement('script');
        script.src = src;
        script.async = false;
        script.setAttribute('data-tm-leaflet', '1');
        script.onload = function () { done(); };
        script.onerror = function () { done(); };
        document.head.appendChild(script);
    }

    function parsePreviewConfig(wrap) {
        var el = wrap.querySelector('[data-tm-preview-config]');
        if (!el) {
            return null;
        }
        var raw = el.getAttribute('data-tm-preview-config') || el.textContent || '';
        if (!raw) {
            return null;
        }
        try {
            return JSON.parse(raw);
        } catch (e) {
            return null;
        }
    }

    function tileUrl(cfg, style) {
        if (cfg.urlTemplate) {
            return cfg.urlTemplate.replace('{style}', style || cfg.style || 'alidade_smooth');
        }
        return cfg.url || '';
    }

    function readView(wrap) {
        var lat = parseFloat((wrap.querySelector('[data-tm-lat]') || {}).value);
        var lng = parseFloat((wrap.querySelector('[data-tm-lng]') || {}).value);
        var zoom = parseInt((wrap.querySelector('[data-tm-zoom]') || {}).value, 10);
        if (isNaN(lat)) {
            lat = 52.4862;
        }
        if (isNaN(lng)) {
            lng = -1.8904;
        }
        if (isNaN(zoom) || zoom < 1) {
            zoom = 7;
        }
        return { lat: lat, lng: lng, zoom: Math.min(20, zoom) };
    }

    function writeView(wrap, lat, lng, zoom) {
        var latEl = wrap.querySelector('[data-tm-lat]');
        var lngEl = wrap.querySelector('[data-tm-lng]');
        var zoomEl = wrap.querySelector('[data-tm-zoom]');
        wrap._tmPreviewLock = true;
        if (latEl) {
            latEl.value = Number(lat).toFixed(7);
        }
        if (lngEl) {
            lngEl.value = Number(lng).toFixed(7);
        }
        if (zoomEl) {
            zoomEl.value = String(zoom);
        }
        wrap._tmPreviewLock = false;
    }

    function syncPreviewFromFields(wrap, animate) {
        if (!wrap || !wrap._tmPreviewMap) {
            return;
        }
        var view = readView(wrap);
        wrap._tmPreviewLock = true;
        wrap._tmPreviewMap.setView([view.lat, view.lng], view.zoom, { animate: !!animate });
        wrap._tmPreviewLock = false;
        setTimeout(function () {
            wrap._tmPreviewMap.invalidateSize();
        }, 50);
    }

    function restylePreview(wrap, style) {
        var Lref = leafletGlobal();
        if (!wrap || !wrap._tmPreviewMap || !wrap._tmPreviewCfg || !Lref) {
            return;
        }
        var url = tileUrl(wrap._tmPreviewCfg, style);
        if (!url) {
            return;
        }
        if (wrap._tmPreviewTiles) {
            wrap._tmPreviewMap.removeLayer(wrap._tmPreviewTiles);
        }
        wrap._tmPreviewTiles = Lref.tileLayer(url, {
            attribution: wrap._tmPreviewCfg.attribution || '',
            maxZoom: wrap._tmPreviewCfg.maxZoom || 20
        }).addTo(wrap._tmPreviewMap);
    }

    function destroyPreview(wrap) {
        if (wrap && wrap._tmPreviewMap) {
            wrap._tmPreviewMap.remove();
            wrap._tmPreviewMap = null;
            wrap._tmPreviewTiles = null;
            wrap._tmPreviewCfg = null;
        }
        var canvas = wrap ? wrap.querySelector('[data-tm-preview-map]') : null;
        if (canvas) {
            canvas._tmReady = false;
        }
    }

    function initPreview(wrap, attempt) {
        attempt = attempt || 0;
        var canvas = wrap.querySelector('[data-tm-preview-map]');
        if (!canvas || canvas._tmReady) {
            return;
        }
        if (wrap.classList.contains('d-none')) {
            return;
        }
        var Lref = leafletGlobal();
        var hidden = wrap.offsetWidth === 0 && wrap.offsetHeight === 0;
        if (!Lref || hidden || canvas.clientHeight < 40) {
            if (attempt < 50) {
                setTimeout(function () {
                    initPreview(wrap, attempt + 1);
                }, 80);
            }
            return;
        }
        var cfg = parsePreviewConfig(wrap);
        if (!cfg || !tileUrl(cfg, cfg.style)) {
            return;
        }
        canvas._tmReady = true;
        var view = readView(wrap);
        var map;
        try {
            map = Lref.map(canvas, { scrollWheelZoom: true, zoomControl: true });
        } catch (err) {
            canvas._tmReady = false;
            return;
        }
        var styleEl = wrap.querySelector('[data-tm-style], [name="basemap_style"]');
        var style = styleEl ? styleEl.value : cfg.style;
        var tiles = Lref.tileLayer(tileUrl(cfg, style), {
            attribution: cfg.attribution || '',
            maxZoom: cfg.maxZoom || 20
        }).addTo(map);
        map.setView([view.lat, view.lng], view.zoom);
        wrap._tmPreviewMap = map;
        wrap._tmPreviewTiles = tiles;
        wrap._tmPreviewCfg = cfg;
        map.on('moveend', function () {
            if (wrap._tmPreviewLock) {
                return;
            }
            var c = map.getCenter();
            writeView(wrap, c.lat, c.lng, map.getZoom());
        });
        setTimeout(function () { map.invalidateSize(); }, 200);
        setTimeout(function () { map.invalidateSize(); }, 600);
    }

    function bootPreviews() {
        document.querySelectorAll('[data-tm-place-wrap]').forEach(function (wrap) {
            initPreview(wrap, 0);
        });
    }

    function onSearchKey(e) {
        if (e.key !== 'Enter') {
            return;
        }
        e.preventDefault();
        var list = resultList(this);
        var first = list ? list.querySelector('button') : null;
        if (first) {
            first.click();
        }
    }

    module.initOnPjaxLoad = true;
    module.unload = function () {
        document.querySelectorAll('[data-tm-place-wrap]').forEach(destroyPreview);
    };
    module.bootPreviews = bootPreviews;
    module.init = function () {
        $(document)
            .off('click.thiscoveryMappingForm', '[data-tm-add]')
            .on('click.thiscoveryMappingForm', '[data-tm-add]', onAddClick);
        $(document)
            .off('input.thiscoveryMappingPlace', '[data-tm-place-search]')
            .on('input.thiscoveryMappingPlace', '[data-tm-place-search]', onSearchInput);
        $(document)
            .off('keydown.thiscoveryMappingPlace', '[data-tm-place-search]')
            .on('keydown.thiscoveryMappingPlace', '[data-tm-place-search]', onSearchKey);
        $(document)
            .off('click.thiscoveryMappingPlaceHide')
            .on('click.thiscoveryMappingPlaceHide', function (e) {
                if ($(e.target).closest('.tm-place-search').length) {
                    return;
                }
                document.querySelectorAll('[data-tm-place-results]').forEach(function (list) {
                    hideResults(list);
                });
            });
        $(document)
            .off('change.thiscoveryMappingPreview', '[data-tm-lat], [data-tm-lng], [data-tm-zoom]')
            .on('change.thiscoveryMappingPreview', '[data-tm-lat], [data-tm-lng], [data-tm-zoom]', function () {
                var wrap = coordRoot(this);
                if (wrap && !wrap._tmPreviewLock) {
                    syncPreviewFromFields(wrap, true);
                }
            });
        $(document)
            .off('change.thiscoveryMappingStyle', '[name="basemap_style"], [data-tm-style]')
            .on('change.thiscoveryMappingStyle', '[name="basemap_style"], [data-tm-style]', function () {
                var wrap = coordRoot(this);
                restylePreview(wrap, this.value);
            });
        ensureLeaflet(bootPreviews);
        $(document)
            .off('change.thiscoveryMappingMapType', '[data-cf-field-type]')
            .on('change.thiscoveryMappingMapType', '[data-cf-field-type]', function () {
                setTimeout(bootPreviews, 80);
            });
        $(document)
            .off('click.thiscoveryMappingPalette', '[data-cf-palette-type]')
            .on('click.thiscoveryMappingPalette', '[data-cf-palette-type]', function () {
                setTimeout(bootPreviews, 120);
            });
        $(document)
            .off('change.thiscoveryMappingQuestionType', '[data-tm-question-type]')
            .on('change.thiscoveryMappingQuestionType', '[data-tm-question-type]', function () {
                var row = this.closest('.tm-edit__question');
                if (row) {
                    syncQuestionOptions(row);
                }
            });
        $(document)
            .off('click.thiscoveryMappingChoiceAdd', '[data-tm-choice-add]')
            .on('click.thiscoveryMappingChoiceAdd', '[data-tm-choice-add]', function (e) {
                e.preventDefault();
                var wrap = this.closest('[data-tm-question-options]');
                var list = wrap ? wrap.querySelector('[data-tm-choice-list]') : null;
                addChoiceRow(list, true);
            });
        $(document)
            .off('click.thiscoveryMappingChoiceRemove', '[data-tm-choice-remove]')
            .on('click.thiscoveryMappingChoiceRemove', '[data-tm-choice-remove]', function (e) {
                e.preventDefault();
                var list = this.closest('[data-tm-choice-list]');
                var row = this.closest('.tm-edit__choice');
                if (row) {
                    row.remove();
                }
                if (list && !list.querySelector('.tm-edit__choice')) {
                    addChoiceRow(list, true);
                }
            });
        document.querySelectorAll('.tm-edit__question').forEach(syncQuestionOptions);
    };
});
