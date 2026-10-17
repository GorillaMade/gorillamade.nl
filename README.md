# GorillaMade Astro Starter

Een schone Astro-basis voor nieuwe projecten, gebouwd rond het GorillaMade Sass design system en een compacte componentbibliotheek.

## Start

```bash
npm install
npm run dev
```

Voor productiecontrole:

```bash
npm run check
npm run build
```

## Eerst aanpassen bij een nieuw project

1. `src/config/site.ts` — naam, URL, description, locale en social image.
2. `astro.config.mjs` — zet `site` gelijk aan de productie-URL.
3. `public/og-default.svg` en `public/favicon.svg` — vervang door klant-assets.
4. `src/pages/index.astro` — de homepage gebruikt alle componenten als praktische referentie; vervang demo-copy door projectinhoud.
5. Voeg projectspecifieke routes pas toe wanneer het project ze nodig heeft.

## Structuur

- `sass/` — design tokens, base styles, patterns en utilities. Dit is de visuele source of truth.
- `src/components/` — herbruikbare Astro-primitives, gegroepeerd op functie.
- `src/layouts/BaseLayout.astro` — document shell, navigatie/footer, SEO en WebSite JSON-LD.
- `src/config/site.ts` — centrale site metadata.
- `src/pages/` — alleen homepage, 404 en robots als neutrale starter.
- `public/` — minimale demo/media/SEO assets.

De homepage gebruikt alle componenten. `BaseHead` en `JsonLd` horen in de head/layout; `Nav`, `NavLink`, `SkipLink` en `Footer` horen in de globale shell. De overige componenten worden op de homepage in hun bedoelde context getoond.
