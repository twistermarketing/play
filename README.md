# Playground Planner

Starter WordPress plugin for an interactive playground product planner.

## Install

1. Zip the `playground-planner` folder.
2. In WordPress go to **Plugins → Add New → Upload Plugin**.
3. Activate it.
4. Go to **Settings → Playground Planner** and confirm the product post type. It defaults to `product` for WooCommerce.
5. Create a page and add the shortcode:

`[playground_planner]`

## Current functionality

- Uses existing WordPress product posts as the source of truth.
- Reads common product fields from post meta.
- Exposes products through a REST endpoint.
- Product browsing and search.
- Category, setting and age filtering.
- Optional site dimensions.
- Optional site photo field (UI only at this stage).
- Add/remove products from a concept board.
- Basic critical-fall-height surfacing flag.
- Basic recommendation data field support.

## Important next step

The exact field names in `includes/class-playground-planner.php` should be mapped to the spreadsheet and the actual fields used by the existing WordPress site (WooCommerce, ACF, custom fields, or another product system).

The 3D viewer/placement engine is deliberately not hard-coded yet because the actual 3D file format and existing WordPress 3D field need to be confirmed. The next development stage can add GLB/GLTF viewing and a proper site-plan placement layer.

This is a planning/concept tool and should not claim to provide final safety, compliance, surfacing, or installation approval.
