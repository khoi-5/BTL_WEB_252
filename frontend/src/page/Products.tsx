import Autoplay from "embla-carousel-autoplay";
import { Divide, Eye, Flame, RotateCcw, Search, ShoppingCart, Sparkles, Zap } from "lucide-react";
import { useEffect, useMemo, useRef, useState } from "react";
import { Link, useSearchParams } from "react-router-dom";

import { getImageUrl, getProductDetailApi, getProductsApi } from "@/apis/productApi";
import {
  getCategoriesApi,
  getFlashSalesApi,
  getProductsByCategoryApi,
  getSectionsApi,
} from "@/apis/siteApi";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Card, CardContent } from "@/components/ui/card";
import {
  Carousel,
  CarouselContent,
  CarouselItem,
  CarouselNext,
  CarouselPrevious,
} from "@/components/ui/carousel";
import { Input } from "@/components/ui/input";
import {
  Pagination,
  PaginationContent,
  PaginationEllipsis,
  PaginationItem,
  PaginationLink,
  PaginationNext,
  PaginationPrevious,
} from "@/components/ui/pagination";
import { useToast } from "@/hooks/useToast";
import type {
  Category,
  FlashSale,
  FlashSaleProduct,
  ProductPagination,
  ProductSection,
  ProductVersion,
} from "@/schema/product";
import { useCartStore } from "@/store/cartStore";
import { useUserStore } from "@/store/userStore";
import TimeCounter from "@/components/other/timeCounter";

const formatPrice = (price: number | string) =>
  `${Number(price || 0).toLocaleString("vi-VN")} VND`;

function stockText(item: Pick<ProductVersion, "stock_quantity">) {
  if (Number(item.stock_quantity) <= 0) return "Out of stock";
  if (Number(item.stock_quantity) <= 5) return "Low stock";
  return "In stock";
}


