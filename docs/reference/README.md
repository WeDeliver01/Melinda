# Reference material

## tokens.css

The design system, extracted from the client's live stylesheets on 2026-09-13. Every
value is measured, not estimated. Notes at the bottom of the file flag the decisions
still open with the client.

## live-html/

Five full page captures from melindaskitchen.co.za, saved from Chrome. These are the
ground truth for layout and spacing.

| File | Page |
|---|---|
| `Melinda_s_Kitchen___Bespoke_wedding_cake_designer_Pretoria.html` | Homepage |
| `Shop___Melinda_s_Kitchen.html` | Shop archive |
| `Weddings___Melinda_s_Kitchen.html` | Weddings |
| `FAQ___Melinda_s_Kitchen.html` | FAQ |
| `Gallery___Melinda_s_Kitchen.html` | Gallery |

Each references 15 external stylesheets that are **not** committed here: Total's
`style.css`, `js_composer.min.css`, `wpex-woocommerce.css`, `vcex-shortcodes.css` and
others. They are licensed commercial code. The values that matter were extracted into
`tokens.css` and HANDOVER section 5.

To re-capture them: open the page, Ctrl+S, choose "Webpage, Complete". Chrome writes a
`_files` folder alongside the HTML containing every stylesheet.

The inline `<style>` blocks inside these HTML files survive on their own and carry the
Customizer output and the client's custom CSS, which is where most of the brand values
came from.
