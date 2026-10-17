export interface SocialLink {
    id: string;
    label: string;
    href: string;
    icon: string;
    category: 'social' | 'chat' | 'contact';
    external?: boolean;
}

export const socials: SocialLink[] = [
  {
    "id": "github",
    "label": "GitHub",
    "href": "https://github.com/tjay",
    "icon": "github",
    "category": "social",
    "external": true
  },
  {
    "id": "linkedin",
    "label": "LinkedIn",
    "href": "https://www.linkedin.com/company/gorillamade",
    "icon": "linkedin",
    "category": "social",
    "external": true
  },
  {
    "id": "instagram",
    "label": "Instagram",
    "href": "https://www.instagram.com/gorillamade.nl/",
    "icon": "instagram",
    "category": "social",
    "external": true
  },
  {
    "id": "nostr",
    "label": "Nostr",
    "href": "https://njump.me/npub1s4eztsvantqp72jh2ygzk8t4q9gwva8xkqdj83kha9dupyk23kwqmlya7j",
    "icon": "nostr",
    "category": "social",
    "external": true
  },
  {
    "id": "xmpp",
    "label": "XMPP Chat",
    "href": "xmpp:gorillamade@conversations.im",
    "icon": "message-circle",
    "category": "chat"
  },
  {
    "id": 'whatsapp',
    "label": 'WhatsApp',
    "href": 'https://wa.me/31613897425?text=Hoi%20GorillaMade%2C%20ik%20heb%20een%20vraag!',
    "icon": 'message-circle',
    "category": 'chat',
    "external": true,
  },
  {
    "id": "email",
    "label": "E-mail",
    "href": "mailto:hallo@gorillamade.nl",
    "icon": "mail",
    "category": "contact"
  }
];

export const socialLinks = socials.filter((item) => item.category === 'social' && item.href.trim() !== '');
export const chatLinks = socials.filter((item) => item.category === 'chat' && item.href.trim() !== '');
export const contactLinks = socials.filter((item) => item.category === 'contact' && item.href.trim() !== '');
export function getSocial(id: string): SocialLink | undefined {
    return socials.find((item) => item.id === id);
}
