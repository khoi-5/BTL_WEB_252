import { ArrowLeft, PackageCheck, ShoppingCart } from "lucide-react";
import { useEffect, useState } from "react";
import { Link, useParams } from "react-router-dom";

import { getImageUrl, getProductDetailApi } from "@/apis/productApi";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { useToast } from "@/hooks/useToast";
import type { ProductVersion } from "@/schema/product";
import { useCartStore } from "@/store/cartStore";
import { useUserStore } from "@/store/userStore";

const formatPrice = (price: number | string) =>
  `${Number(price || 0).toLocaleString("vi-VN")} VND`;

function ProductDetails() {
  const { id } = useParams();
  const { toast, showToast } = useToast();
  const user = useUserStore((s) => s.user);
  const addItem = useCartStore((s) => s.addItem);

  const [product, setProduct] = useState<ProductVersion | null>(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    const versionId = Number(id);
    if (!versionId) {
      setLoading(false);
      return;
    }

    setLoading(true);
    getProductDetailApi(versionId)
      .then((res) => {
        if (res.success) {
          setProduct(res.data);
        } else {
          showToast(res.message || "Product not found.", "error");
        }
      })
      .catch((err) => {
        showToast(err.response?.data?.message || "Could not connect to the server.", "error");
      })
      .finally(() => setLoading(false));
  }, [id]);

  const handleAddToCart = async () => {
    if (!product) return;

    if (!user?.user_id) {
      showToast("Please log in to add items to your cart.", "error");
      return;
    }

    const res = await addItem(user.user_id, product.version_id, 1);

    if (!res.success) {
      showToast(res.message || "Could not add this item to your cart.", "error");
      return;
    }

    showToast("Added to cart.", "success");
  };

  if (loading) {
    return (
      <div className="min-h-screen bg-[#f6f7fb] px-4 py-10 md:px-8">
        <div className="mx-auto max-w-6xl animate-pulse rounded-lg bg-white p-8 shadow-sm">
          <div className="h-8 w-48 rounded bg-slate-200" />
          <div className="mt-8 grid gap-8 lg:grid-cols-[420px_1fr]">
            <div className="h-[480px] rounded-lg bg-slate-200" />
            <div className="space-y-4">
              <div className="h-10 w-3/4 rounded bg-slate-200" />
              <div className="h-6 w-1/2 rounded bg-slate-200" />
              <div className="h-28 rounded bg-slate-200" />
            </div>
          </div>
        </div>
      </div>
    );
  }

  if (!product) {
    return (
      <div className="min-h-screen bg-[#f6f7fb] px-4 py-16 text-center">
        <h1 className="m-0 text-3xl font-extrabold text-slate-950">Product not found</h1>
        <Button asChild className="mt-6">
          <Link to="/products">Back to Store</Link>
        </Button>
      </div>
    );
  }

  const soldOut = Number(product.stock_quantity) <= 0;

  return (
    <div className="min-h-screen bg-[#f6f7fb] px-4 py-10 md:px-8 lg:px-12">
      <div className="mx-auto max-w-7xl text-left">
        <Button asChild variant="ghost" className="mb-5">
          <Link to="/products">
            <ArrowLeft className="mr-2 h-4 w-4" />
            Back to Products
          </Link>
        </Button>

        <div className="grid overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm lg:grid-cols-[460px_1fr]">
          <div className="bg-slate-100 p-6">
            <img
              src={getImageUrl(product.image_url)}
              alt={product.product_name}
              className="h-[520px] w-full object-contain"
              onError={(e) => {
                e.currentTarget.src = "/icons.svg";
              }}
            />
          </div>

          <div className="p-6 md:p-8">
            <div className="flex flex-wrap items-center gap-3">
              <Badge variant="outline" className="bg-blue-50 text-blue-700">
                {product.brand || "Bookstore"}
              </Badge>
              <span className="text-sm text-slate-500">SKU: {product.sku}</span>
            </div>

            <h1 className="m-0 mt-4 text-4xl font-extrabold tracking-normal text-slate-950">
              {product.product_name}
            </h1>
            <p className="mt-3 text-base text-slate-500">{product.version_name}</p>

            <div className="mt-6 flex flex-wrap items-end gap-4 border-b border-slate-200 pb-6">
              <p className="text-4xl font-extrabold text-blue-700">{formatPrice(product.price)}</p>
              <Badge
                variant={soldOut ? "destructive" : "secondary"}
                className={soldOut ? "" : "bg-green-100 text-green-700"}
              >
                {soldOut ? "Out of stock" : `${product.stock_quantity} in stock`}
              </Badge>
            </div>

            <div className="mt-6 grid gap-4 rounded-lg bg-slate-50 p-5 sm:grid-cols-2">
              <Info label="Format" value={product.format_type} />
              <Info label="Language" value={product.language || "N/A"} />
              <Info label="Cover" value={product.cover_type || "N/A"} />
              <Info label="Edition" value={product.edition || "N/A"} />
            </div>

            <div className="mt-7">
              <h2 className="m-0 flex items-center gap-2 text-xl font-bold text-slate-950">
                <PackageCheck className="h-5 w-5 text-blue-700" />
                Product Description
              </h2>
              <p className="mt-3 leading-8 text-slate-600">
                {product.description || "This product does not have a detailed description yet."}
              </p>
            </div>

            <div className="mt-8 flex flex-col gap-3 sm:flex-row">
              <Button className="h-12 flex-1" disabled={soldOut} onClick={handleAddToCart}>
                <ShoppingCart className="mr-2 h-5 w-5" />
                Add to Cart
              </Button>
              <Button asChild variant="outline" className="h-12 flex-1">
                <Link to="/cart">View Cart</Link>
              </Button>
            </div>
          </div>
        </div>
      </div>

      {toast.show && (
        <div className={`toast ${toast.type} show`}>{toast.message}</div>
      )}
    </div>
  );
}

function Info({ label, value }: { label: string; value: string | number }) {
  return (
    <div>
      <p className="text-sm text-slate-500">{label}</p>
      <p className="mt-1 font-semibold text-slate-950">{value}</p>
    </div>
  );
}

export default ProductDetails;