function Products() {
  const [searchParams, setSearchParams] = useSearchParams();
  const initialQuery = searchParams.get("q") || "";

  const { toast, showToast } = useToast();
  const user = useUserStore((s) => s.user);
  const addItem = useCartStore((s) => s.addItem);

  const [products, setProducts] = useState<ProductVersion[]>([]);
  const [pagination, setPagination] = useState<ProductPagination>({
    current_page: 1,
    total_pages: 1,
    total_items: 0,
    limit: 12,
  });
  const [categories, setCategories] = useState<Category[]>([]);
  const [flashSales, setFlashSales] = useState<FlashSale[]>([]);
  const [sections, setSections] = useState<ProductSection[]>([]);
  const [selectedProduct, setSelectedProduct] = useState<ProductVersion | null>(null);
  const [keyword, setKeyword] = useState(initialQuery);
  const [searchText, setSearchText] = useState(initialQuery);
  const [selectedCategoryId, setSelectedCategoryId] = useState<number | null>(null);
  const [page, setPage] = useState(Number(searchParams.get("page") || 1));
  const [loading, setLoading] = useState(false);
  const [featureLoading, setFeatureLoading] = useState(true);

  const plugin = useRef(Autoplay({ delay: 3500, stopOnInteraction: true }));

  const activeFlashSales = useMemo(
    () => flashSales.filter((sale) => sale.products?.length > 0),
    [flashSales]
  );

  const loadProducts = async () => {
    try {
      setLoading(true);

      const res = selectedCategoryId
        ? await getProductsByCategoryApi(selectedCategoryId, page, 12)
        : await getProductsApi({ q: searchText, page, limit: 12 });

      if (!res.success) {
        showToast(res.message || "Could not load products.", "error");
        return;
      }

      setProducts(res.data.items || []);
      setPagination(res.data.pagination);
    } catch (err: any) {
      showToast(err.response?.data?.message || "Could not connect to the server.", "error");
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    loadProducts();
  }, [page, searchText, selectedCategoryId]);

  useEffect(() => {
    setFeatureLoading(true);
    Promise.allSettled([getCategoriesApi(), getFlashSalesApi(), getSectionsApi()])
      .then(([categoryRes, flashRes, sectionRes]) => {
        if (categoryRes.status === "fulfilled" && categoryRes.value.success) {
          setCategories(categoryRes.value.data || []);
        }
        if (flashRes.status === "fulfilled" && flashRes.value.success) {
          setFlashSales(flashRes.value.data || []);
        }
        if (sectionRes.status === "fulfilled" && sectionRes.value.success) {
          setSections(sectionRes.value.data || []);
        }
      })
      .finally(() => setFeatureLoading(false));
  }, []);

  useEffect(() => {
    const params = new URLSearchParams();
    if (searchText) params.set("q", searchText);
    if (page > 1) params.set("page", String(page));
    setSearchParams(params, { replace: true });
  }, [page, searchText, setSearchParams]);

  const handleSearch = () => {
    setSelectedCategoryId(null);
    setPage(1);
    setSearchText(keyword.trim());
  };

  const handleClearSearch = () => {
    setKeyword("");
    setSearchText("");
    setSelectedCategoryId(null);
    setPage(1);
  };

  const handleCategory = (categoryId: number | null) => {
    setSelectedCategoryId(categoryId);
    setKeyword("");
    setSearchText("");
    setPage(1);
  };



  const handleViewDetail = async (id: number) => {
    try {
      const res = await getProductDetailApi(id);

      if (!res.success) {
        showToast(res.message || "Could not load product details.", "error");
        return;
      }

      setSelectedProduct(res.data);
    } catch (err: any) {
      showToast(err.response?.data?.message || "Could not connect to the server.", "error");
    }
  };

  const handleAddToCart = async (versionId: number) => {
    const customerId = user?.user_id;

    if (!customerId) {
      showToast("Please log in to add items to your cart.", "error");
      return;
    }

    const res = await addItem(Number(customerId), Number(versionId), 1);

    if (!res.success) {
      showToast(res.message || "Could not add this item to your cart.", "error");
      return;
    }

    showToast("Added to cart.", "success");
  };

  const itemsRef = useRef(new Map());


  // 2. A reusable function that takes a ref and scrolls to it
  const scrollToCategoryId = (id: any) => {
    // Get the exact HTML node from our Map dictionary
    const node = itemsRef.current.get(id);
    if (node) {
      node.scrollIntoView({ behavior: "smooth", block: "start" });
    }
  };

  const renderProductCard = (
    item: ProductVersion,
    options: { compact?: boolean; salePrice?: number } = {}
  ) => {
    const soldOut = Number(item.stock_quantity) <= 0;

    return (
      <Card
        key={item.version_id}
        className="group flex h-full overflow-hidden rounded-lg border-slate-200 bg-white shadow-sm transition hover:-translate-y-1 hover:shadow-xl"
      >
        <div className="relative flex aspect-[4/3] items-center justify-center bg-slate-100 p-4">
          <Link to={`/products/${item.version_id}`} className="absolute inset-0 z-10" aria-label={item.product_name} />
          <img
            src={getImageUrl(item.image_url)}
            alt={item.product_name}
            className="h-full w-full object-contain transition duration-500 group-hover:scale-105"
            onError={(e) => {
              e.currentTarget.src = "/icons.svg";
            }}
          />
          <Badge
            variant={soldOut ? "destructive" : "secondary"}
            className="absolute left-3 top-3 bg-white/95 shadow-sm"
          >
            {stockText(item)}
          </Badge>
          <Button
            size="icon"
            variant="secondary"
            className="absolute bottom-3 right-3 z-20 h-9 w-9 rounded-full opacity-0 shadow-md transition group-hover:opacity-100"
            onClick={() => handleViewDetail(item.version_id)}
          >
            <Eye className="h-4 w-4" />
          </Button>
        </div>

        <CardContent className="flex flex-1 flex-col p-4 text-left">
          <div className="mb-2 flex items-center justify-between gap-2">
            <span className="truncate text-xs font-semibold uppercase tracking-wide text-blue-700">
              {item.brand || "Bookstore"}
            </span>
            <span className="text-xs text-slate-400">{item.sku}</span>
          </div>
          <Link
            to={`/products/${item.version_id}`}
            className="line-clamp-2 min-h-12 text-base font-bold text-slate-950 transition hover:text-blue-700"
          >
            {item.product_name}
          </Link>
          <p className="mt-2 line-clamp-1 text-sm text-slate-500">{item.version_name}</p>
          <div className="mt-auto flex items-end justify-between gap-3 pt-5">
            <div>
              {options.salePrice ? (
                <>
                  <p className="text-lg font-extrabold text-red-600">{formatPrice(options.salePrice)}</p>
                  <p className="text-sm text-slate-400 line-through">{formatPrice(item.price)}</p>
                </>
              ) : (
                <p className="text-lg font-extrabold text-slate-950">{formatPrice(item.price)}</p>
              )}
            </div>
            <Button
              size="icon"
              className="h-10 w-10 rounded-full"
              disabled={soldOut}
              onClick={() => handleAddToCart(item.version_id)}
            >
              <ShoppingCart className="h-5 w-5" />
            </Button>
          </div>
        </CardContent>
      </Card>
    );
  };

  const renderFlashProduct = (product: FlashSaleProduct) => {
    const available = Math.max(0, Number(product.stock_allocated) - Number(product.stock_sold));
    const discount =
      Number(product.original_price) > 0
        ? Math.round((1 - Number(product.sale_price) / Number(product.original_price)) * 100)
        : 0;

    const cardProduct: ProductVersion = {
      version_id: product.version_id,
      product_id: 0,
      product_name: product.product_name,
      brand: product.brand,
      description: null,
      sku: "",
      version_name: product.version_name,
      format_type: "special_edition",
      language: "",
      cover_type: null,
      edition: null,
      price: Number(product.original_price),
      stock_quantity: available,
      image_url: product.image_url,
      version_status: available > 0 ? "available" : "out_of_stock",
    };

    return (
      <CarouselItem
        key={product.version_id}
        className="basis-[85%] sm:basis-1/2 lg:basis-1/3 xl:basis-1/4"
      >
        <div className="relative h-full">
          {discount > 0 && (
            <Badge className="absolute right-4 top-4 z-30 bg-red-600 text-white">
              -{discount}%
            </Badge>
          )}
          {renderProductCard(cardProduct, { salePrice: Number(product.sale_price) })}
          <div className="mt-3 h-2 overflow-hidden rounded-full bg-red-100">
            <div
              className="h-full rounded-full bg-red-600"
              style={{
                width: `${Math.min(
                  100,
                  (Number(product.stock_sold) / Math.max(1, Number(product.stock_allocated))) * 100
                )}%`,
              }}
            />
          </div>
          <p className="mt-2 text-center text-xs font-medium text-white/90">
            {available}/{product.stock_allocated} items left
          </p>
        </div>
      </CarouselItem>
    );
  };

  return (
    <div className="min-h-screen bg-[#f6f7fb]">
      <section className="bg-white px-4 py-10 text-left md:px-8 lg:px-12">
        <div className="mx-auto grid max-w-7xl gap-8 lg:grid-cols-[1fr_420px] lg:items-end">
          <div>
            {/* <Badge variant="outline" className="mb-4 bg-blue-50 text-blue-700">
              The Reader Bookstore
            </Badge> */}
            <h1 className="m-0 max-w-3xl text-4xl font-extrabold text-blue-700 tracking-normal md:text-5xl">
              Books, study gadgets, and office supplies in one place.
            </h1>
            <p className="mt-4 max-w-2xl text-base leading-7">

            </p>
          </div>


        </div>
      </section>

      <div className="mx-auto max-w-7xl space-y-12 px-4 py-10 md:px-8 lg:px-12">
        <section className="space-y-4 text-left">
          <div className="flex items-center justify-between gap-4">
            <h2 className="m-0 text-2xl font-bold text-blue-600">Product Categories</h2>
            {featureLoading && <span className="text-sm text-slate-500">Loading...</span>}
          </div>
          <Carousel
            opts={{
              align: "start",
              dragFree: true,
            }}
            className="w-full bg-white rounded-2xl p-4 "
          >
            <CarouselContent className="-ml-3 pb-2 gap-4 flex items-center justify-center">

              {activeFlashSales.map((sale) => (
                <CarouselItem key={sale.flash_sale_id} className="pl-3 basis-auto"
                  onClick={() => scrollToCategoryId(sale.flash_sale_name)}>
                  <img
                    src={getImageUrl(sale.flash_sale_image)}
                    alt={sale.flash_sale_name}
                    className="w-10 h-10 mx-auto"
                    onError={(e) => {
                      e.currentTarget.src = "/icons.svg";
                    }}
                  />
                  <div
                    className="rounded-full"
                  >
                    {sale.flash_sale_name}
                  </div>
                </CarouselItem>
              ))}

              {/* Mapped Category Items */}
              {sections.map((sec) => (
                <CarouselItem key={sec.section_id} className="pl-3 basis-auto"
                  onClick={() => scrollToCategoryId(sec.section_name)}>
                  <img
                    src={getImageUrl(sec.section_image)}
                    alt={sec.section_name}
                    className="w-10 h-10 mx-auto"
                    onError={(e) => {
                      e.currentTarget.src = "/icons.svg";
                    }}
                  />
                  <div
                    className="rounded-full"
                  >
                    {sec.section_name}
                  </div>
                </CarouselItem>
              ))}

            </CarouselContent>

            {/* Optional: Add navigation arrows (hidden on mobile since users can just swipe) */}
            <CarouselPrevious className="hidden md:flex" />
            <CarouselNext className="hidden md:flex" />
          </Carousel>
        </section>




      </div>

      {activeFlashSales.map((sale) => (
        <section key={sale.flash_sale_id} className="scroll-mt-24  w-full bg-gradient-to-r from-red-600 to-orange-500 p-5 text-left shadow-xl md:p-8 my-4"
          ref={(node) => {
            if (node) {
              itemsRef.current.set(sale.flash_sale_name, node);
            } else {
              itemsRef.current.delete(sale.flash_sale_name);
            }
          }}>
          <div className="mb-6 flex flex-col gap-4 text-white md:flex-row md:items-end md:justify-between">
            <div className="flex gap-2">
              <div className="mb-2 flex items-center gap-2">
                <Flame className="h-7 w-7 text-yellow-200" />
                {/* <Badge className="bg-white text-red-700">{sale.sale_status}</Badge> */}
              </div>
              <div>
                <h2 className="m-0 text-3xl font-extrabold text-white">{sale.flash_sale_name}</h2>

                <p className="mt-2 max-w-2xl text-sm text-white/85">{sale.flash_sale_description}</p>
              </div>
            </div>
            <div className="text-xl font-bold text-white/90 flex justify-center items-center gap-4">
              {
                (new Date().getTime() <= new Date(sale.flash_sale_end_time).getTime()) ?
                  <>
                    <div>Start In</div>
                    <TimeCounter targetDate={new Date(sale.flash_sale_start_time)} />
                  </>
                  :
                  <>
                    <div>End In</div>
                    <TimeCounter targetDate={new Date(sale.flash_sale_end_time)} />
                  </>
              }
            </div>
          </div>

          <Carousel
            plugins={[plugin.current]}
            onMouseEnter={plugin.current.stop}
            onMouseLeave={plugin.current.reset}
            opts={{ align: "start", dragFree: true }}
          >
            <CarouselContent className="-ml-4">
              {sale.products.map(renderFlashProduct)}
            </CarouselContent>
            <CarouselPrevious className="hidden border-0 bg-white/20 text-white hover:bg-white hover:text-red-700 lg:flex" />
            <CarouselNext className="hidden border-0 bg-white/20 text-white hover:bg-white hover:text-red-700 lg:flex" />
          </Carousel>
        </section>
      ))}

      <div className="mx-auto max-w-7xl px-4 py-10 md:px-8 lg:px-12">
        {sections.map((section) => (
          <div className="my-4">
            <div ref={(node) => {
              if (node) {
                itemsRef.current.set(section.section_name, node);
              } else {
                itemsRef.current.delete(section.section_name);
              }
            }} className="relative bg-blue-600 p-4 rounded-t-3xl text-white w-[33%] mx-auto">
              <div className="relative z-10 scroll-mt-30 ">

                <h2 className="m-0 text-3xl font-extrabold text-white">{section.section_name}</h2>
                <p className="mt-3 text-sm leading-6 text-white/80">{section.section_description}</p>
              </div>
            </div>

            <section key={section.section_id} className="overflow-hidden rounded-2xl border-slate-200 bg-gradient-to-b from-blue-600 to-30% to-white text-left shadow-sm"
            >
              <div className="p-8">
                <div className="grid grid-cols-1 gap-4 p-5 sm:grid-cols-2 xl:grid-cols-3">
                  {(section.products || []).slice(0, 6).map((item) => renderProductCard(item, { compact: true }))}
                </div>
              </div>
            </section>
          </div>
        ))}

        <div className="bg-white p-4 gap-4 flex flex-col">
          <section className="space-y-6 text-left ">
            <div className="flex flex-col  md:flex-row md:items-end md:justify-between">

              <div className="min-w-10">
                <h2 className="m-0 flex items-center gap-2 text-3xl font-extrabold text-slate-950">

                  Our Products
                </h2>
                <p className="mt-2 text-sm text-slate-500">
                  {pagination.total_items} result{pagination.total_items === 1 ? "" : "s"}
                  {searchText ? ` for "${searchText}"` : ""}
                </p>

              </div>
              <div className="rounded-lg bw-auto">
                <div className="flex items-center gap-2">
                  <div className="relative flex-1">
                    <Search className="absolute left-3 top-1/2 h-5 w-5 -translate-y-1/2 text-slate-400" />
                    <Input
                      className="h-10 bg-white pl-10"
                      placeholder="Search books, authors, SKU..."
                      value={keyword}
                      onChange={(e) => setKeyword(e.target.value)}
                      onKeyDown={(e) => {
                        if (e.key === "Enter") handleSearch();
                      }}
                    />
                  </div>
                  <Button className="h-10 px-4" onClick={handleSearch}>
                    Search
                  </Button>
                  {(searchText || selectedCategoryId) && (
                    <Button size="icon" variant="outline" className="h-12 w-12" onClick={handleClearSearch}>
                      <RotateCcw className="h-5 w-5" />
                    </Button>
                  )}
                </div>
              </div>
            </div>



          </section>


          {/* Product list */}
          {loading ? (
            <div className="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
              {Array.from({ length: 8 }).map((_, i) => (
                <div key={i} className="h-[360px] animate-pulse rounded-lg bg-white p-4 shadow-sm">
                  <div className="h-40 rounded-md bg-slate-200" />
                  <div className="mt-5 h-5 w-3/4 rounded bg-slate-200" />
                  <div className="mt-3 h-4 w-1/2 rounded bg-slate-200" />
                  <div className="mt-16 h-8 w-full rounded bg-slate-200" />
                </div>
              ))}
            </div>
          ) : products.length === 0 ? (
            <div className="rounded-lg border border-dashed border-slate-300 bg-white p-12 text-center">
              <Search className="mx-auto h-12 w-12 text-slate-300" />
              <h3 className="mt-4 text-xl font-bold text-slate-900">No products found</h3>
              <p className="mt-2 text-slate-500">Try another keyword or clear the current filters.</p>
              <Button variant="outline" className="mt-6 rounded-full" onClick={handleClearSearch}>
                Clear Filters
              </Button>
            </div>
          ) : (
            <div className="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
              {products.map((item) => renderProductCard(item))}
            </div>
          )}

          {products.length > 0 && pagination.total_pages > 1 && (
            <Pagination>
              <PaginationContent className="rounded-full border bg-white px-2 py-1 shadow-sm">
                <PaginationItem>
                  <PaginationPrevious
                    href="#"
                    onClick={(e) => {
                      e.preventDefault();
                      if (page > 1) setPage((p) => p - 1);
                    }}
                    className={page <= 1 ? "pointer-events-none opacity-50" : ""}
                  />
                </PaginationItem>

                {Array.from({ length: pagination.total_pages }, (_, i) => i + 1).map((p) => {
                  if (
                    pagination.total_pages > 5 &&
                    p !== 1 &&
                    p !== pagination.total_pages &&
                    Math.abs(page - p) > 1
                  ) {
                    if (p === 2 || p === pagination.total_pages - 1) {
                      return (
                        <PaginationItem key={p}>
                          <PaginationEllipsis />
                        </PaginationItem>
                      );
                    }
                    return null;
                  }

                  return (
                    <PaginationItem key={p}>
                      <PaginationLink
                        href="#"
                        isActive={page === p}
                        onClick={(e) => {
                          e.preventDefault();
                          setPage(p);
                        }}
                      >
                        {p}
                      </PaginationLink>
                    </PaginationItem>
                  );
                })}

                <PaginationItem>
                  <PaginationNext
                    href="#"
                    onClick={(e) => {
                      e.preventDefault();
                      if (page < pagination.total_pages) setPage((p) => p + 1);
                    }}
                    className={page >= pagination.total_pages ? "pointer-events-none opacity-50" : ""}
                  />
                </PaginationItem>
              </PaginationContent>
            </Pagination>
          )}
        </div>

        {selectedProduct && (
          <div className="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/60 p-4 backdrop-blur-sm">
            <div className="grid max-h-[90vh] w-full max-w-5xl overflow-hidden rounded-lg bg-white shadow-2xl lg:grid-cols-[380px_1fr]">
              <div className="bg-slate-100 p-6">
                <img
                  src={getImageUrl(selectedProduct.image_url)}
                  alt={selectedProduct.product_name}
                  className="h-full max-h-[520px] w-full object-contain"
                  onError={(e) => {
                    e.currentTarget.src = "/icons.svg";
                  }}
                />
              </div>
              <div className="overflow-y-auto p-6 text-left">
                <div className="mb-4 flex items-start justify-between gap-4">
                  <div>
                    <Badge variant="outline" className="mb-3">{selectedProduct.brand || "Bookstore"}</Badge>
                    <h2 className="m-0 text-3xl font-extrabold text-slate-950">{selectedProduct.product_name}</h2>
                    <p className="mt-2 text-sm text-slate-500">SKU: {selectedProduct.sku}</p>
                  </div>
                  <Button variant="ghost" onClick={() => setSelectedProduct(null)}>Close</Button>
                </div>

                <p className="text-3xl font-extrabold text-blue-700">{formatPrice(selectedProduct.price)}</p>
                <div className="mt-5 grid grid-cols-2 gap-4 rounded-lg bg-slate-50 p-4 text-sm">
                  <div>
                    <p className="text-slate-500">Version</p>
                    <p className="font-semibold text-slate-900">{selectedProduct.version_name}</p>
                  </div>
                  <div>
                    <p className="text-slate-500">Format</p>
                    <p className="font-semibold text-slate-900">{selectedProduct.format_type}</p>
                  </div>
                  <div>
                    <p className="text-slate-500">Language</p>
                    <p className="font-semibold text-slate-900">{selectedProduct.language || "N/A"}</p>
                  </div>
                  <div>
                    <p className="text-slate-500">Stock</p>
                    <p className="font-semibold text-slate-900">{selectedProduct.stock_quantity}</p>
                  </div>
                </div>

                <div className="mt-6">
                  <h3 className="text-lg font-bold text-slate-950">Product Description</h3>
                  <p className="mt-2 leading-7 text-slate-600">
                    {selectedProduct.description || "This product does not have a detailed description yet."}
                  </p>
                </div>

                <div className="mt-8 flex gap-3">
                  <Button
                    className="h-12 flex-1"
                    disabled={Number(selectedProduct.stock_quantity) <= 0}
                    onClick={() => handleAddToCart(selectedProduct.version_id)}
                  >
                    <ShoppingCart className="mr-2 h-5 w-5" />
                    Add to Cart
                  </Button>
                  <Button asChild variant="outline" className="h-12">
                    <Link to={`/products/${selectedProduct.version_id}`}>View Details Page</Link>
                  </Button>
                </div>
              </div>
            </div>

          </div>

        )}
      </div>
      {toast.show && (
        <div className={`toast ${toast.type} show`}>{toast.message}</div>
      )}
    </div>
  );
}

export default Products;
