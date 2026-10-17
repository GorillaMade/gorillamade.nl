import { defineCollection, z } from "astro:content";
import { glob } from "astro/loaders";

const showcases = defineCollection({
  loader: glob({
    pattern: "**/*.md",
    base: "./src/content/showcases",
  }),

  schema: z.object({
    title: z.string(),
    client: z.string(),
    year: z.number(),
    category: z.string(),

    services: z.array(z.string()),
    summary: z.string(),

    // Optioneel voor oudere showcasebestanden.
    // Nieuwe showcases gebruiken de Markdown-inhoud.
    challenge: z.string().optional(),
    solution: z.string().optional(),
    result: z.string().optional(),

    featured: z.boolean().default(false),
    draft: z.boolean().default(false),

    cover: z.string().optional(),
    gallery: z.array(z.string()).optional(),

    url: z.string().url().optional(),

    quote: z.string().optional(),
    quoteAuthor: z.string().optional(),
    quoteAuthorFunction: z.string().optional(),

    results: z
      .array(
        z.object({
          value: z.string(),
          label: z.string(),
        }),
      )
      .optional(),
  }),
});

const blog = defineCollection({
  loader: glob({
    pattern: "**/*.md",
    base: "./src/content/blog",
  }),

  schema: z.object({
    title: z.string(),
    date: z.coerce.date(),
    category: z.string(),
    summary: z.string(),
    cover: z.string().optional(),
  }),
});

const services = defineCollection({
  loader: glob({
    pattern: "**/*.md",
    base: "./src/content/services",
  }),

  schema: z.object({
    title: z.string(),
    order: z.number().int(),
    tag: z.string(),
    summary: z.string(),
    intro: z.string(),
    points: z.array(z.string()),
    deliverable: z.string(),
    featured: z.boolean().default(true),
    icon: z.string().optional(),
  }),
});

export const collections = {
  showcases,
  blog,
  services,
};
