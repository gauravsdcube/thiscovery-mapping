# Map settings

This page is for the **Edit map** form. Only a title is required. Skip any optional section until you need it.

Open **Edit** from the map list or from **Edit** on the map page. **Back to maps** returns to the list without saving. **Cancel** on an existing map returns to the map page.

## About this map

**Title** is shown in lists, on the map page, and when the map is embedded.

**Description** is a short intro above the map. Leave it blank if the title is enough. Use it to say what people should add and why.

## Starting view

This is where the map opens. People can still pan and zoom afterwards.

Search for a place, postcode, town, or city rather than typing coordinates. The search fills latitude, longitude, and zoom. You can also pan and zoom the preview; the circle marks the centre.

Place search needs Stadia Maps in Configuration. Until a key is saved, enter latitude and longitude yourself.

**Zoom:** 1 is the whole world, about 7 is a region, 14 is a neighbourhood, 18 is street level.

## Basemap style

The background map. This does not change what people can draw.

| Style | Typical use |
| --- | --- |
| Alidade Smooth | Pale street map (site default) |
| OSM Bright / Outdoors | More colourful streets and paths |
| Alidade Satellite | Aerial photography |
| Stamen Terrain | Hills and landscape |
| Stamen Toner | Black and white |

Each map can override the site default from Configuration.

## What people can add

**Drawing types** — tick at least one. Tick only what you need so the drawing tools stay simple.

- **Pins** mark a place.
- **Lines** mark a route or boundary.
- **Areas** mark a neighbourhood, site, or zone.

**Who can see contributions:**

| Option | What people see |
| --- | --- |
| Everyone | All drawings are public |
| Own only | Each person sees only what they added |
| Moderated | Drawings stay hidden until a map manager approves them |

**Group nearby pins into clusters** is recommended when many pins overlap. Clusters open into individual pins as people zoom in. Lines and areas are not clustered.

## Categories (optional)

Let people tag a drawing, for example Housing or Transport. Each category has its own colour on the map. Skip this if every drawing is the same kind of thing.

## Extra questions (optional)

Asked in a small form when someone saves a drawing, alongside an optional comment. Keep this to a few short questions (short text, long text, dropdown, or choice).

For a full survey, use a Thiscovery Form instead of extra questions on the map.

## Background layers (optional)

Show existing data under people’s drawings, such as ward boundaries or a council dataset. Skip this unless you have a GIS feed to overlay. Use **HTTPS** links only. Local or private network addresses are blocked.

| Type | What to paste |
| --- | --- |
| GeoJSON URL | A public `.geojson` or FeatureCollection file |
| WMS | A map image service, with layer names as listed by the service |
| ArcGIS FeatureServer | A FeatureServer or MapServer/query endpoint. This site fetches the features |
| KML URL | A public KML file, converted to drawings on this site |

## Related

- [Getting started](creators-getting-started.md)
- [Contributions and moderation](creators-contributing.md)
