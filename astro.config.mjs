import { defineConfig } from 'astro/config';
import sitemap from '@astrojs/sitemap';
import alpinejs from '@astrojs/alpinejs';
export default defineConfig({site:'https://gorillamade.nl',integrations:[sitemap(),alpinejs()]});
