import * as React from "react";

import {
  NavigationMenu,
  NavigationMenuContent,
  NavigationMenuItem,
  NavigationMenuLink,
  NavigationMenuList,
  NavigationMenuTrigger,
  navigationMenuTriggerStyle,
} from "@/components/ui/navigation-menu";
import NavMenuLink from "@/components/other/navMenuLink";

const productLinks: { title: string; href: string; description: string }[] = [
  {
    title: "All Products",
    href: "/products",
    description: "Books, gadgets, and office supplies from the store API.",
  },
  {
    title: "Flash Sales",
    href: "/products",
    description: "Active promotional campaigns and discounted products.",
  },
  {
    title: "Collections",
    href: "/products",
    description: "Curated product sections managed by the admin team.",
  },
  {
    title: "Cart",
    href: "/cart",
    description: "Review and update products before checkout.",
  },
];

const supportLinks: { title: string; href: string; description: string }[] = [
  {
    title: "About",
    href: "/about",
    description: "Basic information about The Reader Bookstore.",
  },
  {
    title: "FAQ",
    href: "/faq",
    description: "Common questions about orders, payment, and delivery.",
  },
  {
    title: "Contact",
    href: "/contact",
    description: "Send a support message to the store.",
  },
];

export function Navbar() {
  return (
    <nav className="hidden md:flex justify-center items-center">
      <NavigationMenu>
        <NavigationMenuList>
          <NavigationMenuItem>
            <NavigationMenuLink asChild>
              <NavMenuLink name="HOME" />
            </NavigationMenuLink>
          </NavigationMenuItem>

          <NavigationMenuItem>
            <NavigationMenuTrigger>
              <NavigationMenuLink asChild>
                <NavMenuLink name="PRODUCTS" to="/products" />
              </NavigationMenuLink>
            </NavigationMenuTrigger>
            <NavigationMenuContent>
              <ul className="grid w-[420px] gap-2 p-2 md:w-[560px] md:grid-cols-2">
                {productLinks.map((item) => (
                  <ListItem key={item.title} title={item.title} href={item.href}>
                    {item.description}
                  </ListItem>
                ))}
              </ul>
            </NavigationMenuContent>
          </NavigationMenuItem>

          <NavigationMenuItem>
            <NavigationMenuTrigger>
              <NavigationMenuLink asChild>
                <NavMenuLink name="ABOUT US" to="/about" />
              </NavigationMenuLink>
            </NavigationMenuTrigger>
            <NavigationMenuContent>
              <ul className="w-96 p-2">
                {supportLinks.map((item) => (
                  <ListItem key={item.title} title={item.title} href={item.href}>
                    {item.description}
                  </ListItem>
                ))}
              </ul>
            </NavigationMenuContent>
          </NavigationMenuItem>

          <NavigationMenuItem>
            <NavigationMenuLink asChild className={navigationMenuTriggerStyle()}>
              <NavMenuLink name="FAQs" to="/faq" />
            </NavigationMenuLink>
          </NavigationMenuItem>

          <NavigationMenuItem>
            <NavigationMenuLink asChild className={navigationMenuTriggerStyle()}>
              <NavMenuLink name="CONTACT" to="/contact" />
            </NavigationMenuLink>
          </NavigationMenuItem>
        </NavigationMenuList>
      </NavigationMenu>
    </nav>
  );
}

function ListItem({
  title,
  children,
  href,
  ...props
}: React.ComponentPropsWithoutRef<"li"> & { href: string }) {
  return (
    <li {...props}>
      <NavigationMenuLink href={href} className="block rounded-md p-3 hover:bg-blue-50">
        <div className="text-sm font-semibold leading-none text-slate-900">{title}</div>
        <div className="mt-2 line-clamp-2 text-sm leading-5 text-muted-foreground">{children}</div>
      </NavigationMenuLink>
    </li>
  );
}
