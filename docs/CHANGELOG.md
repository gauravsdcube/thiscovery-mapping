# Changelog

All notable changes to this module are documented in this file.

## 1.0.4 (September 1, 2026)

- Enh: Saving a drawing opens a side panel instead of a small popup; choice questions are tappable chips
- Enh: Category can be required on Edit map so people must pick one when they save a drawing
- Enh: Extra-question choices on Edit map are a list with Add choice, not a cramped one-line box
- Fix: Extra questions appear only after someone draws and saves, not as a survey on the map page; admins and map managers can always draw so they can reach that save box
- Change: Place search and type/date/category filters are off unless you enable them on Edit map (including when the map is embedded on a page)
- Enh: A short hint on the map when extra questions are configured and the viewer can contribute
- Enh: Form map questions can use a per-question basemap style (Thiscovery Forms 1.21.5)

## 1.0.3 (September 1, 2026)

- Fix: Network map list uses the Administration layout and table UI, matching Thiscovery Forms and Page Builder
- Fix: Map list counts load without ActiveQuery withCount(), which HumHub content queries do not support
- Fix: Opened maps include a Back to maps control; network maps stay in Administration so the list is reachable
- Enh: In-product Help for administrators and map creators, matching Forms and Page Builder

## 1.0.2 (September 1, 2026)

- Change: When Thiscovery Navigation is enabled, maps are added to the top bar there instead of from this module

## 1.0.1 (August 29, 2026)

- Fix: Page Builder map block registers only when Mapping is enabled; safer palette registration
- Note: Forms and Page Builder show Map features only while this module is installed and enabled

## 1.0.0 (August 29, 2026)

- Enh: Interactive and participatory maps for spaces and the network
- Enh: Leaflet drawing, Stadia basemaps, GeoJSON contributions, moderation, and export
- Enh: Page Builder map embed and Thiscovery Forms map question support
