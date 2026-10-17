
import { getCollection } from 'astro:content';

interface ServiceOptions {
    featuredOnly?: boolean;
}

export async function getServices(
    { featuredOnly = false }: ServiceOptions = {}
) {
    const services = await getCollection('services');

    return services
        .filter((service) => {
            return !featuredOnly || service.data.featured === true;
        })
        .sort((a, b) => {
            return (a.data.order ?? 999) - (b.data.order ?? 999);
        });
}
