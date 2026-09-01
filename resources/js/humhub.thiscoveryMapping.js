humhub.module('thiscoveryMapping', function (module, require, $) {
    var client = require('client');

    function parseConfig(root) {
        var el = root.querySelector('[data-tm-config]');
        if (!el) {
            return null;
        }
        try {
            return JSON.parse(el.textContent);
        } catch (e) {
            return null;
        }
    }

    function showTileError(root, cfg) {
        if (root.querySelector('[data-tm-tile-error]')) {
            return;
        }
        var banner = document.createElement('div');
        banner.className = 'tm-tile-error';
        banner.setAttribute('data-tm-tile-error', '1');
        banner.textContent = cfg.tileError || 'The map background could not load.';
        if (cfg.settingsUrl) {
            var link = document.createElement('a');
            link.href = cfg.settingsUrl;
            link.textContent = ' Open mapping settings';
            banner.appendChild(link);
        }
        root.insertBefore(banner, root.firstChild);
    }

    function iconFor(color) {
        var c = String(color || '#1d70b8').replace(/[^#A-Za-z0-9(),.% ]/g, '');
        if (!c) {
            c = '#1d70b8';
        }
        return L.divIcon({
            className: 'tm-pin',
            html: '<svg xmlns="http://www.w3.org/2000/svg" width="25" height="41" viewBox="0 0 25 41" aria-hidden="true"><path fill="' + c + '" stroke="#fff" stroke-width="1.5" d="M12.5 1C6.7 1 2 5.7 2 11.5 2 20 12.5 32 12.5 32S23 20 23 11.5C23 5.7 18.3 1 12.5 1z"/><circle fill="#fff" cx="12.5" cy="11.5" r="4"/></svg>',
            iconSize: [25, 41],
            iconAnchor: [12, 40],
            popupAnchor: [1, -34]
        });
    }

    function useDefaultPin() {
        if (typeof L === 'undefined' || L.Marker.prototype.options.icon instanceof L.DivIcon) {
            return;
        }
        L.Marker.prototype.options.icon = iconFor('#1d70b8');
    }

    function styleFor(props) {
        var color = (props && props.color) || '#1d70b8';
        return { color: color, weight: 3, fillColor: color, fillOpacity: 0.25 };
    }

    function csrf(cfg) {
        return { [humhub.csrfTokenName || '_csrf']: cfg.csrf };
    }

    function initMap(root) {
        var cfg = parseConfig(root);
        if (!cfg || cfg.mode === 'empty' || typeof L === 'undefined') {
            return;
        }
        var canvas = root.querySelector('.tm-canvas');
        if (!canvas || canvas._tmReady) {
            return;
        }
        canvas._tmReady = true;
        useDefaultPin();

        var map = L.map(canvas, { scrollWheelZoom: true, fullscreenControl: true });
        var tiles = L.tileLayer(cfg.basemap.url, {
            attribution: cfg.basemap.attribution,
            maxZoom: cfg.basemap.maxZoom || 20
        }).addTo(map);
        tiles.on('tileerror', function () {
            showTileError(root, cfg);
        });

        var center = cfg.center || [-1.89, 52.48];
        map.setView([center[1], center[0]], cfg.zoom || 7);

        if (cfg.mode === 'form') {
            initForm(root, map, cfg);
            return;
        }
        initParticipatory(root, map, cfg);
    }

    function initForm(root, map, cfg) {
        var wrap = root.closest('.cf-map-field') || root.parentElement || root;
        var drawn = L.featureGroup().addTo(map);
        var hidden = null;
        if (!cfg.readOnly) {
            hidden = wrap.querySelector('input[name="' + cfg.inputName + '"]');
            if (!hidden) {
                hidden = document.createElement('input');
                hidden.type = 'hidden';
                hidden.name = cfg.inputName;
                root.appendChild(hidden);
            }
        }

        function sync() {
            if (hidden) {
                hidden.value = JSON.stringify(drawn.toGeoJSON());
            }
        }

        if (!cfg.readOnly) {
            enableDraw(map, cfg.allowedTypes || ['Point'], {
                onCreate: function (layer) {
                    if (layer instanceof L.Marker) {
                        layer.setIcon(iconFor('#1d70b8'));
                    }
                    if (cfg.maxFeatures === 1 || (cfg.maxFeatures > 0 && drawn.getLayers().length >= cfg.maxFeatures)) {
                        drawn.clearLayers();
                    }
                    drawn.addLayer(layer);
                    sync();
                }
            });
            map.on('pm:update', sync);
            map.on('pm:remove', function (e) {
                drawn.removeLayer(e.layer);
                sync();
            });
        }
        if (cfg.value) {
            try {
                var gj = typeof cfg.value === 'string' ? JSON.parse(cfg.value) : cfg.value;
                L.geoJSON(gj, {
                    pointToLayer: function (f, latlng) {
                        return L.marker(latlng, { icon: iconFor((f.properties && f.properties.color) || '#1d70b8') });
                    },
                    style: styleFor
                }).eachLayer(function (l) { drawn.addLayer(l); });
                if (drawn.getLayers().length) {
                    map.fitBounds(drawn.getBounds(), { padding: [24, 24], maxZoom: 16 });
                }
                sync();
            } catch (e) {}
        }
        if (!cfg.readOnly) {
            bindSearch(root, map, cfg);
        }
        setTimeout(function () { map.invalidateSize(); }, 250);
        setTimeout(function () { map.invalidateSize(); }, 700);
    }

    function enableDraw(map, types, handlers) {
        if (!map.pm) {
            return;
        }
        var pos = {
            position: 'topleft',
            drawMarker: types.indexOf('Point') !== -1,
            drawPolyline: types.indexOf('LineString') !== -1,
            drawPolygon: types.indexOf('Polygon') !== -1,
            drawCircle: false,
            drawCircleMarker: false,
            drawRectangle: false,
            drawText: false,
            editMode: true,
            dragMode: true,
            cutPolygon: false,
            removalMode: true,
            rotateMode: false
        };
        map.pm.addControls(pos);
        map.on('pm:create', function (e) {
            if (e.layer instanceof L.Marker) {
                e.layer.setIcon(iconFor('#1d70b8'));
            }
            if (handlers.onCreate) {
                handlers.onCreate(e.layer);
            }
        });
    }

    function initParticipatory(root, map, cfg) {
        var cluster = cfg.clustering && typeof L.markerClusterGroup === 'function'
            ? L.markerClusterGroup({ showCoverageOnHover: false, maxClusterRadius: 48 })
            : null;
        var points = cluster || L.layerGroup();
        var lines = L.layerGroup();
        points.addTo(map);
        lines.addTo(map);

        var state = { layersById: {} };

        function addFeature(feature) {
            var id = feature.id || (feature.properties && feature.properties.id);
            if (state.layersById[id]) {
                points.removeLayer(state.layersById[id]);
                lines.removeLayer(state.layersById[id]);
            }
            var layer = L.geoJSON(feature, {
                pointToLayer: function (f, latlng) {
                    return L.marker(latlng, { icon: iconFor(f.properties && f.properties.color) });
                },
                style: function (f) {
                    return styleFor(f.properties);
                },
                onEachFeature: function (f, lyr) {
                    lyr.on('click', function () {
                        openDetail(root, cfg, f.properties.id);
                    });
                }
            });
            var geom = feature.geometry && feature.geometry.type;
            if (geom === 'Point' && cluster) {
                cluster.addLayer(layer);
            } else if (geom === 'Point') {
                points.addLayer(layer);
            } else {
                lines.addLayer(layer);
            }
            state.layersById[id] = layer;
        }

        function reload() {
            var params = {};
            var cat = root.querySelector('[data-tm-filter="category"]');
            var type = root.querySelector('[data-tm-filter="type"]');
            var from = root.querySelector('[data-tm-filter="from"]');
            var to = root.querySelector('[data-tm-filter="to"]');
            if (cat && cat.value) params.category = cat.value;
            if (type && type.value) params.type = type.value;
            if (from && from.value) params.from = from.value;
            if (to && to.value) params.to = to.value;
            client.get(cfg.urls.features, {data: params}).then(function (resp) {
                var data = resp.data || resp;
                if (cluster) {
                    cluster.clearLayers();
                } else {
                    points.clearLayers();
                }
                lines.clearLayers();
                state.layersById = {};
                (data.features || []).forEach(addFeature);
            }).catch(function () {});
        }

        root.querySelectorAll('[data-tm-filter]').forEach(function (el) {
            el.addEventListener('change', reload);
        });

        if (cfg.canContribute) {
            enableDraw(map, cfg.allowedTypes, {
                onCreate: function (layer) {
                    promptSave(root, map, cfg, layer, null, reload);
                }
            });
            map.on('pm:update', function (e) {
                var id = e.layer.feature && e.layer.feature.properties && e.layer.feature.properties.id;
                if (!id) {
                    return;
                }
                saveFeature(cfg, { id: id, feature: e.layer.toGeoJSON() }).then(reload);
            });
            map.on('pm:remove', function (e) {
                var id = e.layer.feature && e.layer.feature.properties && e.layer.feature.properties.id;
                if (!id) {
                    return;
                }
                client.post(cfg.urls.delete, {data: { featureId: id }}).then(reload);
            });
        }

        (cfg.layers || []).forEach(function (layer) {
            loadExternal(map, cfg, layer);
        });

        bindSearch(root, map, cfg);
        bindDrawer(root, map);
        reload();
        setTimeout(function () { map.invalidateSize(); }, 200);
    }

    function loadExternal(map, cfg, layer) {
        if (layer.type === 'wms' && layer.url) {
            L.tileLayer.wms(layer.url, {
                layers: layer.layers || '',
                format: 'image/png',
                transparent: true,
                attribution: HtmlEncode(layer.name)
            }).addTo(map);
            return;
        }
        client.get(cfg.urls.layer, {data: { layerId: layer.id }}).then(function (resp) {
            var data = resp.data || resp;
            if (!data || data.type !== 'FeatureCollection') {
                return;
            }
            L.geoJSON(data, {
                style: { color: '#505a5f', weight: 2, fillOpacity: 0.1 },
                onEachFeature: function (f, lyr) {
                    var bits = [];
                    (layer.popupFields || []).forEach(function (key) {
                        if (f.properties && f.properties[key] != null && f.properties[key] !== '') {
                            bits.push('<div><span class="text-muted">' + HtmlEncode(key) + '</span> ' + HtmlEncode(String(f.properties[key])) + '</div>');
                        }
                    });
                    if (bits.length) {
                        lyr.bindPopup(bits.join(''));
                    }
                }
            }).addTo(map);
        }).catch(function () {});
    }

    function HtmlEncode(s) {
        return String(s).replace(/[&<>"']/g, function (c) {
            return ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[c];
        });
    }

    function str(cfg, key, fallback) {
        return (cfg.strings && cfg.strings[key]) || fallback;
    }

    function setDrawer(root, title, content, onClose) {
        var drawer = root.querySelector('[data-tm-drawer]');
        var body = root.querySelector('[data-tm-drawer-body]');
        var heading = root.querySelector('[data-tm-drawer-title]');
        if (!drawer || !body) {
            return null;
        }
        if (heading) {
            heading.textContent = title || '';
        }
        body.innerHTML = '';
        if (typeof content === 'string') {
            body.innerHTML = content;
        } else if (content) {
            body.appendChild(content);
        }
        drawer.hidden = false;
        root._tmDrawerOnClose = onClose || null;
        return body;
    }

    function closeDrawer(root, skipCallback) {
        var drawer = root.querySelector('[data-tm-drawer]');
        var body = root.querySelector('[data-tm-drawer-body]');
        var heading = root.querySelector('[data-tm-drawer-title]');
        if (drawer) {
            drawer.hidden = true;
        }
        if (heading) {
            heading.textContent = '';
        }
        if (body) {
            body.innerHTML = '';
        }
        var onClose = root._tmDrawerOnClose;
        root._tmDrawerOnClose = null;
        if (!skipCallback && typeof onClose === 'function') {
            onClose();
        }
    }

    function bindDrawer(root, map) {
        var close = root.querySelector('[data-tm-close]');
        if (!close || close._tmBound) {
            return;
        }
        close._tmBound = true;
        close.addEventListener('click', function () {
            closeDrawer(root);
        });
    }

    function fieldBlock(label, required, requiredLabel, inner) {
        return '<div class="tm-field"' + (required ? ' data-required="1"' : '') + '>' +
            '<div class="tm-field__head"><span class="tm-field__label">' + HtmlEncode(label) + '</span>' +
            (required ? '<span class="tm-field__req">' + HtmlEncode(requiredLabel) + '</span>' : '') +
            '</div>' + inner + '<p class="tm-field__error" hidden></p></div>';
    }

    function questionControl(q, cfg) {
        var key = HtmlEncode(q.key);
        if (q.type === 'textarea') {
            return '<textarea class="form-control" data-q="' + key + '" rows="3"></textarea>';
        }
        if (q.type === 'radio') {
            if (!(q.options || []).length) {
                return '<p class="tm-field__empty">' + HtmlEncode(str(cfg, 'noChoices', 'This question has no choices yet.')) + '</p>';
            }
            var html = '<div class="tm-choices" data-q="' + key + '" data-tm-choice-group role="radiogroup">';
            (q.options || []).forEach(function (o, i) {
                var oid = 'tmq-' + key + '-' + i;
                html += '<label class="tm-choice" for="' + oid + '">' +
                    '<input type="radio" id="' + oid + '" name="tmq-' + key + '" value="' + HtmlEncode(o) + '">' +
                    '<span>' + HtmlEncode(o) + '</span></label>';
            });
            return html + '</div>';
        }
        if (q.type === 'dropdown') {
            var html = '<select class="form-control" data-q="' + key + '"><option value="">' +
                HtmlEncode(str(cfg, 'choose', 'Choose…')) + '</option>';
            (q.options || []).forEach(function (o) {
                html += '<option value="' + HtmlEncode(o) + '">' + HtmlEncode(o) + '</option>';
            });
            return html + '</select>';
        }
        return '<input class="form-control" type="text" data-q="' + key + '">';
    }

    function readValue(el) {
        if (el.getAttribute('data-tm-choice-group') != null) {
            var on = el.querySelector('input:checked');
            return on ? on.value : '';
        }
        return el.value;
    }

    function saveTitleFor(gj, cfg) {
        var type = (gj.geometry && gj.geometry.type) || '';
        if (type === 'Point') {
            return str(cfg, 'savePoint', 'Save this pin');
        }
        if (type === 'LineString') {
            return str(cfg, 'saveLine', 'Save this line');
        }
        if (type === 'Polygon') {
            return str(cfg, 'saveArea', 'Save this area');
        }
        return str(cfg, 'saveTitle', 'Save this drawing');
    }

    function promptSave(root, map, cfg, layer, existing, reload) {
        var gj = layer.toGeoJSON();
        var wrap = document.createElement('div');
        wrap.className = 'tm-form';
        var html = '<p class="tm-form__intro">' + HtmlEncode(str(cfg, 'saveIntro', 'Add a comment if you like, then answer any questions before you save.')) + '</p>';
        html += fieldBlock(
            str(cfg, 'comment', 'Comment'),
            false,
            '',
            '<textarea class="form-control" data-f="comment" rows="3" placeholder="' + HtmlEncode(str(cfg, 'commentHint', 'Optional. A short note about this drawing.')) + '"></textarea>'
        );
        if (cfg.categories && cfg.categories.length) {
            var catRequired = !!cfg.requireCategory;
            var cat = '<select class="form-control" data-f="category"><option value="">' +
                HtmlEncode(str(cfg, 'chooseCategory', 'Choose a category')) + '</option>';
            cfg.categories.forEach(function (c) {
                cat += '<option value="' + HtmlEncode(c.key) + '">' + HtmlEncode(c.name) + '</option>';
            });
            cat += '</select>';
            html += fieldBlock(str(cfg, 'category', 'Category'), catRequired, str(cfg, 'required', 'Required'), cat);
        }
        (cfg.questions || []).forEach(function (q) {
            html += fieldBlock(q.label, !!q.required, str(cfg, 'required', 'Required'), questionControl(q, cfg));
        });
        html += '<div class="tm-form__actions">' +
            '<button type="button" class="btn btn-primary" data-save>' + HtmlEncode(str(cfg, 'save', 'Save')) + '</button>' +
            '<button type="button" class="btn btn-default" data-cancel>' + HtmlEncode(str(cfg, 'cancel', 'Cancel')) + '</button>' +
            '</div>';
        wrap.innerHTML = html;

        var discarded = false;
        function discard() {
            if (discarded) {
                return;
            }
            discarded = true;
            if (map && layer && map.hasLayer(layer)) {
                map.removeLayer(layer);
            }
        }

        setDrawer(root, saveTitleFor(gj, cfg), wrap, discard);

        wrap.querySelector('[data-save]').addEventListener('click', function () {
            var missing = false;
            wrap.querySelectorAll('.tm-field[data-required]').forEach(function (field) {
                var el = field.querySelector('[data-q], [data-f]');
                var err = field.querySelector('.tm-field__error');
                var val = el ? String(readValue(el) || '').trim() : '';
                var bad = !val;
                var message = field.querySelector('[data-f="category"]')
                    ? str(cfg, 'categoryRequired', 'Please choose a category.')
                    : str(cfg, 'requiredMissing', 'Please answer this question.');
                field.classList.toggle('is-invalid', bad);
                if (err) {
                    err.hidden = !bad;
                    err.textContent = bad ? message : '';
                }
                if (bad) {
                    missing = true;
                }
            });
            if (missing) {
                return;
            }
            var responses = {};
            wrap.querySelectorAll('[data-q]').forEach(function (el) {
                responses[el.getAttribute('data-q')] = readValue(el);
            });
            var cat = wrap.querySelector('[data-f="category"]');
            var btn = wrap.querySelector('[data-save]');
            btn.disabled = true;
            saveFeature(cfg, {
                feature: gj,
                comment: wrap.querySelector('[data-f="comment"]').value,
                category: cat ? cat.value : '',
                responses: responses
            }).then(function () {
                discarded = true;
                closeDrawer(root, true);
                if (map && layer && map.hasLayer(layer)) {
                    map.removeLayer(layer);
                }
                reload();
            }).catch(function () {
                btn.disabled = false;
                btn.textContent = str(cfg, 'couldNotSave', 'Could not save. Try again.');
            });
        });
        wrap.querySelector('[data-cancel]').addEventListener('click', function () {
            closeDrawer(root);
        });
    }

    function saveFeature(cfg, payload) {
        return client.post(cfg.urls.save, {data: { payload: JSON.stringify(payload) }});
    }

    function openDetail(root, cfg, id) {
        closeDrawer(root);
        setDrawer(root, str(cfg, 'details', 'Drawing'), '', null);
        client.get(cfg.urls.detail, {data: { featureId: id }}).then(function (resp) {
            var html = (resp.data && resp.data.html) || resp.html || resp.output || '';
            var body = root.querySelector('[data-tm-drawer-body]');
            if (body) {
                body.innerHTML = html;
            }
        }).catch(function () {
            closeDrawer(root, true);
        });
    }

    function bindSearch(root, map, cfg) {
        var input = root.querySelector('[data-tm-search]');
        var list = root.querySelector('[data-tm-search-results]');
        var geocodeUrl = (cfg.urls && cfg.urls.geocode) || cfg.geocodeUrl;
        if (!input || !list || !geocodeUrl) {
            return;
        }
        var t;
        input.addEventListener('input', function () {
            clearTimeout(t);
            var q = input.value.trim();
            if (q.length < 3) {
                list.hidden = true;
                return;
            }
            t = setTimeout(function () {
                client.get(geocodeUrl, {data: { q: q }}).then(function (resp) {
                    var rows = (resp.results || (resp.data && resp.data.results) || []);
                    list.innerHTML = '';
                    rows.forEach(function (row) {
                        var li = document.createElement('li');
                        var b = document.createElement('button');
                        b.type = 'button';
                        b.textContent = row.label;
                        b.addEventListener('click', function () {
                            map.setView([row.lat, row.lng], Math.max(map.getZoom(), 14));
                            list.hidden = true;
                            input.value = row.label;
                        });
                        li.appendChild(b);
                        list.appendChild(li);
                    });
                    list.hidden = rows.length === 0;
                });
            }, 280);
        });
    }

    function boot(node) {
        (node || document).querySelectorAll('[data-tm-map]').forEach(initMap);
    }

    module.initOnPjaxLoad = true;
    module.initOnAjaxLoad = true;
    module.init = function () {
        boot(document);
    };
    module.initListMaps = function () {
        $(document).off('change.tmListSubmit').on('change.tmListSubmit', 'select[data-tm-auto-submit]', function () {
            var form = $(this).closest('form');
            if (form.length) {
                form.trigger('submit');
            }
        });
    };
    module.export({
        init: module.init,
        initListMaps: module.initListMaps
    });
});
