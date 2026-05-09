import { useEffect, useState } from "react";
import { Link } from "react-router-dom";
import { Mail, MapPin, Phone } from "lucide-react";

import myLogo from "../assets/Logo.svg";
import { getSiteSettingsApi } from "@/apis/siteApi";

type Settings = Record<string, string>;

function Footer() {
  const [settings, setSettings] = useState<Settings>({});

  useEffect(() => {
    getSiteSettingsApi()
      .then((res) => {
        if (res.success) setSettings(res.data || {});
      })
      .catch(() => undefined);
  }, []);

  const address = settings.contact_address || "268 Ly Thuong Kiet Street, District 10, HCMC";
  const phone = settings.contact_phone || "+84 12345 6789";
  const email = settings.contact_email || "support@store.com";
  const siteName = settings.site_name || "THE READER";

  return (
    <footer className="bg-(--color-dark) px-6 py-12 text-white md:px-10">
      <div className="mx-auto grid max-w-7xl grid-cols-1 gap-8 md:grid-cols-5">
        <div className="flex flex-col items-center gap-4 md:col-span-2 md:items-start">
          <img src={myLogo} alt={`${siteName} Logo`} className="mx-auto mb-2 h-40 w-auto" />

          <div className="mx-auto space-y-3 text-sm text-gray-300">
            <div className="flex items-start gap-3">
              <MapPin size={18} className="text-(--color-brand) shrink-0" />
              <span>
                <span className="font-bold text-white">Address:</span> {address}
              </span>
            </div>
            <div className="flex items-center gap-3">
              <Phone size={18} className="text-(--color-brand) shrink-0" />
              <span>
                <span className="font-bold text-white">Phone:</span> {phone}
              </span>
            </div>
            <div className="flex items-center gap-3">
              <Mail size={18} className="text-(--color-brand) shrink-0" />
              <span>
                <span className="font-bold text-white">Email:</span> {email}
              </span>
            </div>
          </div>
        </div>

        <FooterColumn
          title="About Us"
          links={[
            ["Our Story", "/about"],
            ["Customer Support", "/contact"],
            ["FAQ", "/faq"],
          ]}
        />
        <FooterColumn
          title="Products"
          links={[
            ["All Products", "/products"],
            ["Flash Sales", "/products"],
            ["Cart", "/cart"],
          ]}
        />
        <FooterColumn
          title="Account"
          links={[
            ["Profile", "/profile"],
            ["Orders", "/orders"],
            ["Login", "/login"],
          ]}
        />
      </div>

      <div className="mt-12 pt-8 text-center text-xs text-gray-400">
        © {new Date().getFullYear()} {siteName}. All rights reserved.
      </div>
    </footer>
  );
}

function FooterColumn({ title, links }: { title: string; links: [string, string][] }) {
  return (
    <div className="flex flex-col gap-4">
      <h4 className="text-lg font-bold uppercase tracking-wider">{title}</h4>
      <ul className="space-y-2 text-sm text-gray-400">
        {links.map(([label, to]) => (
          <li key={label}>
            <Link to={to} className="transition-colors hover:text-white">
              {label}
            </Link>
          </li>
        ))}
      </ul>
    </div>
  );
}

export default Footer;
