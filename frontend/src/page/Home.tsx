import { Link } from "react-router-dom";
import { ArrowRight, BookOpen, Headphones, ShieldCheck, Truck } from "lucide-react";

import hero from "@/assets/hero.png";
import { Button } from "@/components/ui/button";

const highlights = [
  {
    icon: BookOpen,
    title: "Curated Books",
    text: "Programming, business, self-improvement, and office supplies for daily study.",
  },
  {
    icon: Truck,
    title: "Clear Delivery",
    text: "Receiver and order information stay connected to your customer account.",
  },
  {
    icon: ShieldCheck,
    title: "Flexible Payment",
    text: "Support for COD, bank transfer, and common payment methods in the system.",
  },
  {
    icon: Headphones,
    title: "Fast Support",
    text: "Send questions through the contact page or check FAQ for quick answers.",
  },
];

function Home() {
  return (
    <div className="bg-[#f6f7fb] text-left">
      <section className="bg-white px-4 py-10 md:px-8 lg:px-12">
        <div className="mx-auto grid max-w-7xl gap-10 lg:grid-cols-[1.05fr_0.95fr] lg:items-center">
          <div>
            <p className="mb-4 text-sm font-bold uppercase tracking-normal text-blue-700">
              The Reader Bookstore
            </p>
            <h1 className="m-0 max-w-3xl text-4xl font-extrabold tracking-normal text-slate-950 md:text-6xl">
              A clean place to buy books and study supplies.
            </h1>
            <p className="mt-5 max-w-2xl text-base leading-8 text-slate-600">
              Browse books, add them to your cart, track orders, and contact support in one simple flow.
            </p>
            <div className="mt-8 flex flex-col gap-3 sm:flex-row">
              <Button asChild className="h-12 px-6">
                <Link to="/products">
                  Browse Products
                  <ArrowRight className="ml-2 h-5 w-5" />
                </Link>
              </Button>
              <Button asChild variant="outline" className="h-12 px-6">
                <Link to="/faq">View FAQ</Link>
              </Button>
            </div>
          </div>

          <div className="overflow-hidden rounded-lg border border-slate-200 bg-slate-100">
            <img src={hero} alt="Bookstore" className="h-full min-h-[320px] w-full object-cover" />
          </div>
        </div>
      </section>

      <section className="px-4 py-12 md:px-8 lg:px-12">
        <div className="mx-auto grid max-w-7xl gap-4 sm:grid-cols-2 lg:grid-cols-4">
          {highlights.map((item) => {
            const Icon = item.icon;
            return (
              <div key={item.title} className="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
                <div className="mb-4 flex h-11 w-11 items-center justify-center rounded-md bg-blue-50 text-blue-700">
                  <Icon className="h-5 w-5" />
                </div>
                <h2 className="m-0 text-lg font-bold text-slate-950">{item.title}</h2>
                <p className="mt-2 text-sm leading-6 text-slate-600">{item.text}</p>
              </div>
            );
          })}
        </div>
      </section>

      <section className="px-4 pb-14 md:px-8 lg:px-12">
        <div className="mx-auto flex max-w-7xl flex-col gap-5 rounded-lg bg-slate-950 p-8 text-white md:flex-row md:items-center md:justify-between">
          <div>
            <h2 className="m-0 text-2xl font-extrabold text-white">Promotions live on the Products page.</h2>
            <p className="mt-2 text-sm text-slate-300">
              Flash sales and product sections are loaded directly from the backend API.
            </p>
          </div>
          <Button asChild variant="secondary" className="h-11 w-full md:w-auto">
            <Link to="/products">Go to Store</Link>
          </Button>
        </div>
      </section>
    </div>
  );
}

export default Home;
