# Thiscovery Mapping for administrators

This page is for people who enable the module, add a map key, and set who can create maps. Map creators have their own guides.

Thiscovery Mapping lets teams publish interactive maps that people can explore and contribute to. Maps can live at **network (global)** level or inside a **space**. You can embed a map on a Thiscovery page or use a map question on a Thiscovery Form.

## Enable the module

1. Go to **Administration → Modules**.
2. Enable **Thiscovery Mapping**.
3. Open **Administration → Thiscovery Mapping** for network-level maps.
4. On each space that should have its own maps, enable the module for that space (**Space → Modules**).

Without the space-level enable, members will not see Maps in that space.

Network-level maps live outside a space. People with the right permission reach them from **Administration → Thiscovery Mapping**.

## Configuration

Open **Administration → Thiscovery Mapping → Configuration**, or **Administration → Modules → Thiscovery Mapping → Configure**.

### Stadia Maps

The default background map and place search use **Stadia Maps** on EU servers (Frankfurt and Paris).

1. Create a Stadia account and copy an API key.
2. Paste the key into **Stadia API key**.
3. In the Stadia dashboard, allow this website as an **HTTP referrer**.

Without a key, maps show a **401** authentication error. Place search is sent through this site so the key is not exposed in the browser.

**Stadia style** is the default background for new maps (Alidade Smooth is a pale street map). Each map can pick a different style. OSM Bright and Outdoors are more colourful; Alidade Satellite is aerial photography; Stamen Toner is black and white.

### Place search and default view

**Place search** can use Stadia or be turned off. The default latitude, longitude, and zoom apply to **new** maps until a creator sets a starting view.

### Custom tiles

Use **Custom XYZ tiles** only if you already have a tile service. The URL must be `https` and include `{z}/{x}/{y}`. Add the copyright **Attribution** the provider requires.

## Permissions

### Network (global)

| Permission | What it allows |
| --- | --- |
| Create global maps | Create network-level maps |
| Manage global maps | Edit maps, layers, and moderate contributions |
| Contribute to global maps | Place pins, lines, and areas on network maps |

Site administrators can always create and manage. Module managers can open Configuration.

### Space

| Permission | What it allows |
| --- | --- |
| Create maps | Create maps in that space |
| Manage maps | Edit maps, layers, and moderate contributions |
| Contribute to maps | Place pins, lines, and areas on maps in that space |

Space owners, admins, and moderators can create by default.

## Related modules

| Module | Why |
| --- | --- |
| Thiscovery Page Builder | Map block to embed a map on a page |
| Thiscovery Forms | Map question on a form |
| Thiscovery Navigation | When Navigation is on, maps are added to the top bar there instead of from this module |

See [Pages, forms, and the top bar](creators-embed.md) for how creators use those.
